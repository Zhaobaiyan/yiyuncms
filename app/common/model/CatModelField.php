<?php
// +----------------------------------------------------------------------
// | 栏目模型字段模型
// | 全局表 yy_cat_model_field，不分语言
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class CatModelField extends Model
{
    protected $name = 'cat_model_field';
    protected $autoWriteTimestamp = 'int';

    /** 字段类型（与栏目自定义字段控件一致） */
    const TYPES = ['text' => '单行文本', 'image' => '图片', 'textarea' => '多行文本', 'richtext' => '富文本',
                   'select' => '下拉选择', 'radio' => '单选', 'checkbox' => '多选'];

    /**
     * 获取某栏目模型的全部字段（按 sort 排序）
     */
    public static function fieldsOf(int $modelId): array
    {
        return self::where('model_id', $modelId)->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
    }
}
