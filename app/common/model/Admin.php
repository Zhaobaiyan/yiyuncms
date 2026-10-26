<?php
// +----------------------------------------------------------------------
// | 后台管理员模型
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class Admin extends Model
{
    // 表名（自动加前缀）
    protected $name = 'admin';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';

    // 密码字段不参与查询输出
    protected $hidden = ['password'];

    /**
     * 密码加密（TP6 使用 PHP password_hash）
     */
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * 校验密码
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->getData('password'));
    }
}
