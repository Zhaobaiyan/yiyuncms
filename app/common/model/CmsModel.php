<?php
// +----------------------------------------------------------------------
// | 内容模型
// +----------------------------------------------------------------------
namespace app\common\model;

class CmsModel extends LangModel
{
    // 表名：yy_model
    protected $name = 'model';
    protected $autoWriteTimestamp = 'int';

    /**
     * 解析扩展字段定义 JSON -> 数组
     */
    public function getFieldsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
