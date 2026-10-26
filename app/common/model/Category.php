<?php
// +----------------------------------------------------------------------
// | 栏目模型（多级树形）
// +----------------------------------------------------------------------
namespace app\common\model;

use think\model\concern\SoftDelete;

class Category extends LangModel
{
    use SoftDelete;

    protected $name = 'category';
    protected $autoWriteTimestamp = 'int';

    // 软删除（delete_time=0 为未删）
    protected $defaultSoftDelete = 0;
    protected $deleteTime = 'delete_time';

    /**
     * 生成栏目层级路径（用于面包屑）
     */
    public static function getParents($id, &$parents = [])
    {
        $cat = static::find($id);
        if ($cat && $cat['parent_id'] > 0) {
            static::getParents($cat['parent_id'], $parents);
        }
        if ($cat) {
            $parents[] = $cat;
        }
        return $parents;
    }

    /**
     * 获取某栏目的全部后代栏目ID（含自身）
     * 用于列表页聚合子栏目内容
     */
    public static function getChildIds($id)
    {
        $all = static::column('parent_id', 'id');
        $ids = [$id];
        // 递归收集子孙
        $loop = true;
        while ($loop) {
            $loop = false;
            foreach ($all as $cid => $pid) {
                if (in_array($pid, $ids) && !in_array($cid, $ids)) {
                    $ids[] = $cid;
                    $loop = true;
                }
            }
        }
        return $ids;
    }

    /**
     * 获取扁平列表（含 depth 层级，用于后台表格缩进）
     */
    public static function getFlatList($parentId = 0, $depth = 0, &$result = [])
    {
        $children = static::where('parent_id', $parentId)->order('sort', 'asc')->select();
        foreach ($children as $cat) {
            $arr = $cat->toArray();
            $arr['depth'] = $depth;
            $result[] = $arr;
            static::getFlatList($cat['id'], $depth + 1, $result);
        }
        return $result;
    }

    /**
     * 构建树形结构
     */
    public static function getTree($parentId = 0)
    {
        $cats = static::order('sort', 'asc')->select()->toArray();
        return self::buildTree($cats, $parentId);
    }

    protected static function buildTree(&$list, $parentId)
    {
        $tree = [];
        foreach ($list as &$item) {
            if ($item['parent_id'] == $parentId) {
                $children = self::buildTree($list, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
