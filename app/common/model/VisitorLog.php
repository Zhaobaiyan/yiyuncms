<?php
// +----------------------------------------------------------------------
// | 前台访问统计模型：采集（UA/来源/关键词/设备/地区）+ 聚合排行
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class VisitorLog extends Model
{
    protected $name = 'visit_log';
    public $autoWriteTimestamp = false;

    /**
     * 记录一次访问（去重：同 IP 同页面 5 分钟内只记一次）
     * @param string $pageUrl 页面地址
     * @param string $referer 来源地址
     * @param string $lang    语言
     */
    public static function record($pageUrl = '', $referer = '', $lang = '')
    {
        try {
            $ip = (string)request()->ip();
            if ($ip === '') return;
            $pageUrl = mb_substr($pageUrl, 0, 255);
            $referer = mb_substr($referer, 0, 255);

            // 去重：同 IP + 同页面 5 分钟内
            $dup = self::where('ip', $ip)
                ->where('page_url', $pageUrl)
                ->where('create_time', '>', time() - 300)
                ->find();
            if ($dup) return;

            $ua = (string)request()->header('user-agent', '');
            $parsed = self::parseUa($ua);
            $src = self::parseReferer($referer);

            self::create([
                'page_url'    => $pageUrl,
                'referer'     => $referer,
                'keyword'     => $src['keyword'],
                'engine'      => $src['engine'],
                'area'        => self::resolveArea($ip),
                'browser'     => $parsed['browser'],
                'device'      => $parsed['device'],
                'os'          => $parsed['os'],
                'ip'          => $ip,
                'lang'        => mb_substr($lang, 0, 10),
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 采集失败不影响页面访问
        }
    }

    /**
     * 解析来源 referer：识别搜索引擎 + 提取搜索关键词
     */
    protected static function parseReferer($referer)
    {
        $out = ['engine' => '', 'keyword' => ''];
        if (!$referer) return $out;
        $host = strtolower((string)parse_url($referer, PHP_URL_HOST));
        if (!$host) return $out;

        $map = [
            'baidu.com'   => ['baidu', ['wd', 'word']],
            'google.'     => ['google', ['q']],
            'bing.com'    => ['bing', ['q']],
            'sogou.com'   => ['sogou', ['query', 'keyword']],
            'so.com'      => ['360', ['q']],
            '360.cn'      => ['360', ['q']],
            'sm.cn'       => ['神马', ['q']],
            'toutiao.com' => ['头条搜索', ['keyword']],
            'shenma.com'  => ['神马', ['q']],
            'yandex.'     => ['yandex', ['text']],
            'duckduckgo.' => ['duckduckgo', ['q']],
        ];
        foreach ($map as $key => [$engine, $params]) {
            if (strpos($host, $key) !== false) {
                $out['engine'] = $engine;
                parse_str((string)parse_url($referer, PHP_URL_QUERY), $qs);
                foreach ($params as $pk) {
                    if (!empty($qs[$pk])) {
                        $out['keyword'] = mb_substr(trim($qs[$pk]), 0, 100);
                        break;
                    }
                }
                break;
            }
        }
        return $out;
    }

    /**
     * 解析 UA：浏览器 / 设备 / 操作系统
     */
    protected static function parseUa($ua)
    {
        $out = ['browser' => '', 'device' => 'pc', 'os' => ''];
        if (!$ua) return $out;

        // 爬虫优先
        $spider = ['Baiduspider', 'Googlebot', 'Sogou web spider', '360Spider', 'bingbot', 'Bytespider', 'YisouSpider', 'Shenma', 'python-requests', 'curl', 'Go-http-client'];
        foreach ($spider as $s) {
            if (stripos($ua, $s) !== false) {
                $out['browser'] = '爬虫';
                $out['device']  = 'spider';
                break;
            }
        }

        if ($out['browser'] === '') {
            // 微信内置
            if (stripos($ua, 'MicroMessenger') !== false) {
                $out['browser'] = '微信';
            } elseif (stripos($ua, 'QQ/') !== false || stripos($ua, 'QQBrowser') !== false) {
                $out['browser'] = 'QQ浏览器';
            } elseif (stripos($ua, 'UCBrowser') !== false) {
                $out['browser'] = 'UC浏览器';
            } elseif (stripos($ua, 'Edg') !== false) {
                $out['browser'] = 'Edge';
            } elseif (stripos($ua, 'Chrome') !== false) {
                $out['browser'] = 'Chrome';
            } elseif (stripos($ua, 'Firefox') !== false) {
                $out['browser'] = 'Firefox';
            } elseif (stripos($ua, 'Safari') !== false) {
                $out['browser'] = 'Safari';
            } elseif (stripos($ua, 'Opera') !== false || stripos($ua, 'OPR') !== false) {
                $out['browser'] = 'Opera';
            } elseif (stripos($ua, 'MSIE') !== false || stripos($ua, 'Trident') !== false) {
                $out['browser'] = 'IE';
            } else {
                $out['browser'] = '其他';
            }
        }

        // 设备
        if ($out['device'] !== 'spider') {
            if (preg_match('/iPad|Tablet/i', $ua)) {
                $out['device'] = 'tablet';
            } elseif (preg_match('/Mobile|iPhone|Android|Phone/i', $ua)) {
                $out['device'] = 'mobile';
            } else {
                $out['device'] = 'pc';
            }
        }

        // 系统
        if (stripos($ua, 'Windows') !== false) $out['os'] = 'Windows';
        elseif (stripos($ua, 'Android') !== false) $out['os'] = 'Android';
        elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $out['os'] = 'iOS';
        elseif (stripos($ua, 'Mac OS') !== false) $out['os'] = 'macOS';
        elseif (stripos($ua, 'Linux') !== false) $out['os'] = 'Linux';
        else $out['os'] = '其他';

        return $out;
    }

    /**
     * 地区解析（可插拔）：
     * - 内置：内网/私有 IP 归为「本地/内网」，其他暂记「未知」
     * - 若检测到 public/static/ip2region/ip2region.xdb（官方 ip2region 数据文件），自动启用精确省市区解析
     */
    public static function resolveArea($ip)
    {
        // 内网 / 私有段
        $long = 0;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) return '未知';
            $long = (float)sprintf('%u', $long);
            if (
                ($long >= 167772160 && $long <= 184549375)   // 10.0.0.0/8
                || ($long >= 2886729728 && $long <= 2887778303) // 172.16.0.0/12
                || ($long >= 3232235520 && $long <= 3232301055) // 192.168.0.0/16
                || ($long >= 2130706432 && $long <= 2147483647) // 127.0.0.0/8
                || ($long >= 2851995648 && $long <= 2852061183) // 169.254.0.0/16
            ) {
                return '本地/内网';
            }
        } else {
            if ($ip === '::1') return '本地/内网';
            if (preg_match('/^f[cd]/i', $ip)) return '本地/内网';
            return '未知';
        }

        // 尝试 ip2region 精确解析（文件存在时启用）
        $xdb = root_path() . 'public' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'ip2region' . DIRECTORY_SEPARATOR . 'ip2region.xdb';
        if (is_file($xdb)) {
            try {
                $searcherFile = dirname($xdb) . DIRECTORY_SEPARATOR . 'XdbSearcher.php';
                if (is_file($searcherFile)) {
                    require_once $searcherFile;
                    $searcher = \XdbSearcher::newWithFileOnly($xdb);
                    $region = $searcher->search($ip);
                    if ($region && strpos($region, '0|0|0|0|') !== 0) {
                        // 格式：国家|区域|省份|城市|ISP，取 省份|城市
                        $parts = explode('|', $region);
                        $prov  = trim($parts[2] ?? '');
                        $city  = trim($parts[3] ?? '');
                        if ($prov === '0' && $city === '0') return '未知';
                        $area = $prov === '0' ? ($city === '0' ? '未知' : $city) : ($city === '0' ? $prov : $prov . $city);
                        return $area === '' ? '未知' : $area;
                    }
                }
            } catch (\Throwable $e) {
                // 解析失败继续降级
            }
        }
        return '未知';
    }

    /**
     * 日期范围计算：today/yesterday/week/month/year/all
     * 返回 [开始时间戳, 结束时间戳]（结束含当天末尾）
     */
    public static function range($key)
    {
        $now = time();
        $today = strtotime(date('Y-m-d'));
        switch ($key) {
            case 'today':
                return [$today, $today + 86400 - 1];
            case 'yesterday':
                return [$today - 86400, $today - 1];
            case 'week':
                $w = (int)date('N'); // 1-7
                return [$today - ($w - 1) * 86400, $now];
            case 'month':
                return [strtotime(date('Y-m-01')), $now];
            case 'year':
                return [strtotime(date('Y-01-01')), $now];
            default: // all
                return [0, $now];
        }
    }

    /**
     * 时间范围内某字段排行（如 area/engine/browser/device/keyword/os）
     */
    public static function rank($field, $from, $to, $limit = 10, $excludeEmpty = true)
    {
        $query = self::where('create_time', '>=', $from)->where('create_time', '<=', $to);
        if ($excludeEmpty) {
            $query->where($field, '<>', '');
            if ($field === 'keyword') $query->where('engine', '<>', '');
        }
        $rows = $query->field($field . ' as name, COUNT(*) as num')
            ->group($field)->order('num', 'desc')->limit($limit)->select();
        $out = [];
        $max = 0;
        foreach ($rows as $r) {
            if ($r['name'] === '') continue;
            $out[] = ['name' => (string)$r['name'], 'num' => (int)$r['num']];
            $max = max($max, (int)$r['num']);
        }
        // 计算占比
        foreach ($out as &$o) $o['pct'] = $max > 0 ? round($o['num'] / $max * 100) : 0;
        return $out;
    }

    /**
     * 时间范围内 PV 总数
     */
    public static function countPv($from, $to)
    {
        return (int)self::where('create_time', '>=', $from)->where('create_time', '<=', $to)->count();
    }

    /**
     * 最近访问明细
     */
    public static function recent($limit = 20)
    {
        return self::order('id', 'desc')->limit($limit)->select();
    }
}
