<?php
// +----------------------------------------------------------------------
// | Sitemap 服务（v3.10）
// | 生成全站 XML 站点地图：多语言合并，主语言 URL 无前缀，其他语言带前缀
// | 供前台控制器 /sitemap 动态输出与静态构建写入 public/sitemap.xml 使用
// +----------------------------------------------------------------------
namespace app\common\service;

use app\common\model\Category;
use app\common\model\Content;

class SitemapService
{
    /**
     * 生成站点地图 XML 字符串（全部已启用语言合并，已发布内容）
     */
    public static function build(): string
    {
        $codes   = \lang_codes();
        $default = \default_lang();
        $siteUrl = rtrim((string)\get_setting('site.url', ''), '/');
        if ($siteUrl === '') $siteUrl = 'http://' . (string)(request()->host() ?: 'localhost');

        $items = [];
        foreach ($codes as $code) {
            $prev = \lang_code();
            \lang_set($code);
            try {
                $prefix = $code === $default ? '' : '/' . $code;
                // 首页
                $items[] = ['loc' => $prefix === '' ? '/' : $prefix . '/', 'lastmod' => date('Y-m-d')];
                // 栏目：显示中的 列表/单页 栏目（外链/跳转不收录）
                $cats = Category::where('is_show', 1)
                    ->whereIn('type', ['list', 'single'])
                    ->order('sort', 'asc')
                    ->select();
                foreach ($cats as $cat) {
                    $u = \url_cat($cat);
                    if (!$u || $u === '#' || $u === '/') continue;
                    $items[] = ['loc' => $u, 'lastmod' => date('Y-m-d', (int)$cat['update_time'] ?: time())];
                }
                // 内容：已发布（含已到定时发布时间）
                $rows = Content::published()
                    ->field('id,catid,title,update_time,static_path')
                    ->order('id', 'desc')
                    ->limit(50000)
                    ->select();
                foreach ($rows as $row) {
                    $u = \url_content($row);
                    if (!$u || $u === '#') continue;
                    $items[] = ['loc' => $u, 'lastmod' => date('Y-m-d', (int)$row['update_time'] ?: time())];
                }
            } finally {
                \lang_set($prev);
            }
        }

        // 去重 + 转义
        $seen = [];
        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($items as $it) {
            $loc = $siteUrl . (isset($it['loc'][0]) && $it['loc'][0] === '/' ? $it['loc'] : '/' . ltrim($it['loc'], '/'));
            if (isset($seen[$loc])) continue;
            $seen[$loc] = 1;
            $xml .= "  <url><loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc><lastmod>" . $it['lastmod'] . "</lastmod></url>\n";
        }
        $xml .= "</urlset>";
        return $xml;
    }

    /**
     * 写入 public/sitemap.xml（静态构建 / 后台生成全站静态时调用）
     * @return bool
     */
    public static function writeFile(): bool
    {
        try {
            $xml = static::build();
            $file = root_path() . 'public' . DIRECTORY_SEPARATOR . 'sitemap.xml';
            return file_put_contents($file, $xml, LOCK_EX) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
