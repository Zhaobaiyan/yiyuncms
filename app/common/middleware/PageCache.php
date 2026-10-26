<?php
// +----------------------------------------------------------------------
// | 前台静态页缓存中间件（动态模式可选使用）
// +----------------------------------------------------------------------
namespace app\common\middleware;

use think\facade\Cache;

class PageCache
{
    /**
     * 对前台 GET 请求做页面级缓存，减轻数据库压力
     * @param \think\Request $request
     * @param \Closure       $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // 仅缓存 GET 请求
        if ($request->isGet()) {
            $key = 'page_' . md5($request->url(true));
            if ($html = Cache::get($key)) {
                return response($html);
            }
        }

        $response = $next($request);

        if ($request->isGet() && $response instanceof \think\response\Html) {
            $key = 'page_' . md5($request->url(true));
            Cache::set($key, $response->getContent(), 60);
        }

        return $response;
    }
}
