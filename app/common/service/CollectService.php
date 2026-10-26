<?php
// +----------------------------------------------------------------------
// | 采集服务（v3.10.2）
// | 抓取列表页 -> 提取链接 -> 抓取详情 -> 起止标记截取标题/正文 -> 入库
// | 规则字段见 yy_collect_rule；支持 {page} 翻页、图片下载到本地、标题去重
// +----------------------------------------------------------------------
namespace app\common\service;

use app\common\model\Category;
use app\common\model\CollectLog;
use app\common\model\CollectRule;
use app\common\model\Content;
use think\facade\Db;

class CollectService
{
    /** 单次执行最多抓取详情条数（避免超时） */
    const MAX_PER_RUN = 50;

    /**
     * 执行一条采集规则
     * @param int $ruleId
     * @return array ['code'=>0|1,'msg'=>...,'data'=>[total,added,skipped,failed]]
     */
    public static function run($ruleId)
    {
        $rule = CollectRule::find($ruleId);
        if (!$rule || (int)$rule['status'] !== 1) {
            return ['code' => 0, 'msg' => '规则不存在或未启用'];
        }

        $stat = ['total' => 0, 'added' => 0, 'skipped' => 0, 'failed' => 0];
        $cat  = Category::find((int)$rule['catid']);
        if (!$cat) {
            return ['code' => 0, 'msg' => '目标栏目不存在'];
        }

        // 目标语言（规则指定）
        $ruleLang = (string)$rule['lang'];
        // 目标栏目模型（内容模型ID，0=默认）
        $modelId = (int)($cat['model_id'] ?? 0);

        $links = [];
        $maxPage = max(1, (int)$rule['max_page']);
        for ($p = 1; $p <= $maxPage; $p++) {
            $url = str_replace('{page}', (string)$p, (string)$rule['list_url']);
            $html = self::fetch($url, (string)$rule['charset']);
            if ($html === '') continue;
            $zone = self::slice($html, (string)$rule['list_start'], (string)$rule['list_end']);
            $links = array_merge($links, self::extractLinks($zone, (string)$rule['link_preg'], $url));
            if (count($links) >= self::MAX_PER_RUN) break;
        }
        // 按 url 去重（保留第一条的缩略图）
        $uniq = [];
        foreach ($links as $l) {
            if (!isset($uniq[$l['url']])) $uniq[$l['url']] = $l;
        }
        $links = array_values($uniq);
        $stat['total'] = count($links);

        // 每语言表名：Content 模型按当前 lang_code 生成表名，这里用 Db 直接操作指定语言表
        $contentTable = 'yy_' . $ruleLang . '_content';
        $modelId = (int)($cat['model_id'] ?? 0);

        $filterWords = array_values(array_filter(array_map('trim', explode(',', (string)$rule['filter_words']))));
        $imgSwitch   = (int)$rule['img_switch'] === 1;

        foreach ($links as $link) {
            $url = trim($link['url']);
            if ($url === '' || stripos($url, 'javascript:') === 0) continue;
            if (count($links) > self::MAX_PER_RUN) break;
            try {
                $detail = self::fetch($url, (string)$rule['charset']);
                if ($detail === '') { $stat['failed']++; continue; }
                $title = trim(strip_tags(self::slice($detail, (string)$rule['title_start'], (string)$rule['title_end'])));
                // 标题超长保护：varchar(255) 超出会整条失败，截断兜底
                $title = mb_substr($title, 0, 250, 'utf-8');
                if ($title === '') { $stat['failed']++; continue; }
                // 过滤词
                $skip = false;
                foreach ($filterWords as $w) {
                    if ($w !== '' && mb_strpos($title, $w) !== false) { $skip = true; break; }
                }
                if ($skip) { $stat['skipped']++; continue; }
                // 标题去重（指定语言内容表）
                $dup = Db::table($contentTable)->where('title', $title)->count();
                if ($dup > 0) { $stat['skipped']++; continue; }

                $body = trim(self::slice($detail, (string)$rule['content_start'], (string)$rule['content_end']));
                if ($body === '') { $stat['failed']++; continue; }

                // 图片下载到本地（可选）：正文图片始终下载替换，正文第一张图作备用缩略图
                $bodyFirst = '';
                if ($imgSwitch) {
                    $body = self::saveImages($body, $ruleLang, $bodyFirst);
                }
                // 缩略图优先级：列表页缩略图优先，正文第一张图兜底
                $thumb = '';
                if (!empty($link['thumb'])) {
                    $thumb = $imgSwitch ? self::downloadImage($link['thumb']) : self::absUrl($link['thumb'], $link['thumb']);
                }
                if ($thumb === '') $thumb = $bodyFirst;
                $desc = mb_substr(strip_tags($body), 0, max(0, (int)$rule['desc_len']), 'utf-8');

                $now = time();
                $data = [
                    'title'        => $title,
                    'description'  => $desc,
                    'content'      => $body,
                    'catid'        => (int)$rule['catid'],
                    'model_id'     => $modelId,
                    'thumb'        => $thumb,
                    'views'        => 0,
                    'status'       => 1,
                    'publish_time' => 0,
                    'create_time'  => $now,
                    'update_time'  => $now,
                ];
                Db::table($contentTable)->insert($data);
                $stat['added']++;
            } catch (\Throwable $e) {
                $stat['failed']++;
            }
        }

        $rule->save([
            'last_run_time' => time(),
            'last_success'  => $stat['added'],
        ]);
        CollectLog::create([
            'rule_id'    => $ruleId,
            'rule_name'  => $rule['name'],
            'lang'       => $ruleLang,
            'catid'      => (int)$rule['catid'],
            'total'      => $stat['total'],
            'added'      => $stat['added'],
            'skipped'    => $stat['skipped'],
            'failed'     => $stat['failed'],
            'msg'        => '完成',
            'create_time'=> time(),
        ]);
        return [
            'code' => 1,
            'msg'  => "采集完成：抓取 {$stat['total']} 条，新增 {$stat['added']}，跳过 {$stat['skipped']}，失败 {$stat['failed']}",
            'data' => $stat,
        ];
    }

    /* ---------- 抓取与解析 ---------- */

    protected static function fetch($url, $charset = 'UTF-8')
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) return '';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => ["Accept-Language: zh-CN,zh;q=0.9"],
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($html === false || $code != 200) return '';
        // 编码转换
        $cs = strtoupper(trim($charset));
        if ($cs && !in_array($cs, ['UTF-8', 'UTF8'], true)) {
            $converted = @mb_convert_encoding($html, 'UTF-8', $cs);
            if ($converted) $html = $converted;
        }
        return $html;
    }

    /** 起止标记截取（未配置起止标记则返回全文） */
    protected static function slice($html, $start, $end)
    {
        $html = (string)$html;
        $s = trim((string)$start);
        $e = trim((string)$end);
        if ($s !== '') {
            $p = mb_strpos($html, $s);
            if ($p === false) return '';
            $html = mb_substr($html, $p + mb_strlen($s));
        }
        if ($e !== '') {
            $p = mb_strpos($html, $e);
            if ($p !== false) {
                $html = mb_substr($html, 0, $p);
            }
        }
        return $html;
    }

    /**
     * 从片段中提取全部详情链接（正则捕获组1为URL；相对地址补全）
     * 返回 [ ['url'=>..., 'thumb'=>...], ... ]：thumb 为列表页该条目内第一张图（原 URL，可能为空）
     */
    protected static function extractLinks($zone, $preg, $baseUrl)
    {
        if (trim($preg) === '') {
            $preg = '#<a[^>]+href=["\']([^"\']+)["\'][^>]*>#is';
        }
        $out = [];
        if (preg_match_all($preg, $zone, $m)) {
            $picked = isset($m[1]) ? $m[1] : $m[0];
            foreach ($picked as $u) {
                $u = trim($u);
                if ($u === '') continue;
                $out[] = ['url' => self::absUrl($u, $baseUrl), 'thumb' => ''];
            }
        }
        // 按 <a href=...>...</a> 块精确配对缩略图：块内第一个 <img src> 归属该 href
        if ($out && preg_match_all('#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', $zone, $blocks)) {
            $map = [];
            foreach ($blocks[1] as $i => $href) {
                if (isset($blocks[2][$i]) && preg_match('#<img[^>]+src=["\']([^"\']+)["\']#is', $blocks[2][$i], $im)) {
                    $map[self::absUrl(trim($href), $baseUrl)] = self::absUrl(trim($im[1]), $baseUrl);
                }
            }
            foreach ($out as &$l) {
                if ($l['thumb'] === '' && isset($map[$l['url']])) {
                    $l['thumb'] = $map[$l['url']];
                }
            }
            unset($l);
        }
        return $out;
    }

    /** 相对地址补全 */
    protected static function absUrl($url, $base)
    {
        if (preg_match('#^https?://#i', $url)) return $url;
        $parts = parse_url($base);
        $scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
        $host   = isset($parts['host']) ? $parts['host'] : '';
        if ($url === '' || strpos($url, '#') === 0) return '';
        if ($url[0] === '/') return $scheme . '://' . $host . $url;
        $path = isset($parts['path']) ? $parts['path'] : '/';
        $dir  = preg_replace('#/[^/]*$#', '/', $path);
        return $scheme . '://' . $host . $dir . $url;
    }

    /** 正文图片下载到本地，替换 src，返回缩略图（第一张） */
    protected static function saveImages($body, $lang, &$thumb)
    {
        if (!preg_match_all('#<img[^>]+src=["\']([^"\']+)["\']#is', $body, $m)) {
            return $body;
        }
        foreach ($m[1] as $i => $src) {
            $src = trim($src);
            $local = self::downloadImage($src);
            if ($local === '') continue;
            $body = str_replace($src, $local, $body);
            if ($i === 0) $thumb = $local;
        }
        return $body;
    }

    /** 下载单张图片到本地 upload/image/，返回本地 URL（失败返回空串） */
    protected static function downloadImage($url)
    {
        $url = trim($url);
        if ($url === '' || stripos($url, 'data:') === 0) return '';
        $full = self::absUrl($url, $url); // 已是完整地址则原样
        $bin = @file_get_contents($full);
        if ($bin === false) return '';
        $ext = strtolower(pathinfo(parse_url($full, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) $ext = 'jpg';
        $sub = date('Ym') . '/';
        $dir = root_path() . 'public' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $name = date('YmdHis') . mt_rand(1000, 9999) . '.' . $ext;
        if (@file_put_contents($dir . $name, $bin) === false) return '';
        return '/upload/image/' . $sub . $name;
    }
}
