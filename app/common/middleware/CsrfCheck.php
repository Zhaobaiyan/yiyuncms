<?php
// +----------------------------------------------------------------------
// | 后台 CSRF 轻量防护（v3.26）
// | 规则：仅拦截后台路径（默认 admin）的 POST/PUT/DELETE 请求；携带
// |       Referer/Origin 时必须与本站同源，否则拒绝；无来源信息
// |       （API/爬虫/支付回调/隐私模式）放行，兼顾兼容性与基础 CSRF 防护。
// | 判断方式与 AdminAuth 一致：按 pathinfo 首段识别后台路径。
// +----------------------------------------------------------------------
namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

class CsrfCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $method = strtoupper($request->method());
        if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            return $next($request);
        }
        // 后台路径识别（数据库异常时兜底为 admin）
        try {
            $adminPath = get_config('base.admin_path', 'admin');
        } catch (\Throwable $e) {
            $adminPath = 'admin';
        }
        $path = strtolower(trim($request->pathinfo(), '/'));
        if ($path !== $adminPath && strpos($path, $adminPath . '/') !== 0) {
            return $next($request);
        }
        if (!$this->sameOrigin($request)) {
            return json(['code' => 0, 'msg' => '请求来源不合法（CSRF 校验失败），请刷新页面后重试']);
        }
        return $next($request);
    }

    /**
     * 同源判定：带 Referer/Origin 时比对 host，无来源信息视为可信放行
     */
    protected function sameOrigin(Request $request): bool
    {
        $ref = trim((string)$request->header('referer', ''));
        if ($ref !== '') {
            $host = parse_url($ref, PHP_URL_HOST);
        } else {
            $origin = trim((string)$request->header('origin', ''));
            if ($origin === '') return true;
            $host = parse_url($origin, PHP_URL_HOST);
        }
        if (!$host) return true;
        return strtolower((string)$host) === strtolower((string)$request->host());
    }
}
