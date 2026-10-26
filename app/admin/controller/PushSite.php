<?php
// +----------------------------------------------------------------------
// | 跨站/跨语言内容推送管理控制器（v3.20）
// | 分站 → 主站：主站管理员审核通过后发布到主站
// | 主站 → 分站：分站管理员采纳后发布到分站；也可拒绝
// | 说明：模型为 app\common\model\Push（表 yy_push）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Push;
use app\common\model\Lang;
use think\facade\Db;

class PushSite extends AdminBase
{
    /**
     * 当前后台会话对应的站点标识：当前语言=全局默认语言 → main，否则=语言代码（站点别名）
     */
    protected function mySite()
    {
        $lang = lang_code();
        try { if ($lang === default_lang()) return 'main'; } catch (\Throwable $e) {}
        return $lang;
    }

    /**
     * 可推送的目标站点列表（主站 + 启用路径站点 + 启用语言版本）
     */
    public function targetSites()
    {
        $sites = [['key' => 'main', 'name' => '主站']];
        if ((int)get_config('site.multi', 0) === 1) {
            $rows = Db::name('site')->where('status', 1)->order('sort', 'asc')->select();
            foreach ($rows as $r) {
                $sites[] = ['key' => (string)$r['alias'], 'name' => $r['name'] . '（' . $r['alias'] . '）'];
            }
        }
        // 语言版本（en/ru 等）也可作为推送目标（多语言站）
        foreach (Lang::where('is_open', 1)->order('sort', 'asc')->column('code') as $code) {
            $hit = false;
            foreach ($sites as $s) { if ($s['key'] === $code) { $hit = true; break; } }
            if (!$hit) $sites[] = ['key' => $code, 'name' => '语言站 ' . $code];
        }
        return $sites;
    }

    /**
     * 推送记录列表
     * GET: tab=in（待我处理）/ out（我发起的）/ all（全部）
     */
    public function index()
    {
        $tab = input('get.tab', 'in');
        $status = input('get.status', '');
        $keyword = trim(input('get.keyword', ''));
        $my = $this->mySite();

        $query = Db::name('push');
        if ($tab === 'in') {
            $query->where('to_site', $my);
            if ($status === '') $query->where('status', 0);
        } elseif ($tab === 'out') {
            $query->where('from_site', $my);
        }
        if ($status !== '' && $tab !== 'in') $query->where('status', (int)$status);
        if ($keyword !== '') $query->where('title', 'like', '%' . $keyword . '%');

        $list = $query->order('id', 'desc')->paginate(15)->appends(array_filter([
            'tab'     => $tab,
            'status'  => $status,
            'keyword' => $keyword,
        ], function ($v) { return $v !== '' && $v !== null; }));

        $names = ['main' => '主站'];
        foreach ($this->targetSites() as $s) $names[$s['key']] = $s['name'];

        // 来源/目标栏目名映射（v3.21：列表显示完整栏目信息，不只显示数字ID）
        $catNames = [];
        foreach ($list as $row) {
            if (!empty($row['from_lang']) && !empty($row['from_catid'])) $catNames[$row['from_lang']][(int)$row['from_catid']] = 1;
            if (!empty($row['to_lang']) && !empty($row['to_catid']))     $catNames[$row['to_lang']][(int)$row['to_catid']] = 1;
        }
        $catDict = [];
        foreach ($catNames as $lc => $ids) {
            try {
                $rows = Db::name($lc . '_category')->whereIn('id', array_keys($ids))->field('id,name')->select();
                foreach ($rows as $r) $catDict[$lc][(int)$r['id']] = (string)$r['name'];
            } catch (\Throwable $e) {}
        }

        $cnt = [
            'in'  => (int)Db::name('push')->where('to_site', $my)->where('status', 0)->count(),
            'out' => (int)Db::name('push')->where('from_site', $my)->where('status', 0)->count(),
        ];
        return view('push_site/index', [
            'list' => $list, 'tab' => $tab, 'status' => $status, 'keyword' => $keyword,
            'names' => $names, 'my_site' => $my, 'cnt' => $cnt, 'cat_names' => $catDict,
            'canAudit' => !\app\common\model\AdminRole::needAudit(),
        ]);
    }

    /**
     * 创建推送（POST）：ids、to_site、to_lang、to_catid
     * 供内容管理批量推送调用（Content::pushToSite 亦可直连）
     */
    public function create()
    {
        $ids    = input('post.ids', '');
        $toSite = trim((string)input('post.to_site', ''));
        $toLang = trim((string)input('post.to_lang', ''));
        $toCat  = (int)input('post.to_catid', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return $this->fail('请选择内容');
        if ($toSite === '' || $toSite === $this->mySite()) return $this->fail('请选择有效的目标站点');
        $langs = lang_codes();
        if (!in_array($toLang, $langs, true)) return $this->fail('目标语言不存在');
        // 目标栏目校验（目标语言表）
        $tcat = Db::name($toLang . '_category')->where('id', $toCat)->find();
        if (!$tcat) return $this->fail('请选择目标栏目');

        $fromLang = lang_code();
        $uid  = (int)session('admin_id');
        $name = (string)session('admin_nick');
        $my   = $this->mySite();

        $scope = $this->dataScopeCats();
        $count = Push::createPush($fromLang, $my, $toSite, $toLang, $toCat, $ids, $uid, $name, $scope);
        if (!$count) return $this->fail('所选内容均不在您的管理范围内');
        AdminLog::record('pushsite_create', "向 [{$toSite}] 推送 {$count} 条内容");
        $tip = $toSite === 'main' ? '已推送主站，等待主站管理员审核' : '已推送分站，等待分站管理员接收';
        return $this->ok('推送成功（' . $count . ' 条），' . $tip, (string)url('admin/push_site/index?tab=out'));
    }

    /**
     * 处理推送（目标站管理员）：POST id, act=pass|reject, to_catid（pass可选改栏目）, remark（reject必填）
     */
    public function audit()
    {
        $id = (int)input('post.id', 0);
        $act = trim((string)input('post.act', ''));
        $row = Db::name('push')->where('id', $id)->find();
        if (!$row) return $this->fail('推送记录不存在');
        if ((int)$row['status'] !== 0) return $this->fail('该推送已处理');
        if ($row['to_site'] !== $this->mySite()) return $this->fail('仅目标站点管理员可处理该推送');
        if (\app\common\model\AdminRole::needAudit()) return $this->fail('当前角色无处理权限');

        $uid  = (int)session('admin_id');
        $name = (string)session('admin_nick');
        $toLang = (string)$row['to_lang'];

        if ($act === 'pass') {
            $toCat = (int)input('post.to_catid', (int)$row['to_catid']);
            if ($toCat <= 0) return $this->fail('请选择目标栏目');
            if (!Db::name($toLang . '_category')->where('id', $toCat)->find()) return $this->fail('目标栏目不存在');
            $res = Push::copyContent((string)$row['from_lang'], (int)$row['from_content_id'], $toLang, $toCat, (int)$row['creator_uid']);
            if (isset($res['error'])) return $this->fail($res['error']);
            Db::name('push')->where('id', $id)->update([
                'status' => 1, 'to_catid' => $toCat, 'target_content_id' => $res['newId'],
                'audit_uid' => $uid, 'audit_name' => $name, 'audit_time' => time(),
            ]);
            if (get_config('base.static_mode') == 1) {
                try { \app\common\service\StaticPage::refreshContent($res['newId'], $toCat); } catch (\Throwable $e) {}
            }
            AdminLog::record('pushsite_pass', "采纳推送 #{$id}（{$row['from_site']}→{$toLang}）生成内容 #{$res['newId']}");
            $tip = $row['from_site'] === 'main' ? '已采纳并发布到本站' : '已审核通过并发布到主站';
            // v3.21：通过后直接跳转到目标内容编辑页，便于管理员预览校对后微调
            return $this->ok('处理成功：' . $tip, (string)url('admin/content/edit/id/' . $res['newId']));
        }

        if ($act === 'reject') {
            $remark = trim((string)input('post.remark', ''));
            if ($remark === '') return $this->fail('请填写拒绝原因');
            Db::name('push')->where('id', $id)->update([
                'status' => 2, 'audit_uid' => $uid, 'audit_name' => $name, 'audit_time' => time(), 'remark' => $remark,
            ]);
            AdminLog::record('pushsite_reject', "拒绝推送 #{$id}：{$remark}");
            return $this->ok('已拒绝（发起方可查看原因）', (string)url('admin/push_site/index'));
        }
        return $this->fail('无效操作');
    }

    /**
     * 撤回推送（发起方）：POST id
     */
    public function withdraw()
    {
        $id = (int)input('post.id', 0);
        $row = Db::name('push')->where('id', $id)->find();
        if (!$row) return $this->fail('推送记录不存在');
        if ((int)$row['status'] !== 0) return $this->fail('该推送已处理，无法撤回');
        if ($row['from_site'] !== $this->mySite()) return $this->fail('仅发起方可撤回');
        Db::name('push')->where('id', $id)->update(['status' => 3, 'audit_time' => time()]);
        AdminLog::record('pushsite_withdraw', "撤回推送 #{$id}");
        return $this->ok('已撤回', (string)url('admin/push_site/index?tab=out'));
    }

    /**
     * 目标站点栏目树（AJAX）：GET to_lang=语言代码 → 该语言栏目树 JSON（完整层级，顶级→二级→三级）
     */
    public function cats()
    {
        $toLang = trim((string)input('get.to_lang', ''));
        if (!in_array($toLang, lang_codes(), true)) return $this->fail('语言不存在');
        $cats = Db::name($toLang . '_category')
            ->where('is_show', 1)
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();
        // 按 parent_id 分组，递归展开完整栏目树（修复 v3.21 值拷贝导致只返回一级的问题）
        $byPid = [];
        foreach ($cats as $c) $byPid[(int)$c['parent_id']][] = $c;
        $out = [];
        $walk = function ($pid, $depth) use (&$walk, &$byPid, &$out) {
            if (empty($byPid[$pid])) return;
            foreach ($byPid[$pid] as $c) {
                $out[] = ['id' => (int)$c['id'], 'name' => (string)$c['name'], 'depth' => $depth];
                $walk((int)$c['id'], $depth + 1);
            }
        };
        $walk(0, 0);
        // 兜底：若存在父级缺失的“孤儿”栏目（parent_id 指向已删除栏目），也追加展示
        foreach ($byPid as $pid => $list) {
            if ($pid === 0) continue;
            $exists = false;
            foreach ($cats as $c) if ((int)$c['id'] === $pid) { $exists = true; break; }
            if (!$exists) {
                foreach ($list as $c) $out[] = ['id' => (int)$c['id'], 'name' => (string)$c['name'], 'depth' => 0];
            }
        }
        return json(['code' => 1, 'msg' => 'ok', 'data' => ['cats' => $out]]);
    }
}
