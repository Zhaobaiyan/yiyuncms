<?php
// +----------------------------------------------------------------------
// | 栏目模型（栏目字段模板）模型
// | 全局表 yy_cat_model，不分语言：定义一组栏目自定义字段，
// | 栏目选择该模型后自动继承模板字段，免去逐栏目重复添加
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class CatModel extends Model
{
    protected $name = 'cat_model';
    protected $autoWriteTimestamp = 'int';

    /**
     * 使用该模型的栏目数（cn + en 合计）
     */
    public function usedCount()
    {
        $cn = \think\facade\Db::name('cn_category')->where('cat_model_id', $this->id)->count();
        $en = \think\facade\Db::name('en_category')->where('cat_model_id', $this->id)->count();
        return $cn + $en;
    }
}
