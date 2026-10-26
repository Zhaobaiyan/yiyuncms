<?php
// +----------------------------------------------------------------------
// | 广告位控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\Ad as AdModel;
use app\common\model\AdminLog;
use think\facade\Cache;

class Ad extends AdminBase
{
    public function index()
    {
        // 广告位二级：分类（parent_id=0）→ 该分类下的轮播图项
        $parentId = (int)input('get.parent_id', 0);
        $list = AdModel::where('parent_id', $parentId)->order('sort', 'asc')->order('id', 'asc')->select();
        $parent = null;
        if ($parentId > 0) {
            $parent = AdModel::find($parentId);
            if (!$parent) {
                // 分类不存在：返回提示（TP6 无 error() 方法，避免 500 错误页）
                return $this->fail('广告位分类不存在或已被删除');
            }
            foreach ($list as &$r) {
                $r['call_code'] = '&#123;cms:ad name="' . $r['name'] . '"&#125;';
            }
        } else {
            // 分类视图：统计各分类下的轮播图项数
            $counts = AdModel::where('parent_id', '>', 0)->column('parent_id');
            $countMap = array_count_values($counts);
            foreach ($list as &$r) { $r['child_count'] = $countMap[$r['id']] ?? 0; }
        }
        return view('ad/index', ['list' => $list, 'parentId' => $parentId, 'parent' => $parent]);
    }

    public function edit($id = 0)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['image'] = (string)input('post.image', '');
            $data['url']   = (string)input('post.url', '');
            $data['sort']  = (int)input('post.sort', 100);
            // 标识留空自动生成唯一标识（分类用 a，子项用 ad）
            if (empty($data['name'])) {
                $data['name'] = (($data['parent_id'] ?? 0) ? 'ad' : 'a') . substr(md5(uniqid(mt_rand(), true)), 0, 10);
            }
            if ($id) {
                AdModel::where('id', $id)->update($data);
            } else {
                AdModel::create($data);
            }
            Cache::delete('ad_' . $data['name']);
            AdminLog::record('ad_' . ($id ? 'edit' : 'add'), '轮播图：' . ($data['title'] ?: $data['name']));
            return $this->ok('保存成功', (string)url('admin/ad/index'));
        }
        $info = $id ? AdModel::find($id) : null;
        // 分类列表（供下拉选择）
        $parents = AdModel::where('parent_id', 0)->order('sort', 'asc')->select();
        $presetParent = (int)input('get.parent_id', 0);
        return view('ad/edit', ['info' => $info, 'parents' => $parents, 'presetParent' => $presetParent]);
    }

    public function delete($id = 0)
    {
        $ad = AdModel::find($id);
        if ($ad) {
            // 若删除分类，其下轮播图项升为顶级，避免丢失
            AdModel::where('parent_id', $ad['id'])->update(['parent_id' => 0]);
            Cache::delete('ad_' . $ad['name']);
            $ad->delete();
            AdminLog::record('ad_delete', '删除广告位：' . $ad['title']);
        }
        return $this->ok('删除成功', (string)url('admin/ad/index'));
    }
}
