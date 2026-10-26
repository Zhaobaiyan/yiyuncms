<?php
// +----------------------------------------------------------------------
// | 自定义块模型
// +----------------------------------------------------------------------
namespace app\common\model;

class Block extends LangModel
{
    protected $name = 'block';
    protected $autoWriteTimestamp = 'int';

    /**
     * 按标识获取块（前台标签用，带缓存，缓存按语言隔离）
     */
    public static function getByName($name)
    {
        $key = 'block_' . lang_code() . '_' . $name;
        $row = \think\facade\Cache::get($key);
        if (!$row) {
            $row = static::where('name', $name)->where('status', 1)->find();
            if ($row) {
                \think\facade\Cache::set($key, $row->toArray(), 600);
            }
        }
        return $row;
    }
}
