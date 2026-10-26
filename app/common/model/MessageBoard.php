<?php
// +----------------------------------------------------------------------
// | 留言板模型（多语言表 yy_{lang}_message_board）
// | fields 存该留言板的动态字段定义 JSON，支持必填/非必填
// +----------------------------------------------------------------------
namespace app\common\model;

class MessageBoard extends LangModel
{
    protected $name = 'message_board';
    protected $autoWriteTimestamp = 'int';

    /**
     * fields 解析为数组（自动为缺 id 的历史字段补 id）
     */
    public function getFieldsAttr($value)
    {
        $arr = $value ? json_decode($value, true) : [];
        if (!is_array($arr)) return [];
        $max = 0;
        foreach ($arr as &$f) {
            if (!is_array($f)) continue;
            if (!isset($f['id'])) {
                $max++;
                $f['id'] = $max;
            } elseif ($f['id'] > $max) {
                $max = $f['id'];
            }
            if (!isset($f['options'])) $f['options'] = [];
            if (!isset($f['required'])) $f['required'] = 0;
        }
        return $arr;
    }

    /**
     * 获取启用中的留言板列表
     */
    public static function enabled(): array
    {
        return self::where('status', 1)->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
    }
}
