<?php
// +----------------------------------------------------------------------
// | 前台语言 + 设备路由中间件
// | 1) 从 URL 首段解析语言（/en/... -> en，否则默认语言）
// | 2) 站群路径模式：URL 首段命中站点别名（/shenyang/...）→ 注入站点并切换其默认语言（v3.18）
// | 3) 站群模式：URL 语言段不在站点白名单时回落站点默认语言（v3.13）
// +----------------------------------------------------------------------
namespace app\common\middleware;

use think\facade\Cookie;

class LangRouter
{
    public function handle($request, \Closure $next)
    {
        // 后台路径交给 AdminAuth，本中间件只处理前台
        $adminPath = 'admin';
        try {
            $adminPath = get_config('base.admin_path', 'admin');
        } catch (\Throwable $e) {
        }
        $path = trim($request->pathinfo(), '/');

        if ($path === $adminPath || strpos($path, $adminPath . '/') === 0) {
            return $next($request);
        }

        // 站群路径模式：首段命中已开启站点的路径别名 → 注入站点（site_match 内部做 path 解析）
        $seg = strtolower(strtok($path, '/'));
        if ((int)get_config('site.multi', 0) === 1 && $seg !== '') {
            $site = site_match();
            if ($site && (string)($site['alias'] ?? '') !== '' && $seg === strtolower(trim((string)$site['alias']))) {
                $request->withMiddleware(['_cms_site' => $site]);
            }
        }

        // 解析语言：URL 首段若是已启用语言（站群模式下为站点白名单内），则注入
        if ($seg && in_array($seg, lang_codes())) {
            lang_set($seg);
        } else {
            lang_set(default_lang());
        }

        return $next($request);
    }
}
