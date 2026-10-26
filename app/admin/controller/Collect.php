<?php
// +----------------------------------------------------------------------
// | 采集器控制器（v3.10.2）
// | 规则管理 + 执行采集 + 采集日志
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Category;
use app\common\model\CollectLog;
use app\common\model\CollectRule;
use app\common\model\Lang;
use app\common\service\CollectService;

class Collect extends AdminBase
{
    /**
     * 规则列表
     */
    public function index()
    {
        $keyword = trim((string)input('get.keyword', ''));
        $query   = CollectRule::order('id', 'desc');
        if ($keyword !== '') {
            $query->where('name', 'like', '%' . $keyword . '%');
        }
        $rows = $query->paginate(15)->appends(array_filter(['keyword' => $keyword], 'strlen'));
        foreach ($rows as &$r) {
            $cat = $r['catid'] ? Category::find($r['catid']) : null;
            $r['_cat'] = $cat ? $cat['title'] : '（栏目已删）';
            $r['_last'] = $r['last_run_time'] ? date('Y-m-d H:i', (int)$r['last_run_time']) : '从未执行';
        }
        unset($r);
        return view('collect/index', [
            'rows'    => $rows,
            'keyword' => $keyword,
        ]);
    }

    /**
     * 添加/编辑规则
     */
    public function edit()
    {
        $id = (int)input('get.id', 0);
        $model = $id ? CollectRule::find($id) : null;
        if ($id && !$model) {
            return $this->fail('规则不存在');
        }
        // 视图用数组 + 默认值，模板 if 条件直接用 $row.xxx 无需 isset/三元
        $row = array_merge(['lang' => 'cn', 'catid' => 0, 'img_switch' => 0, 'status' => 1], $model ? $model->toArray() : []);
        if (request()->isPost()) {            $data = [
                'name'          => trim((string)input('post.name', '')),
                'lang'          => trim((string)input('post.lang', 'cn')),
                'catid'         => (int)input('post.catid', 0),
                'charset'       => trim((string)input('post.charset', 'UTF-8')),
                'list_url'      => trim((string)input('post.list_url', '')),
                'list_start'    => (string)input('post.list_start', ''),
                'list_end'      => (string)input('post.list_end', ''),
                'link_preg'     => (string)input('post.link_preg', ''),
                'title_start'   => (string)input('post.title_start', ''),
                'title_end'     => (string)input('post.title_end', ''),
                'content_start' => (string)input('post.content_start', ''),
                'content_end'   => (string)input('post.content_end', ''),
                'desc_len'      => max(0, (int)input('post.desc_len', 200)),
                'img_switch'    => (int)input('post.img_switch', 0),
                'filter_words'  => (string)input('post.filter_words', ''),
                'max_page'      => max(1, (int)input('post.max_page', 1)),
                'status'        => (int)input('post.status', 1),
            ];
            if ($data['name'] === '' || $data['list_url'] === '') {
                return $this->fail('规则名称与列表地址必填');
            }
            // 数据范围：采集目标栏目须在管理员可管理栏目内
            $scope = $this->dataScopeCats();
            if ($scope !== null && !in_array($data['catid'], $scope)) {
                return $this->fail('无权向该栏目采集');
            }
            if ($model) {
                $model->save($data);
                AdminLog::record('collect_edit', '修改采集规则：' . $data['name']);
            } else {
                $data['create_time'] = time();
                $data['update_time'] = time();
                CollectRule::create($data);
                AdminLog::record('collect_add', '新增采集规则：' . $data['name']);
            }
            return $this->ok('保存成功', (string)url('collect/index'));
        }
        return view('collect/edit', [
            'row'    => $row,
            'id'     => $id,
            'langs'  => Lang::where('is_open', 1)->order('sort', 'asc')->select()->toArray(),
            'cats'   => Category::order('sort', 'asc')->order('id', 'asc')->select()->toArray(),
        ]);
    }

    /**
     * 删除规则
     */
    public function delete()
    {
        $id = (int)input('get.id', 0);
        $row = CollectRule::find($id);
        if (!$row) return $this->fail('规则不存在');
        AdminLog::record('collect_delete', '删除采集规则：' . $row['name']);
        $row->delete();
        return $this->ok('已删除', (string)url('collect/index'));
    }

    /**
     * 执行采集（POST，JSON 返回进度）
     */
    public function run()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }
        $id = (int)input('post.id', 0);
        $rule = CollectRule::find($id);
        if (!$rule) return json(['code' => 0, 'msg' => '规则不存在']);
        // 数据范围
        $scope = $this->dataScopeCats();
        if ($scope !== null && !in_array((int)$rule['catid'], $scope)) {
            return json(['code' => 0, 'msg' => '无权执行该规则']);
        }
        set_time_limit(300);
        $result = CollectService::run($id);
        if ($result['code'] === 1) {
            AdminLog::record('collect_run', '执行采集：' . $rule['name'] . '（' . $result['msg'] . '）');
        }
        return json($result);
    }

    /**
     * 采集日志
     */
    public function log()
    {
        $ruleId = (int)input('get.rule_id', 0);
        $query  = CollectLog::order('id', 'desc');
        if ($ruleId > 0) $query->where('rule_id', $ruleId);
        $rows = $query->paginate(15)->appends(array_filter(['rule_id' => $ruleId], function ($v) { return $v > 0; }));
        foreach ($rows as &$r) {
            $r['_time'] = $r['create_time'] ? date('Y-m-d H:i', (int)$r['create_time']) : '';
        }
        unset($r);
        return view('collect/log', [
            'rows'   => $rows,
            'ruleId' => $ruleId,
            'rules'  => CollectRule::order('id', 'desc')->select()->toArray(),
        ]);
    }
}
