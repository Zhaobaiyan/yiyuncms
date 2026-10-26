<?php
// +----------------------------------------------------------------------
// | 后台登录鉴权 + 角色权限拦截中间件（全局注册，仅对后台路径生效）
// +----------------------------------------------------------------------
namespace app\common\middleware;

use think\facade\Session;
use app\common\model\AdminRole;

class AdminAuth
{
    /**
     * 后台登录鉴权
     * 判断依据：请求路径首段是否等于后台路径（默认 admin）
     */
    public function handle($request, \Closure $next)
    {
        // 读取后台路径（数据库异常时兜底为 admin）
        try {
            $adminPath = get_config('base.admin_path', 'admin');
        } catch (\Throwable $e) {
            $adminPath = 'admin';
        }
        $path      = strtolower(trim($request->pathinfo(), '/'));

        // 非后台路径直接放行
        if ($path !== $adminPath && strpos($path, $adminPath . '/') !== 0) {
            return $next($request);
        }

        // 登录白名单
        $allow = [
            $adminPath . '/login/index',
            $adminPath . '/login/login',
            $adminPath . '/login/captcha',
        ];

        // 未登录且不在白名单 -> 跳转登录
        if (!Session::has('admin_id') && !in_array($path, $allow)) {
            if ($request->isAjax() || $request->isPost()) {
                return json(['code' => 401, 'msg' => '登录已失效，请重新登录']);
            }
            return redirect('/' . $adminPath . '/login/index');
        }

        // 已登录 -> 角色权限拦截（非超级管理员按角色规则校验控制器）
        if (Session::has('admin_id')) {
            $seg  = explode('/', $path);
            $controller = strtolower($seg[1] ?? '');
            // 基础放行控制器（控制台 / 登录 / 上传 / 富文本上传）
            $always = ['index', 'login', 'upload', 'ueditor'];
            if (!in_array($controller, $always) && $path !== $adminPath) {
                $role  = (string)Session::get('admin_role', 'admin');
                $rules = $this->roleRules($role);
                if (!in_array('*', $rules) && !in_array($controller, $rules)) {
                    if ($request->isAjax() || $request->isPost()) {
                        return json(['code' => 403, 'msg' => '无权限操作']);
                    }
                    return redirect('/' . $adminPath . '/index/index');
                }
            }
        }

        return $next($request);
    }

    /**
     * 角色允许的控制器白名单
     */
    protected function roleRules($role)
    {
        if ($role === 'super') return ['*'];
        $row = AdminRole::where('code', $role)->find();
        if (!$row) return [];
        $rules = (string)$row['rules'];
        if ($rules === '[]' || $rules === '') return [];
        if ($rules === '*') return ['*'];
        $arr = json_decode($rules, true);
        return is_array($arr) ? $arr : [];
    }
}
