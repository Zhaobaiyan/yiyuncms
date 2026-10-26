<?php
// +----------------------------------------------------------------------
// | 模型扩展字段模型（多语言表 yy_{lang}_model_field）
// +----------------------------------------------------------------------
namespace app\common\model;

class ModelField extends LangModel
{
    protected $name = 'model_field';
    protected $autoWriteTimestamp = 'int';

    /** 字段类型 */
    const TYPES = ['text' => '单行文本', 'textarea' => '多行文本', 'image' => '图片(单图)', 'gallery' => '图片集(多图)', 'rich' => '富文本',
                   'number' => '数字', 'select' => '下拉', 'radio' => '单选', 'checkbox' => '多选'];

    /**
     * options 解析为数组
     */
    public function getOptionsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 获取某模型的全部字段（按 sort 排序）
     */
    public static function fieldsOf(int $modelId): array
    {
        return self::where('model_id', $modelId)->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
    }
}
