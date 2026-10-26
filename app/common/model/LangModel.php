<?php
// +----------------------------------------------------------------------
// | 语言化模型基类
// | 表名按当前语言前缀生成：如模型名 content -> 表 yy_cn_content / yy_en_content
// | 实现：各语言数据表独立，主键自增各自从 1 开始，互不干扰
// | 注：think-orm 基类的 initialize() 为 private 方法，子类无法覆盖。
// |     ORM4(TP8) 在构造函数 initializeData() 阶段即查询表结构（getFields），
// |     语言前缀必须在父构造完成前注入：通过覆盖 getOptions() 在 setOptions 阶段写入
// |     weakMap['name']；ORM3(TP6) 表名实时读属性，构造后赋值 $this->name 即可生效。
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

abstract class LangModel extends Model
{
    /**
     * ORM4(TP8)：父构造 setOptions 阶段注入语言化表名，initializeData 查表前就绪
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            'name' => lang_code() . '_' . $this->name,
        ]);
    }

    public function __construct(array | object $data = [])
    {
        parent::__construct($data);
        // ORM3(TP6) 兜底：构造后属性赋值（ORM3 表名实时读属性，晚赋值也生效）
        // ORM4(TP8) 已在 getOptions 注入语言前缀，这里避免重复拼接
        $raw = $this->getName();
        if (strpos($raw, lang_code() . '_') !== 0) {
            $this->name = lang_code() . '_' . $raw;
            if (method_exists($this, 'setOption')) {
                $this->setOption('name', $this->name);
            }
        }
    }
}