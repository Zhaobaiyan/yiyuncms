<?php
// +----------------------------------------------------------------------
// | 采集日志模型（全局表 yy_collect_log）
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class CollectLog extends Model
{
    protected $name = 'collect_log';
    protected $autoWriteTimestamp = false;
}
