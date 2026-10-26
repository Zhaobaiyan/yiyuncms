<?php
// +----------------------------------------------------------------------
// | 静态页面生成服务（多语言）
// | 按语言生成前台静态 HTML：cn 写入 public/ 根，en 写入 public/en/
// | 生成前注入目标语言，保证模板标签与 URL 均按该语言渲染
// +----------------------------------------------------------------------
namespace app\common\service;

use app\common\model\Category;
use app\common\model\Content;
use think\facade\View;

class StaticPage
{
    /**
     * 前台模板目录（PC）——使用该语言的模板文件夹（tpl_dir）
     */
    public static function tplDir($lang = null)
    {
        $lang = $lang ?: lang_code();
        return root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir($lang) . DIRECTORY_SEPARATOR;
    }

    /**
     * 静态文件根目录（cn 在 public/，其他语言在 public/{lang}/）
     * v3.18 站群路径站点：无论其是否为全局默认语言，静态目录一律 public/{别名}/
     * （访问入口即 /别名/，站内 URL 前缀固定）
     */
    public static function pubRoot($lang = null, $type = 'home')
    {
        $lang = $lang ?: lang_code();
        $site = site_match();
        $isSite = $site && (string)($site['mode'] ?? 'domain') === 'path' && (string)$site['alias'] === $lang;
        if ($isSite) {
            return root_path() . 'public' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR;
        }
        if ($lang === default_lang()) {
            // 全局主语言：首页 index.html 在站点根目录；栏目/内容静态页放主语言文件夹（如 cn/），URL 由伪静态隐藏 {lang} 前缀
            $sub = ($type === 'home') ? '' : $lang . DIRECTORY_SEPARATOR;
        } else {
            // 其他语言：首页与栏目/内容均放 {lang}/ 文件夹，URL 带 {lang} 前缀
            $sub = $lang . DIRECTORY_SEPARATOR;
        }
        return root_path() . 'public' . DIRECTORY_SEPARATOR . $sub;
    }

    /**
     * 生成首页
     * 全局设置 base.splash=1（引导页开启）时：
     *   index.html = 引导页（模板 index_index.html）
     *   home.html  = 正常首页（模板 index.html）
     * base.splash=0（默认）：index.html = 正常首页（优先 index_index.html，否则 index）
     */
    public static function home($lang = null)
    {
        $lang = $lang ?: lang_code();
        $prev = lang_code();
        lang_set($lang);
        try {
            $splash = (get_config('base.splash') == 1);
            if ($splash) {
                // 引导页（index_index.html）
                $splashTpl = is_file(self::tplDir($lang) . 'index_index.html') ? 'index_index' : 'index';
                $html      = self::render($splashTpl, ['cur_catid' => 0], $lang);
                self::write(self::pubRoot($lang) . 'index.html', $html);
                // 正常首页（index.html）
                $homeTpl = is_file(self::tplDir($lang) . 'index.html') ? 'index' : 'index_index';
                $html2   = self::render($homeTpl, ['cur_catid' => 0], $lang);
                return self::write(self::pubRoot($lang) . 'home.html', $html2);
            }
            // 优先云优完整模板 index_index.html（自带 head/css），否则默认 index
            $tpl = is_file(self::tplDir($lang) . 'index_index.html') ? 'index_index' : 'index';
            $html = self::render($tpl, ['cur_catid' => 0], $lang);
        } finally {
            lang_set($prev);
        }
        return self::write(self::pubRoot($lang) . 'index.html', $html);
    }

    /**
     * 生成栏目页（列表/单页）
     */
    public static function category($catid, $lang = null)
    {
        $lang = $lang ?: lang_code();
        $prev = lang_code();
        lang_set($lang);
        try {
            $cat = Category::find($catid);
            if (!$cat) return false;
            // 跳转/外链栏目不产生真实页面，跳过静态生成
            if (in_array($cat['type'] ?? '', ['redirect', 'external'], true)) return false;

            $pageSize = content_pagesize();
            if ($cat['type'] === 'single') {
                // 单页展示模板：优先栏目 content_template（show_xxx），否则默认 show
                $tpl = $cat['content_template'] ?: 'show';
                $html = self::render($tpl, [
                    'category'  => $cat,
                    'content'   => [
                        'content'       => attach_download_rewrite($cat['content']),
                        'title'         => $cat['name'],
                        'author'        => '',
                        'source'        => '',
                        'flag'          => '',
                        'create_time'  => $cat['create_time'] ?: time(),
                        'views'         => 0,
                        'keywords'      => $cat['keywords'] ?? ($cat['seo_title'] ?? ''),
                        'desc'          => attach_download_rewrite($cat['description'] ?? ''),
                        'description'   => attach_download_rewrite($cat['description'] ?? ''),
                        'thumb'         => '',
                        'id'            => 0,
                        'catid'         => $cat['id'],
                        'author_name'   => '',
                        'audit_name'    => '',
                        'author_uid'    => 0,
                        'audit_uid'     => 0,
                        'audit_time'    => 0,
                        'audit_status'  => 0,
                        'submit_count'  => 0,
                        'reject_reason' => '',
                    ],
                    'is_single' => true,
                    'cur_catid' => $cat['id'],
                    'cid'       => $cat['id'],
                ], $lang);
                $dir  = category_path($cat);
                $rel  = self::write(self::pubRoot($lang, 'page') . $dir . '.html', $html);
                // 兼容无后缀访问：同步生成 <ename>/index.html 目录式文件
                self::write(self::pubRoot($lang, 'page') . $dir . DIRECTORY_SEPARATOR . 'index.html', $html);
                $cat->save(['static_path' => self::relPath($lang, $dir . '.html')]);
                return $rel;
            }

            // 列表分页：按栏目 pagesize 计算总页数，逐页生成 index.html + page/N.html
            $ids = Category::getChildIds($cat['id']);
            $total = Content::published()->whereIn('catid', $ids)->count();
            // 模板内联 pagesize 优先（云优模板 {yunu:list pagesize="N"}，模板写了则以模板为准）
            $tplFile = root_path() . 'template' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . ($cat['template'] ?: 'list') . '.html';
            if (is_file($tplFile)) {
                $tplSrc = file_get_contents($tplFile);
                if (preg_match('/\{yunu:list[^}]*pagesize="(\d+)"/', $tplSrc, $pm)) {
                    $pageSize = max(1, (int)$pm[1]);
                }
            }
            $pages = max(1, (int)ceil($total / max(1, $pageSize)));
            $dir  = category_path($cat);
            $path = self::pubRoot($lang, 'page') . $dir . DIRECTORY_SEPARATOR;
            if (!is_dir($path)) @mkdir($path, 0755, true);
            // 静态分页 URL：主语言无前缀（/pro/pro1/page/2.html），其他语言带前缀（/en/pro/pro1/page/2.html）
            $urlPath = '/' . ($lang === default_lang() ? '' : $lang . '/') . $dir . '/page/[PAGE].html';
            if (!defined('YY_STATIC_BUILD')) define('YY_STATIC_BUILD', true);
            $GLOBALS['yy_static_path'] = $urlPath;
            // 与前台控制器一致：优先栏目 template（list_xxx，云优模板自带 head），否则默认 list
            $tpl = $cat['template'] ?: 'list';
            $rel = '';
            for ($p = 1; $p <= $pages; $p++) {
                $GLOBALS['yy_static_page'] = $p;
                // 当前页数据（与 Lists 控制器一致：子栏目聚合 + 排序 + 分页，静态列表页内容完整）
                $rows = Content::published()->whereIn('catid', $ids)
                    ->order('is_top', 'desc')->order('sort', 'asc')->order('create_time', 'desc')
                    ->page($p, $pageSize)->select();
                // 列表项描述中的附件链接重写为下载端点
                foreach ($rows as &$it) {
                    if (!empty($it['description'])) {
                        $it['description'] = attach_download_rewrite($it['description']);
                    }
                }
                unset($it);
                // 商品价格映射（静态列表页显示价格，与 Lists 控制器一致）；商城开关关闭时不注入（v3.16）
                $shopMap = [];
                if ((int)get_config('shop.open', 1) === 1) {
                    try {
                        $rids = array_column($rows->toArray(), 'id');
                        if ($rids) {
                            $shops = \think\facade\Db::name('shop')->where('lang', $lang)->whereIn('conid', $rids)->select();
                            foreach ($shops as $s) {
                                $shopMap[(int)$s['conid']] = [
                                    'price'          => (float)$s['price'],
                                    'original_price' => (float)$s['original_price'],
                                    'stock'          => (int)$s['stock'],
                                    'sales'          => (int)$s['sales'],
                                    'status'         => (int)$s['status'],
                                ];
                            }
                        }
                    } catch (\Throwable $e) {
                        $shopMap = [];
                    }
                }
                $html = self::render($tpl, [
                    'category'    => $cat,
                    'list'        => $rows,
                    'page'        => $p,
                    'cur_catid'   => $cat['id'],
                    'cid'         => $cat['id'],
                    'more_offset' => count($rows),
                    'shop_map'    => $shopMap,
                ], $lang);
                $file = $p === 1 ? $path . 'index.html' : $path . 'page' . DIRECTORY_SEPARATOR . $p . '.html';
                $rel  = self::write($file, $html);
            }
            $cat->save(['static_path' => self::relPath($lang, $dir)]);
            return $rel;
        } finally {
            lang_set($prev);
        }
    }

    /**
     * 生成内容详情页
     */
    public static function content($id, $lang = null)
    {
        $lang = $lang ?: lang_code();
        $prev = lang_code();
        lang_set($lang);
        try {
            $content = Content::find($id);
            // 未发布/草稿/未到定时发布时间的详情页不生成静态
            if (!$content || $content['status'] != 1) return false;
            if ((int)$content['publish_time'] > 0 && (int)$content['publish_time'] > time()) return false;
            $cat = Category::find($content['catid']);
            $content['content']     = attach_download_rewrite($content['content']);
            $content['description'] = attach_download_rewrite($content['description']);
            // 商品价格信息（与 Show 控制器一致）：有 yy_shop 记录则注入 price/original_price/stock/sales；商城开关关闭时不注入（v3.16）
            $shop = null;
            if ((int)get_config('shop.open', 1) === 1) {
                try {
                    $shop = \think\facade\Db::name('shop')->where('lang', $lang)->where('conid', (int)$content['id'])->find();
                } catch (\Throwable $e) {
                    $shop = null;
                }
            }
            if ($shop) {
                $content['price']          = (float)$shop['price'];
                $content['original_price'] = (float)$shop['original_price'];
                $content['stock']          = (int)$shop['stock'];
                $content['sales']          = (int)$shop['sales'];
                $content['shop_status']    = (int)$shop['status'];
            } else {
                $content['price'] = $content['original_price'] = $content['stock'] = $content['sales'] = 0;
                $content['shop_status'] = 0;
            }
            // 云优模板兼容键（与 Show 控制器一致）：desc/click/pic/ftitle/piclist
            $content['desc']    = $content['description'] ?? '';
            $content['click']   = $content['views'] ?? 0;
            $content['pic']     = $content['thumb'] ?? '';
            $content['ftitle']  = $content['short_title'] ?? '';
            $_ext = json_decode((string)($content['extend'] ?? ''), true);
            $content['piclist'] = is_array($_ext) && !empty($_ext['piclist']) ? implode('***', (array)$_ext['piclist']) : '';
            // 云优模板兼容：上下条（{$content.nexturl/nexttitle/prevurl/prevtitle}，云优命名与直觉相反）
            $prevRow = Content::published()->where('catid', $content['catid'])->where('id', '<', $content['id'])->order('id', 'desc')->find();
            $nextRow = Content::published()->where('catid', $content['catid'])->where('id', '>', $content['id'])->order('id', 'asc')->find();
            $content['nexturl']   = $prevRow ? url_content($prevRow) : '';
            $content['nexttitle'] = $prevRow ? $prevRow['title'] : '';
            $content['prevurl']   = $nextRow ? url_content($nextRow) : '';
            $content['prevtitle'] = $nextRow ? $nextRow['title'] : '';
            // 发稿人/审核人（投稿审核机制，模板可调用 author_name / audit_name）
            $people = content_people($content['author_uid'] ?? 0, $content['audit_uid'] ?? 0);
            $content['author_name'] = $people['author_name'];
            $content['audit_name']  = $people['audit_name'];
            $content['audit_status']  = (int)($content['audit_status'] ?? 0);
            $content['submit_count']  = (int)($content['submit_count'] ?? 0);
            $content['reject_reason'] = (string)($content['reject_reason'] ?? '');
            // 详情模板：优先所属栏目 content_template（show_xxx，云优模板自带 head），否则默认 show
            $tpl = ($cat && $cat['content_template']) ? $cat['content_template'] : 'show';
            $html = self::render($tpl, [
                'category'  => $cat,
                'content'   => $content,
                'is_single' => false,
                'cur_catid' => $cat ? $cat['id'] : 0,
                'cid'       => $cat ? $cat['id'] : 0,
                'fav_status' => false,
            ], $lang);

            $dir  = $cat ? category_path($cat) : 'cat_' . $content['catid'];
            $path = self::pubRoot($lang, 'page') . $dir . DIRECTORY_SEPARATOR;
            if (!is_dir($path)) @mkdir($path, 0755, true);
            $rel = self::write($path . $content['id'] . '.html', $html);
            $content->save(['static_path' => self::relPath($lang, $dir . '/' . $content['id'] . '.html')]);
            return $rel;
        } finally {
            lang_set($prev);
        }
    }

    /**
     * 某栏目下全部内容页
     */
    public static function categoryContent($catid, $lang = null)
    {
        $lang  = $lang ?: lang_code();
        $prev  = lang_code();
        lang_set($lang);
        $ids = [];
        try {
            $ids = Content::published()->where('catid', $catid)->column('id');
        } finally {
            lang_set($prev);
        }
        foreach ($ids as $cid) {
            self::content($cid, $lang);
        }
        return count($ids);
    }

    /**
     * 全站静态化（当前语言或全部语言）
     */
    public static function all($lang = null)
    {
        $langs = $lang ? [$lang] : lang_codes();
        $count = 0;
        foreach ($langs as $l) {
            $count += self::buildLang($l);
        }
        // 全站生成后同步站点地图（public/sitemap.xml，多语言合并）与 robots.txt
        try {
            \app\common\service\SitemapService::writeFile();
            \write_robots();
        } catch (\Throwable $e) {
            // sitemap 生成失败不影响主流程
        }
        return $count;
    }

    /**
     * 生成某语言全部页面
     */
    public static function buildLang($lang)
    {
        // v3.18 站群：目标语言是路径站点别名时注入站点（模板按站点白名单/站点前缀渲染）
        try {
            if ((int)get_config('site.multi', 0) === 1) {
                $s = \think\facade\Db::name('site')->where('mode', 'path')->where('alias', $lang)->where('status', 1)->find();
                if ($s) {
                    request()->withMiddleware(['_cms_site' => $s]);
                }
            }
        } catch (\Throwable $e) {
        }
        $prev = lang_code();
        lang_set($lang);
        $cats = [];
        try {
            $cats = Category::where('is_show', 1)->column('id');
        } finally {
            lang_set($prev);
        }
        $count = 1; // 首页
        self::home($lang);
        foreach ($cats as $cid) {
            self::category($cid, $lang);
            $count += self::categoryContent($cid, $lang) + 1;
        }
        // 生成后同步伪静态（把主语言代码写入 nginx.htaccess / .htaccess）
        if ($lang === default_lang()) {
            sync_pretty_firewall($lang);
        }
        return $count;
    }

    /**
     * 渲染模板为字符串（指定语言模板目录，平铺模板统一用前导 / 解析）
     */
    protected static function render($tpl, $data = [], $lang = null)
    {
        // 注入当前栏目上下文，供 {cms:category top="1"} 等标签在静态生成时识别当前栏目
        \app\common\service\TagService::resetContext();
        $GLOBALS['yy_cur_catid'] = isset($data['cur_catid']) ? (int)$data['cur_catid'] : 0;
        $lang = $lang ?: lang_code();
        View::config(['view_path' => self::tplDir($lang)]);
        // 补齐前台模板依赖的全局变量（静态生成固定为 PC 版）
        View::assign('is_wap', false);
        View::assign('lang_code', $lang);
        View::assign('cur_url', '/');
        // 系统功能开关（v3.16）：静态页同样按开关隐藏功能入口
        View::assign('sys_open', [
            'member'   => (int)get_config('member.open', 1) === 1,
            'shop'     => (int)get_config('shop.open', 1) === 1,
            'service'  => (int)get_config('service.open', 1) === 1,
            'comment'  => (int)get_config('comment.open', 1) === 1,
            'favorite' => (int)get_config('favorite.open', 1) === 1,
        ]);
        if ($tpl !== '' && $tpl[0] !== '/') {
            $tpl = '/' . $tpl;
        }
        return View::fetch($tpl, $data);
    }

    /**
     * 写入文件并返回相对路径（去掉 public 前缀）
     */
    protected static function write($file, $html)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        file_put_contents($file, $html, LOCK_EX);
        return '/' . trim(str_replace(root_path() . 'public', '', str_replace('\\', '/', $file)), '/');
    }

    /**
     * 生成静态相对路径（默认语言不带前缀，其他语言带 {lang}/）
     * v3.18 站群路径站点：始终带 {别名}/ 前缀
     */
    protected static function relPath($lang, $path)
    {
        $site = site_match();
        $isSite = $site && (string)($site['mode'] ?? 'domain') === 'path' && (string)$site['alias'] === $lang;
        if ($isSite) {
            return $lang . '/' . $path;
        }
        return ($lang === default_lang() ? '' : $lang . '/') . $path;
    }

    /**
     * 内容更新时联动刷新
     */
    public static function refreshContent($id, $catid = 0)
    {
        if (get_config('base.static_mode') != 1) return true;
        $done = self::content($id) !== false;
        if ($catid) {
            $done = self::category($catid) !== false && $done;
        }
        return $done;
    }

    /**
     * 删除静态文件（下架/草稿时清理，防未登录直接访问）
     * @param string $relPath 数据库 static_path 值（含语言前缀，主语言无前缀）
     */
    public static function remove($relPath)
    {
        $relPath = (string)$relPath;
        if ($relPath === '') return true;
        // 防目录穿越
        $clean = str_replace(['..', '\\', '//'], '', $relPath);
        $path  = public_path() . $clean;
        if (is_file($path)) @unlink($path);
        return true;
    }
}
