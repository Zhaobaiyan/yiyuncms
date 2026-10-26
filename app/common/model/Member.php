<?php
// +----------------------------------------------------------------------
// | 会员模型（基础版）
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class Member extends Model
{
    protected $name = 'member';
    protected $autoWriteTimestamp = 'int';
    protected $hidden = ['password'];

    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
}
