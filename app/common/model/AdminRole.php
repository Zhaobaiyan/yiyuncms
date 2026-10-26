<?php
// +----------------------------------------------------------------------
// | 后台管理员角色模型
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class AdminRole extends Model
{
    // 表名（自动加前缀）
    protected $name = 'admin_role';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';

    /**
     * 解析角色权限规则（返回控制器白名单数组，'*' 表示全部）
     */
    public static function parseRules($rules)
    {
        $rules = (string)$rules;
        if ($rules === '' || $rules === '[]') return [];
        if ($rules === '*') return ['*'];
        $arr = json_decode($rules, true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * 当前后台登录角色是否需要审核（need_audit=1：发布内容默认草稿，需管理员/审核角色改为发布）
     */
    public static function needAudit(): bool
    {
        // 全局内容审核开关：关闭时所有角色免审核（发布即上架，草稿用于下架）
        if ((int)get_config('content.audit', 1) !== 1) return false;
        $code = (string)session('admin_role');
        if ($code === '' || $code === 'super') return false;
        $role = self::where('code', $code)->find();
        return $role ? ((int)$role['need_audit'] === 1) : false;
    }
}
