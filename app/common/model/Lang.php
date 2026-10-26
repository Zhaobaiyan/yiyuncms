<?php
// +----------------------------------------------------------------------
// | 语言模型（共享表 yy_lang，非语言相关）
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class Lang extends Model
{
    protected $name = 'lang';
    protected $autoWriteTimestamp = 'int';
}
