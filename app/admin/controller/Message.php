<?php
// +----------------------------------------------------------------------
// | 留言管理控制器（按留言板分类管理，展示动态字段）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Message as MessageModel;
use app\common\model\MessageBoard;

class Message extends AdminBase
{
    /**
     * 留言列表（可按留言板/状态/关键词筛选）
     */
    public function index()
    {
        $status  = input('get.status', -1);
        $keyword = trim(input('get.keyword', ''));
        $boardId = (int)input('get.board_id', 0);

        $query = new MessageModel();
        $scope = $this->dataScopeBoards();
        if ($scope !== null) {
            if ($boardId && !in_array($boardId, $scope)) {
                return $this->fail('无权限查看该留言板');
            }
            if (!$boardId) {
                $query = $query->whereIn('board_id', $scope);
            }
        }
        if ($boardId) {
            $query = $query->where('board_id', $boardId);
        }
        if ($status >= 0) {
            $query = $query->where('status', (int)$status);
        }
        if ($keyword !== '') {
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('name|phone|email|title|content', 'like', '%' . $keyword . '%')
                  ->whereOr('extend', 'like', '%' . $keyword . '%');
            });
        }
        $list = $query->order('id', 'desc')->paginate(15)->appends(array_filter([
            'status'  => $status,
            'board_id'=> $boardId,
            'keyword' => $keyword,
        ], function ($v) { return $v !== '' && $v !== null && $v !== -1; }));

        $boards = MessageBoard::order('sort', 'asc')->order('id', 'asc')->select();
        if ($scope !== null) {
            $boards = $boards->filter(function ($b) use ($scope) { return in_array((int)$b['id'], $scope); });
        }
        return view('message/index', [
            'list'   => $list,
            'boards' => $boards,
            'filter' => ['status' => $status, 'keyword' => $keyword, 'board_id' => $boardId],
        ]);
    }

    /**
     * 留言详情 + 回复
     */
    public function reply($id = 0)
    {
        $info = MessageModel::find($id);
        if (!$info) return $this->fail('留言不存在');
        if (!$this->canManageBoard((int)$info['board_id'])) return $this->fail('无权限操作该留言');
        $board = MessageBoard::find($info['board_id']) ?: null;

        if (request()->isPost()) {
            $reply = input('post.reply', '');
            $info->save([
                'reply'      => $reply,
                'status'     => 1,
                'is_public'  => (int)input('post.is_public', 0),
                'reply_time' => time(),
                'is_read'    => 1,
            ]);
            AdminLog::record('message_reply', '回复留言：' . $info['name']);
            return $this->ok('回复成功', (string)url('admin/message/index'));
        }

        if ($info['is_read'] == 0) {
            $info->save(['is_read' => 1]);
        }
        return view('message/reply', ['info' => $info, 'board' => $board]);
    }

    /**
     * 审核：通过/拒绝
     */
    public function audit()
    {
        $id   = input('post.id', 0);
        $val  = intval(input('post.val', 1));
        $msg = MessageModel::find($id);
        if (!$msg) return $this->fail('留言不存在');
        if (!$this->canManageBoard((int)$msg['board_id'])) return $this->fail('无权限操作该留言');
        MessageModel::where('id', $id)->update(['status' => $val]);
        return $this->ok('操作成功');
    }

    /**
     * 切换字段（is_public 公开/不公开 等）
     */
    public function toggle()
    {
        $id  = (int)input('post.id', 0);
        $f   = trim((string)input('post.field', ''));
        $val = (int)input('post.val', 0);
        if (!in_array($f, ['is_public', 'status'])) return $this->fail('参数错误');
        $msg = MessageModel::find($id);
        if (!$msg) return $this->fail('留言不存在');
        if (!$this->canManageBoard((int)$msg['board_id'])) return $this->fail('无权限操作该留言');
        MessageModel::where('id', $id)->update([$f => $val]);
        AdminLog::record('message_toggle', '修改留言字段：' . $f . '=' . $val);
        return $this->ok('操作成功');
    }

    /**
     * 删除
     */
    public function delete($id = 0)
    {
        $ids = input('post.ids', $id ?: 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids = array_map('intval', $ids);
        if (!$ids) return $this->fail('请选择留言');
        $scope = $this->dataScopeBoards();
        $list = MessageModel::whereIn('id', $ids)->select();
        foreach ($list as $row) {
            if ($scope !== null && !in_array((int)$row['board_id'], $scope)) continue;
            $row->delete();
        }
        AdminLog::record('message_delete', '删除留言：' . count($ids) . ' 条');
        return $this->ok('删除成功', (string)url('admin/message/index'));
    }
}
