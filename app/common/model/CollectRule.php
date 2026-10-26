<?php
// +----------------------------------------------------------------------
// | 采集规则模型（全局表 yy_collect_rule）
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class CollectRule extends Model
{
    protected $name = 'collect_rule';
    protected $autoWriteTimestamp = 'int';
}
