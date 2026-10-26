<?php
// +----------------------------------------------------------------------
// | 自定义块控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Block as BlockModel;
use think\facade\Cache;

class Block extends AdminBase
{
    /**
     * 列表
     */
    public function index()
    {
        $parentId = (int)input('get.parent_id', 0);
        $list = BlockModel::where('parent_id', $parentId)->order('sort', 'asc')->select();
        $parent = null;
        if ($parentId > 0) {
            $parent = BlockModel::find($parentId);
            foreach ($list as &$r) {
                $r['call_code'] = '{cms:block name="' . $r['name'] . '" /}';
            }
        }
        // 顶级板块视图：统计各板块下的子块数
        if ($parentId === 0) {
            $counts = BlockModel::where('parent_id', '>', 0)->column('parent_id');
            $countMap = array_count_values($counts);
            foreach ($list as &$r) { $r['child_count'] = $countMap[$r['id']] ?? 0; }
        }
        return view('block/index', ['list' => $list, 'parentId' => $parentId, 'parent' => $parent]);
    }

    /**
     * 添加/编辑
     */
    public function edit($id = 0)
    {
        if (request()->isPost()) {
            $data = input('post.');
            // 标识留空自动生成唯一标识（板块用 p，子块用 b）
            if (empty($data['name'])) {
                $data['name'] = (($data['parent_id'] ?? 0) ? 'b' : 'p') . substr(md5(uniqid(mt_rand(), true)), 0, 10);
            }
            if ($id) {
                BlockModel::where('id', $id)->update($data);
            } else {
                if (BlockModel::where('name', $data['name'])->find()) {
                    return $this->fail('标识已存在');
                }
                BlockModel::create($data);
            }
            Cache::delete('block_' . $data['name']);
            AdminLog::record('block_' . ($id ? 'edit' : 'add'), '自定义块：' . $data['title']);
            return $this->ok('保存成功', (string)url('admin/block/index'));
        }
        $info = $id ? BlockModel::find($id) : null;
        $parents = BlockModel::where('parent_id', 0)->order('sort', 'asc')->select();
        $presetParent = (int)input('get.parent_id', 0);
        return view('block/edit', ['info' => $info, 'parents' => $parents, 'presetParent' => $presetParent]);
    }

    /**
     * 删除
     */
    public function delete($id = 0)
    {
        $block = BlockModel::find($id);
        if ($block) {
            // 若删除板块，其下子块升为顶级板块，避免丢失
            BlockModel::where('parent_id', $block['id'])->update(['parent_id' => 0]);
            Cache::delete('block_' . $block['name']);
            $block->delete();
            AdminLog::record('block_delete', '删除自定义块：' . $block['title']);
        }
        return $this->ok('删除成功', (string)url('admin/block/index'));
    }
}
