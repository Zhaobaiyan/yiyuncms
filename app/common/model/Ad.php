<?php
// +----------------------------------------------------------------------
// | 广告位模型
// +----------------------------------------------------------------------
namespace app\common\model;

class Ad extends LangModel
{
    protected $name = 'ad';
    protected $autoWriteTimestamp = 'int';

    /**
     * 按标识获取广告位（缓存按语言隔离）
     */
    public static function getByName($name)
    {
        $key = 'ad_' . lang_code() . '_' . $name;
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
