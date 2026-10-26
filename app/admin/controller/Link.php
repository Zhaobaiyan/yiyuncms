<?php
// +----------------------------------------------------------------------
// | 友情链接控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Link as LinkModel;

class Link extends AdminBase
{
    public function index()
    {
        $list = LinkModel::order('sort', 'asc')->select();
        return view('link/index', ['list' => $list]);
    }

    public function edit($id = 0)
    {
        if (request()->isPost()) {
            $data = input('post.');
            if ($id) {
                LinkModel::where('id', $id)->update($data);
            } else {
                LinkModel::create($data);
            }
            AdminLog::record('link_' . ($id ? 'edit' : 'add'), '友情链接：' . $data['name']);
            return $this->ok('保存成功', (string)url('admin/link/index'));
        }
        $info = $id ? LinkModel::find($id) : null;
        return view('link/edit', ['info' => $info]);
    }

    public function delete($id = 0)
    {
        $link = LinkModel::find($id);
        if ($link) {
            $link->delete();
            AdminLog::record('link_delete', '删除友情链接：' . $link['name']);
        }
        return $this->ok('删除成功', (string)url('admin/link/index'));
    }
}
