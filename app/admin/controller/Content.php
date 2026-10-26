<?php
// +----------------------------------------------------------------------
// | 内容管理控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Admin;
use app\common\model\AdminRole;
use app\common\model\Category as CategoryModel;
use app\common\model\Content as ContentModel;
use app\common\model\CmsModel;
use app\common\model\ContentAuditLog;
use app\common\service\StaticPage;

class Content extends AdminBase
{
    /**
     * 内容列表（支持按栏目/关键词/模型筛选）
     */
    public function index()
    {
        $catid   = input('get.catid', 0);
        $keyword = trim(input('get.keyword', ''));
        $modelId = input('get.model_id', 0);
        $status  = input('get.status', '');

        $query = ContentModel::with(['category', 'cmsModel']);
        // 回收站模式：查看软删除内容（withTrashed + delete_time>0）
        $isTrash = $status === 'trash';
        if ($isTrash) {
            $query = ContentModel::withTrashed()->with(['category', 'cmsModel'])->where('delete_time', '>', 0);
        }
        if ($catid) {
            $query->whereIn('catid', CategoryModel::getChildIds($catid));
        }
        // 数据范围：受限角色只可见可管理栏目（含子栏目）的内容
        $scope = $this->dataScopeCats();
        if ($scope !== null) {
            $query->whereIn('catid', $scope);
        }
        if ($keyword !== '') {
            $query->where('title', 'like', '%' . $keyword . '%');
        }
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        if ($isTrash) {
            // 回收站模式不参与状态筛选
        } elseif ($status === 'audit1') {
            $query->where('audit_status', 1);
        } elseif ($status === 'audit2') {
            $query->where('audit_status', 2);
        } elseif ($status !== '') {
            $query->where('status', (int)$status);
        }

        $pageSize = (int)input('get.page_size', 15);
        $pageSize = in_array($pageSize, [10, 15, 20, 50, 100, 200, 500], true) ? $pageSize : 15;
        $list  = $query->order('id', 'desc')->paginate($pageSize)->appends(array_filter([
            'status'    => $status,
            'catid'     => $catid,
            'keyword'   => $keyword,
            'model_id'  => $modelId,
            'page_size' => $pageSize,
        ], function ($v) { return $v !== '' && $v !== null && $v !== 0; }));
        // 发稿人/审核人昵称字典（author_uid/audit_uid → 昵称）
        $people = [];
        $uids = [];
        foreach ($list as $it) {
            if (!empty($it['author_uid'])) $uids[(int)$it['author_uid']] = 1;
            if (!empty($it['audit_uid']))  $uids[(int)$it['audit_uid']] = 1;
        }
        if ($uids) {
            $rows = Admin::whereIn('id', array_keys($uids))->column('nickname', 'id');
            $people = array_map('strval', $rows);
        }
        $fd = $this->catFormData();

        return view('content/index', [
            'list'   => $list,
            'cats'   => $fd['parents'],
            'models' => $fd['models'],
            'people' => $people,
            'canAudit' => !AdminRole::needAudit(), 'auditOpen' => (int)get_config('content.audit', 1) === 1,
            'filter' => ['catid' => $catid, 'keyword' => $keyword, 'model_id' => $modelId, 'status' => $status],
            'is_trash' => $isTrash,
            'trash_count' => (int)ContentModel::withTrashed()->where('delete_time', '>', 0)->count(),
            'page_size' => $pageSize,
            'push_sites' => $this->pushSites(),
        ]);
    }

    /**
     * 添加内容
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = $this->collectPost();
            $data['status'] = input('post.status', 1);
            $data['create_time'] = (int)input('post.create_time_dt', (int)input('post.create_time', time()));
            $at = (int)input('post.add_time_dt', 0);
            if ($at > 0) $data['add_time'] = $at;
            if (!$this->canManageCat((int)($data['catid'] ?? 0))) return $this->fail('无权限在该栏目发布内容');
            // 投稿审核：发稿人=当前管理员；需审核角色发布默认草稿，免审核角色直接发布（发稿人即审核人）
            $content = $this->createWithAudit($data, true);
            if (get_config('base.static_mode') == 1 && $content['status'] == 1) {
                StaticPage::content($content['id']);
            }
            AdminLog::record('content_add', '发布内容：' . $data['title']);
            \app\common\Hook::listen('content_save', ['id' => (int)$content['id'], 'action' => 'add', 'lang' => lang_code(), 'catid' => (int)$data['catid'], 'title' => $data['title']]);
            $tip = $data['status'] == 1 ? '发布成功' : (AdminRole::needAudit() ? '已提交审核（待审核）' : '已保存为草稿');
            return $this->ok($tip, (string)url('admin/content/index'));
        }

        $fd = $this->catFormData();
        $selCat = input('get.catid', 0);
        return view('content/edit', ['info' => null, 'cats' => $fd['parents'], 'models' => $fd['models'], 'selCat' => $selCat, 'all_fields' => $this->allFields(), 'imageList' => [], 'needAudit' => AdminRole::needAudit(), 'auditOpen' => (int)get_config('content.audit', 1) === 1, 'commentCount' => 0]);
    }

    /**
     * 编辑内容
     */
    public function edit($id = 0)
    {
        $info = ContentModel::find($id);
        if (!$info) return $this->fail('内容不存在');
        if (!$this->canManageCat((int)$info['catid'])) return $this->fail('无权限操作该内容');

        if (request()->isPost()) {
            $data = $this->collectPost();
            $data['status'] = input('post.status', 1);
            $data['create_time'] = (int)input('post.create_time_dt', (int)input('post.create_time', time()));
            $at = (int)input('post.add_time_dt', 0);
            if ($at > 0) $data['add_time'] = $at;
            if (!$this->canManageCat((int)($data['catid'] ?? $info['catid']))) return $this->fail('无权限移动到该栏目');
            // 发稿人不变；状态按审核规则解析（0→1 由免审核角色操作时记录审核人）
            $oldAuditStatus = (int)$info['audit_status'];
            $oldStatus       = (int)$info['status'];
            $st = $this->resolveStatus($data['status'], $info);
            $data['status'] = $st['status'];
            if ($st['audit_uid']) $data['audit_uid'] = $st['audit_uid'];
            if ($st['audit_time']) $data['audit_time'] = $st['audit_time'];
            $data['audit_status'] = $st['audit_status'];
            $data['submit_count'] = $st['submit_count'];
            $info->save($data);
            // 版本历史：每次编辑保存快照
            try { \app\common\model\ContentLog::record((int)$info['id'], 'save', $data, '编辑'); } catch (\Throwable $e) {}
            // 审核日志：需审核角色保存 = 提交（记修改说明）；免审核角色把待审核/已驳回改为发布 = 审核通过；发布→草稿 = 记录变化
            if (AdminRole::needAudit()) {
                ContentAuditLog::record((int)$info['id'], 1, (int)session('admin_id'), trim((string)input('post.audit_note', '')));
            } elseif ($st['status'] == 1 && $oldAuditStatus != 0) {
                ContentAuditLog::record((int)$info['id'], 3, (int)session('admin_id'), '');
            } elseif ($oldStatus === 1 && $data['status'] === 0) {
                ContentAuditLog::record((int)$info['id'], 4, (int)session('admin_id'), '');
            }
            if (get_config('base.static_mode') == 1) {
                if ($info['status'] == 1) {
                    StaticPage::refreshContent($info['id'], $info['catid']);
                } else {
                    // 草稿/下架：删除静态详情页并刷新栏目列表页，防未登录直接访问
                    StaticPage::remove((string)$info['static_path']);
                    StaticPage::category((int)$info['catid']);
                }
            }
            AdminLog::record('content_edit', '编辑内容：' . $data['title']);
            \app\common\Hook::listen('content_save', ['id' => (int)$id, 'action' => 'edit', 'lang' => lang_code(), 'catid' => (int)$data['catid'], 'title' => $data['title']]);
            return $this->ok('保存成功', (string)url('admin/content/index'));
        }

        $fd = $this->catFormData();
        $imageList = [];
        if ($info['image']) {
            $imageList = array_values(array_filter(array_map('trim', explode('***', (string)$info['image']))));
            if (count($imageList) === 1 && strpos((string)$info['image'], ',') !== false) {
                $imageList = array_values(array_filter(array_map('trim', explode(',', (string)$info['image']))));
            }
        }
        if (!$imageList) {
            $ex = json_decode((string)$info['extend'], true);
            if (is_array($ex) && !empty($ex['piclist'])) $imageList = (array)$ex['piclist'];
        }
        // 发稿人/审核人昵称（投稿审核机制）
        $uids = array_filter([(int)$info['author_uid'], (int)$info['audit_uid']]);
        $people = $uids ? Admin::whereIn('id', $uids)->column('nickname', 'id') : [];
        return view('content/edit', ['info' => $info, 'cats' => $fd['parents'], 'models' => $fd['models'], 'selCat' => $info['catid'], 'all_fields' => $this->allFields(), 'imageList' => $imageList, 'people' => $people, 'needAudit' => AdminRole::needAudit(), 'auditOpen' => (int)get_config('content.audit', 1) === 1, 'auditLogs' => ContentAuditLog::logs((int)$info['id']), 'contentLogs' => \app\common\model\ContentLog::logs((int)$info['id']), 'commentCount' => \app\common\model\Comment::where('content_id', (int)$info['id'])->count()]);
    }

    /**
     * 删除内容（支持批量）
     */
    public function delete($id = 0)
    {
        $ids = input('post.ids', $id ?: 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids = array_map('intval', $ids);
        if (!$ids) return $this->fail('请选择内容');
        $list = ContentModel::whereIn('id', $ids)->select();
        $scope = $this->dataScopeCats();
        $count = 0;
        foreach ($list as $row) {
            if ($scope !== null && !in_array((int)$row['catid'], $scope)) continue;
            $row->delete();
            $count++;
        }
        AdminLog::record('content_delete', '删除内容（回收站）：' . $count . ' 条');
        return $this->ok('已移入回收站 ' . $count . ' 条（可在内容管理顶部“回收站”恢复或彻底删除）', (string)url('admin/content/index'));
    }

    /**
     * 回收站：恢复内容（批量）
     */
    public function restore()
    {
        $ids = input('post.ids', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids = array_map('intval', $ids);
        if (!$ids) return $this->fail('请选择内容');
        $scope = $this->dataScopeCats();
        $rows = ContentModel::withTrashed()->whereIn('id', $ids)->where('delete_time', '>', 0)->select();
        $count = 0;
        foreach ($rows as $row) {
            if ($scope !== null && !in_array((int)$row['catid'], $scope)) continue;
            $row->restore();
            $count++;
        }
        AdminLog::record('content_restore', '恢复内容 ' . $count . ' 条');
        return $this->ok('已恢复 ' . $count . ' 条内容');
    }

    /**
     * 回收站：彻底删除（批量，不可恢复）
     */
    public function purge()
    {
        $ids = input('post.ids', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids = array_map('intval', $ids);
        if (!$ids) return $this->fail('请选择内容');
        $scope = $this->dataScopeCats();
        $rows = ContentModel::withTrashed()->whereIn('id', $ids)->where('delete_time', '>', 0)->select();
        $count = 0;
        foreach ($rows as $row) {
            if ($scope !== null && !in_array((int)$row['catid'], $scope)) continue;
            // 彻底删除：同时清理静态页与版本历史
            StaticPage::remove((string)$row['static_path']);
            \app\common\model\ContentLog::where('content_id', $row['id'])->delete();
            $row->forceDelete();
            $count++;
        }
        AdminLog::record('content_purge', '彻底删除内容 ' . $count . ' 条');
        return $this->ok('已彻底删除 ' . $count . ' 条内容');
    }

    /**
     * 批量设置：置顶/推荐/热门/状态
     */
    public function batch()
    {
        $ids  = input('post.ids', '');
        $type = input('post.type', '');
        $val  = intval(input('post.val', 1));
        if (!$ids) return $this->fail('请选择内容');
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return $this->fail('请选择内容');
        $scope = $this->dataScopeCats();
        if ($scope !== null) {
            $ids = ContentModel::whereIn('id', $ids)->whereIn('catid', $scope)->column('id');
            if (!$ids) return $this->fail('无权限操作所选内容');
        }

        $fieldMap = ['top' => 'is_top', 'recommend' => 'is_recommend', 'hot' => 'is_hot', 'status' => 'status', 'headline' => 'top', 'shop' => 'is_shop'];
        if (!isset($fieldMap[$type])) return $this->fail('非法操作');
        $col = $fieldMap[$type];

        if ($type === 'status' && $val == 1) {
            // 发布/取消下架：需审核角色无发布权；免审核角色发布时记录审核人（发稿人即审核人）
            if (AdminRole::needAudit()) {
                return $this->fail('该角色发布内容需审核，请由管理员或审核角色操作');
            }
            $uid = (int)session('admin_id');
            ContentModel::whereIn('id', $ids)->update(['status' => 1, 'audit_uid' => $uid, 'audit_time' => time(), 'audit_status' => 0]);
            foreach ($ids as $cid) ContentAuditLog::record((int)$cid, 3, $uid, '');
        } elseif ($type === 'status' && $val == 0) {
            // 下架/存草稿：清除待审核/驳回标记（非驳回，不写原因），记录变化日志
            ContentModel::whereIn('id', $ids)->update(['status' => 0, 'audit_status' => 0]);
            foreach ($ids as $cid) ContentAuditLog::record((int)$cid, 4, (int)session('admin_id'), '');
        } else {
            ContentModel::whereIn('id', $ids)->update([$col => $val]);
        }
        // 下架/存草稿时清理静态详情页
        if ($type === 'status' && $val == 0) {
            foreach (ContentModel::whereIn('id', $ids)->select() as $row) {
                StaticPage::remove((string)$row['static_path']);
            }
        }
        // 发布操作后刷新对应栏目列表页
        if ($type === 'status' && $val == 1 && get_config('base.static_mode') == 1) {
            foreach (ContentModel::whereIn('id', $ids)->select() as $row) {
                StaticPage::category((int)$row['catid']);
            }
        }
        AdminLog::record('content_batch', '批量设置：' . $type);
        return $this->ok('操作成功');
    }

    /**
     * 批量复制内容（同语言克隆为副本，可指定目标栏目）
     */
    public function batchCopy()
    {
        $ids  = input('post.ids', '');
        $catid = (int)input('post.catid', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return $this->fail('请选择内容');
        if ($catid > 0 && !$this->canManageCat($catid)) return $this->fail('无权限复制到该栏目');
        $scope = $this->dataScopeCats();
        if ($scope !== null) {
            $ids = ContentModel::whereIn('id', $ids)->whereIn('catid', $scope)->column('id');
            if (!$ids) return $this->fail('无权限操作所选内容');
        }

        $fields = self::COPY_FIELDS_SELF;
        $count = 0;
        foreach (ContentModel::whereIn('id', $ids)->select() as $row) {
            $d = [];
            foreach ($fields as $f) {
                $d[$f] = isset($row[$f]) ? $row[$f] : '';
            }
            $d['title'] = $row['title'] . '（副本）';
            if ($catid > 0) $d['catid'] = $catid;
            $d['views'] = 0;
            $d['static_path'] = '';
            // 副本发稿人=操作者；需审核角色复制后强制草稿
            $newC = $this->createWithAudit($d);
            $count++;
        }
        AdminLog::record('content_batchcopy', '批量复制内容 ' . $count . ' 条');
        return $this->ok('已复制 ' . $count . ' 条内容');
    }

    /**
     * 批量移动内容到其他栏目
     */
    public function batchMove()
    {
        $ids  = input('post.ids', '');
        $catid = (int)input('post.catid', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return $this->fail('请选择内容');
        if (!$catid || !CategoryModel::find($catid)) return $this->fail('请选择有效的目标栏目');
        if (!$this->canManageCat($catid)) return $this->fail('无权限移动到该栏目');
        $scope = $this->dataScopeCats();
        if ($scope !== null) {
            $ids = ContentModel::whereIn('id', $ids)->whereIn('catid', $scope)->column('id');
            if (!$ids) return $this->fail('无权限操作所选内容');
        }

        $n = ContentModel::whereIn('id', $ids)->update(['catid' => $catid, 'static_path' => '']);
        AdminLog::record('content_batchmove', '批量移动内容到栏目#' . $catid . ' 共' . $n . '条');
        return $this->ok('已移动 ' . $n . ' 条内容到目标栏目');
    }

    /**
     * 跨语言复制内容：将当前语言内容复制到目标语言的指定栏目，再进行翻译
     */
    public function copyLang($id = 0)
    {
        $id         = (int)$id;
        $targetLang = input('post.target_lang', '');
        $targetCat  = (int)input('post.target_catid', 0);
        if (!in_array($targetLang, lang_codes()) || $targetLang === lang_code()) {
            return $this->fail('请选择有效的目标语言');
        }
        $info = ContentModel::find($id);
        if (!$info) {
            return $this->fail('内容不存在');
        }
        if (!$this->canManageCat((int)$info['catid'])) return $this->fail('无权限操作该内容');

        // 未指定目标栏目时，按栏目英文别名自动映射（源 ename == 目标 ename）
        if ($targetCat === 0) {
            $srcCat = CategoryModel::find($info['catid']);
            if ($srcCat && $srcCat['ename']) {
                $prev = lang_code();
                lang_set($targetLang);
                try {
                    $tgt = CategoryModel::where('ename', $srcCat['ename'])->find();
                } finally {
                    lang_set($prev);
                }
                $targetCat = $tgt ? (int)$tgt['id'] : 0;
            }
        }
        if ($targetCat === 0) {
            return $this->fail('未找到目标语言对应栏目（请保证目标语言存在同英文别名的栏目）');
        }

        $fields = self::COPY_FIELDS_LANG;
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = isset($info[$f]) ? $info[$f] : '';
        }
        $data['catid']    = $targetCat;
        $data['views']    = 0;
        $data['static_path'] = '';
        // 跨语言复制：发稿人=操作者；需审核角色强制草稿，免审核角色保持源状态（发布则记审核人）
        $prev = lang_code();
        lang_set($targetLang);
        try {
            $new = $this->createWithAudit($data);
        } finally {
            lang_set($prev);
        }
        AdminLog::record('content_copylang', '内容[' . $targetLang . ']复制 id=' . $new['id'] . ' (源' . $prev . ' id=' . $id . ')');
        return $this->ok('已复制到 ' . $targetLang . '，请在目标语言中翻译', (string)url('admin/content/index'));
    }

    /**
     * 批量跨语言复制内容：把选中的内容复制到目标语言的指定栏目，再在目标语言翻译
     */
    public function batchCopyLang()
    {
        $ids        = input('post.ids', '');
        $targetLang = input('post.target_lang', '');
        $targetCat  = (int)input('post.target_catid', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return $this->fail('请选择内容');
        if (!in_array($targetLang, lang_codes()) || $targetLang === lang_code()) {
            return $this->fail('请选择有效的目标语言');
        }
        if ($targetCat === 0) return $this->fail('请选择目标语言下的目标栏目');
        // 校验目标栏目存在于目标语言
        $prev = lang_code();
        lang_set($targetLang);
        try {
            $tcat = CategoryModel::find($targetCat);
        } finally {
            lang_set($prev);
        }
        if (!$tcat) return $this->fail('目标栏目不存在');

        // 数据范围：只复制权限栏目内的内容
        $scope = $this->dataScopeCats();
        if ($scope !== null) {
            $ids = ContentModel::whereIn('id', $ids)->whereIn('catid', $scope)->column('id');
            if (!$ids) return $this->fail('无权限操作所选内容');
        }
        $srcRows = ContentModel::whereIn('id', $ids)->select(); // 源语言读取
        $fields  = self::COPY_FIELDS_LANG;
        $count = 0;
        lang_set($targetLang);
        try {
            foreach ($srcRows as $info) {
                $data = [];
                foreach ($fields as $f) { $data[$f] = isset($info[$f]) ? $info[$f] : ''; }
                $data['catid']       = $targetCat;
                $data['views']       = 0;
                $data['static_path'] = '';
                // 跨语言复制：发稿人=操作者；需审核角色强制草稿，免审核角色保持源状态（发布则记审核人）
                $newC = $this->createWithAudit($data);
                $count++;
            }
        } finally {
            lang_set($prev);
        }
        AdminLog::record('content_batchcopylang', '批量跨语言复制内容[' . $targetLang . ']：' . $count . ' 条');
        return $this->ok('已复制 ' . $count . ' 条内容到 ' . $targetLang . '，请在目标语言中翻译', (string)url('admin/content/index'));
    }

    /**
     * 跨站/跨语言推送内容（v3.20）：批量勾选内容推送到目标站点（分站或主站）
     * POST: ids, to_site, to_lang, to_catid
     */
    public function pushToSite()
    {
        $ids    = input('post.ids', '');
        $toSite = trim((string)input('post.to_site', ''));
        $toLang = trim((string)input('post.to_lang', ''));
        $toCat  = (int)input('post.to_catid', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return $this->fail('请选择内容');
        if (!in_array($toLang, lang_codes(), true)) return $this->fail('目标语言不存在');
        if ($toCat === 0) return $this->fail('请选择目标栏目');
        // 校验目标栏目存在于目标语言
        $prev = lang_code();
        lang_set($toLang);
        try {
            $tcat = CategoryModel::find($toCat);
        } finally {
            lang_set($prev);
        }
        if (!$tcat) return $this->fail('目标栏目不存在');

        // 当前站点标识
        $my = $prev;
        try { if ($my === default_lang()) $my = 'main'; } catch (\Throwable $e) {}
        if ($toSite === '' || $toSite === $my) return $this->fail('请选择有效的目标站点');

        $scope = $this->dataScopeCats();
        if ($scope !== null) {
            $ids = ContentModel::whereIn('id', $ids)->whereIn('catid', $scope)->column('id');
            if (!$ids) return $this->fail('无权限操作所选内容');
        }
        $count = \app\common\model\Push::createPush(
            $prev, $my, $toSite, $toLang, $toCat, $ids,
            (int)session('admin_id'), (string)session('admin_nick'), $scope
        );
        if (!$count) return $this->fail('所选内容均不在您的管理范围内');
        AdminLog::record('content_pushtosite', "内容推送 [{$prev}]→[{$toSite}] 共 {$count} 条");
        $tip = $toSite === 'main' ? '已推送主站，等待主站管理员审核' : '已推送分站，等待分站管理员接收';
        return $this->ok('推送成功（' . $count . ' 条），' . $tip, (string)url('admin/push_site/index?tab=out'));
    }

    /**
     * 跨站推送可选目标站点列表（v3.20）：主站 + 启用路径站点 + 启用语言版本
     * 返回 [{key, name, lang}]，lang=该站点对应语言代码
     */
    protected function pushSites(): array
    {
        $sites = [];
        try { $default = default_lang(); } catch (\Throwable $e) { $default = 'cn'; }
        $sites[] = ['key' => 'main', 'name' => '主站', 'lang' => $default];
        try {
            if ((int)get_config('site.multi', 0) === 1) {
                foreach (\think\facade\Db::name('site')->where('status', 1)->order('sort', 'asc')->select() as $st) {
                    $alias = (string)$st['alias'];
                    if ($alias === $default) continue;
                    $sites[] = ['key' => $alias, 'name' => $st['name'] . '（' . $alias . '）', 'lang' => $alias];
                }
            }
            foreach (\app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->column('code') as $code) {
                $hit = false;
                foreach ($sites as $s) { if ($s['key'] === $code) { $hit = true; break; } }
                if (!$hit) $sites[] = ['key' => $code, 'name' => '语言站 ' . $code, 'lang' => $code];
            }
        } catch (\Throwable $e) {}
        // 排除当前站点
        $my = lang_code();
        try { if ($my === default_lang()) $my = 'main'; } catch (\Throwable $e) {}
        return array_values(array_filter($sites, function ($s) use ($my) { return $s['key'] !== $my; }));
    }

    /**
     * 收集表单字段（含扩展字段 JSON）
     */
    protected function collectPost()
    {
        $data = input('post.');
        // 字段名映射（云优标识 -> 内容表列）：副标题 ftitle->short_title、描述 desc->description、图片集(多图) piclist->image
        foreach (['ftitle' => 'short_title', 'desc' => 'description', 'piclist' => 'image', 'pic' => 'thumb', 'update_time' => 'create_time'] as $k => $col) {
            if (array_key_exists($k, $data)) { $data[$col] = $data[$k]; unset($data[$k]); }
        }
        // 取出扩展字段（以 extend_ 开头）
        $extend = [];
        foreach ($data as $k => $v) {
            if (strpos($k, 'extend_') === 0) {
                $extend[substr($k, 7)] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
            }
        }
        $data['extend'] = $extend ? json_encode($extend, JSON_UNESCAPED_UNICODE) : '';
        // 图片集双写：image 列（***分隔）+ extend.piclist（数组）——保证前台云优键与后台编辑回显一致
        if (isset($data['image']) && $data['image'] !== '') {
            $pics = array_values(array_filter(array_map('trim', explode('***', (string)$data['image']))));
            if (count($pics) === 1 && strpos((string)$data['image'], ',') !== false) {
                $pics = array_values(array_filter(array_map('trim', explode(',', (string)$data['image']))));
            }
            $ex = ($data['extend'] ?? '') ? json_decode($data['extend'], true) : [];
            if (!is_array($ex)) $ex = [];
            $ex['piclist'] = $pics;
            $data['extend'] = json_encode($ex, JSON_UNESCAPED_UNICODE);
        }
        // 发稿人/审核人/审核时间/审核状态/提交次数/驳回原因由系统写入，禁止表单直接覆盖
        unset($data['author_uid'], $data['audit_uid'], $data['audit_time'], $data['audit_status'], $data['submit_count'], $data['reject_reason'], $data['audit_note']);
        // 定时发布：publish_time_dt 为 Y-m-d H:i 或空；空=立即发布(0)
        $pt = trim((string)input('post.publish_time_dt', ''));
        $data['publish_time'] = $pt !== '' ? strtotime($pt) : (int)input('post.publish_time', 0);
        unset($data['file'], $data['create_time_dt'], $data['add_time_dt'], $data['publish_time_dt']);
        return $data;
    }

    /**
     * 状态审核解析：返回 [status, audit_uid, audit_time]
     * @param int   $formStatus 表单提交的状态（1发布 0草稿）
     * @param mixed $row        已有内容行（新建传 null）
     * 规则：
     *  - 需审核角色（need_audit=1）：新建一律草稿；编辑时原已发布保持发布，原草稿即使选发布也强制草稿
     *  - 免审核角色：可直接发布；新建发布时发稿人即审核人；编辑时 0→1 记录审核人与审核时间
     */
    protected function resolveStatus($formStatus, $row)
    {
        // 返回 ['status','audit_uid','audit_time','audit_status','submit_count']
        // audit_status: 0正常 1待审核 2已驳回；submit_count: 提交审核次数
        $formStatus = (int)$formStatus;
        $now   = time();
        $uid   = (int)session('admin_id');
        $isNew = $row === null;
        $oldStatus = $isNew ? -1 : (int)$row['status'];

        if (AdminRole::needAudit()) {
            // 需审核角色（如投稿员）：保存即"提交待审核"
            if ($isNew) {
                return ['status' => 0, 'audit_uid' => 0, 'audit_time' => 0, 'audit_status' => 1, 'submit_count' => 1];
            }
            if ($oldStatus === 1) {
                // 已发布内容编辑：保持发布与审核记录
                return ['status' => 1, 'audit_uid' => (int)$row['audit_uid'], 'audit_time' => (int)$row['audit_time'], 'audit_status' => 0, 'submit_count' => (int)$row['submit_count'] ?: 1];
            }
            // 草稿/待审核/已驳回：保存视为重提（提交次数 +1，重新待审核）
            return ['status' => 0, 'audit_uid' => 0, 'audit_time' => 0, 'audit_status' => 1, 'submit_count' => ((int)$row['submit_count'] ?: 1) + 1];
        }

        if ($isNew) {
            // 免审核：新建可直接发布（发稿人即审核人）；选择草稿则无审核记录
            if ($formStatus === 1) {
                return ['status' => 1, 'audit_uid' => $uid, 'audit_time' => $now, 'audit_status' => 0, 'submit_count' => 1];
            }
            return ['status' => 0, 'audit_uid' => 0, 'audit_time' => 0, 'audit_status' => 0, 'submit_count' => 1];
        }
        // 编辑（免审核角色）
        if ($oldStatus === 0 && $formStatus === 1) {
            // 待审核/已驳回 → 审核通过发布（记录审核人与审核时间）
            return ['status' => 1, 'audit_uid' => $uid, 'audit_time' => $now, 'audit_status' => 0, 'submit_count' => (int)$row['submit_count'] ?: 1];
        }
        if ($oldStatus === 1 && $formStatus === 1) {
            return ['status' => 1, 'audit_uid' => (int)$row['audit_uid'], 'audit_time' => (int)$row['audit_time'], 'audit_status' => 0, 'submit_count' => (int)$row['submit_count'] ?: 1];
        }
        return ['status' => 0, 'audit_uid' => (int)$row['audit_uid'], 'audit_time' => (int)$row['audit_time'], 'audit_status' => 0, 'submit_count' => (int)$row['submit_count'] ?: 1];
    }

    // 跨语言复制保留的字段（同语言复制另含本语言属性 top/tag/etitle/jumpurl）
    protected const COPY_FIELDS_LANG = ['catid', 'model_id', 'title', 'short_title', 'author', 'source', 'thumb', 'image', 'keywords', 'description', 'content', 'extend', 'is_top', 'is_recommend', 'is_hot', 'status', 'sort', 'create_time'];
    protected const COPY_FIELDS_SELF = ['catid', 'model_id', 'title', 'short_title', 'author', 'source', 'thumb', 'image', 'keywords', 'description', 'content', 'extend', 'is_top', 'is_recommend', 'is_hot', 'top', 'tag', 'etitle', 'jumpurl', 'status', 'sort', 'create_time', 'is_shop'];

    /**
     * 按审核规则创建内容（新增/复制共用）：
     * 写发稿人=当前管理员，解析审核状态，创建后写审核日志
     * @param array $data 已收集字段（含 status）
     * @param bool  $withNote 待审核时是否附带表单 audit_note（发布页首次提交说明）
     */
    protected function createWithAudit(array $data, bool $withNote = false)
    {
        $data['author_uid'] = (int)session('admin_id');
        $st = $this->resolveStatus((int)($data['status'] ?? 1), null);
        $data['status']      = $st['status'];
        $data['audit_uid']   = $st['audit_uid'];
        $data['audit_time']  = $st['audit_time'];
        $data['audit_status'] = $st['audit_status'];
        $data['submit_count'] = $st['submit_count'];
        $row = ContentModel::create($data);
        ContentAuditLog::record((int)$row['id'], $st['audit_status'] == 1 ? 1 : 3, (int)session('admin_id'), ($withNote && $st['audit_status'] == 1) ? trim((string)input('post.audit_note', '')) : '');
        // 版本历史：创建即记初始快照
        try { \app\common\model\ContentLog::record((int)$row['id'], 'save', $data, '创建'); } catch (\Throwable $e) {}
        return $row;
    }

    /**
     * 所有模型的扩展字段（按 model_id 分组，供前端动态渲染表单）
     */
    protected function allFields(): array
    {
        $out = [];
        foreach (\app\common\model\ModelField::order('sort', 'asc')->order('id', 'asc')->select() as $f) {
            $out[$f['model_id']][] = [
                'id' => $f['id'], 'name' => $f['name'], 'label' => $f['label'],
                'type' => $f['type'], 'options' => $f['options'], 'required' => $f['required'],
            ];
        }
        return $out;
    }
    /**
     * 审核操作：通过 / 驳回（仅免审核角色可执行）
     * POST: id, act=pass|reject, reason（驳回必填）
     */
    public function audit()
    {
        $id  = (int)input('post.id', 0);
        $act = trim((string)input('post.act', ''));
        if (AdminRole::needAudit()) return $this->fail('无审核权限（当前角色发布需审核）');
        $row = ContentModel::find($id);
        if (!$row) return $this->fail('内容不存在');
        if (!$this->canManageCat((int)$row['catid'])) return $this->fail('无权限操作该内容');
        $uid = (int)session('admin_id');
        if ($act === 'pass') {
            $row->save(['status' => 1, 'audit_status' => 0, 'audit_uid' => $uid, 'audit_time' => time()]);
            ContentAuditLog::record($id, 3, $uid, '');
            if (get_config('base.static_mode') == 1) {
                StaticPage::refreshContent($id, (int)$row['catid']);
            }
            AdminLog::record('content_audit', '审核通过内容#' . $id);
            return $this->ok('已审核通过', (string)url('admin/content/index'));
        }
        if ($act === 'reject') {
            $reason = trim((string)input('post.reason', ''));
            if ($reason === '') return $this->fail('请填写驳回原因');
            $row->save(['status' => 0, 'audit_status' => 2, 'reject_reason' => $reason]);
            ContentAuditLog::record($id, 2, $uid, $reason);
            if (get_config('base.static_mode') == 1) {
                StaticPage::remove((string)$row['static_path']);
                StaticPage::category((int)$row['catid']);
            }
            AdminLog::record('content_audit', '驳回内容#' . $id . '：' . $reason);
            return $this->ok('已驳回（投稿人可查看原因并修改后重新提交）', (string)url('admin/content/index'));
        }
        return $this->fail('非法操作');
    }

    /**
     * 审核日志（审核对话框用）：返回提交/驳回/通过的完整时间线
     */
    public function auditLog($id = 0)
    {
        $id = (int)$id;
        $row = ContentModel::find($id);
        if (!$row) {
            return json(['code' => 0, 'msg' => '内容不存在', 'data' => [], 'submit_count' => 0, 'reject_reason' => '']);
        }
        return json([
            'code' => 1,
            'data'          => ContentAuditLog::logs($id),
            'submit_count'  => (int)$row['submit_count'],
            'reject_reason' => (string)$row['reject_reason'],
            'status'        => (int)$row['status'],
            'audit_status'  => (int)$row['audit_status'],
        ]);
    }

    /**
     * 版本历史：回滚到指定版本
     */
    public function rollback($id = 0)
    {
        $id = (int)$id;
        $logId = (int)input('post.log_id', 0);
        $row = ContentModel::find($id);
        if (!$row) return $this->fail('内容不存在');
        if (!$this->canManageCat((int)$row['catid'])) return $this->fail('无权限操作该内容');
        $log = \app\common\model\ContentLog::find($logId);
        if (!$log || (int)$log['content_id'] !== $id) return $this->fail('版本不存在');
        $row->save([
            'title'       => $log['title'],
            'description' => $log['description'],
            'content'     => $log['content'],
            'thumb'       => $log['thumb'],
        ]);
        \app\common\model\ContentLog::record($id, 'rollback', [
            'title' => $log['title'], 'description' => $log['description'],
            'content' => $log['content'], 'thumb' => $log['thumb'],
        ], '回滚到版本#' . $logId);
        AdminLog::record('content_rollback', '内容#' . $id . ' 回滚到版本#' . $logId);
        return $this->ok('已回滚到该版本', (string)url('admin/content/edit', ['id' => $id]));
    }

    /**
     * 内容列表行内编辑：标题/浏览量/发布时间/创建时间
     */
    public function editField($id = 0)
    {
        $id    = (int)$id;
        $field = input('post.field', '');
        $value = trim((string)input('post.value', ''));
        $allow = ['title', 'views', 'create_time', 'add_time', 'status'];
        if (!in_array($field, $allow)) {
            return $this->fail('不允许的字段');
        }
        $row = ContentModel::find($id);
        if (!$row) {
            return $this->fail('内容不存在');
        }
        if (!$this->canManageCat((int)$row['catid'])) return $this->fail('无权限操作该内容');
        if ($field === 'title') {
            if ($value === '') {
                return $this->fail('标题不能为空');
            }
            $data = ['title' => $value];
        } elseif ($field === 'views') {
            $data = ['views' => (int)$value];
        } elseif ($field === 'status') {
            $newStatus = in_array($value, ['1', '0', 1, 0]) ? (int)$value : 1;
            $oldStatus = (int)$row['status'];
            if ($newStatus === 1 && $oldStatus === 0) {
                // 0→1 发布：需审核角色无发布权；免审核角色发布时记录审核人并记审核日志
                if (AdminRole::needAudit()) {
                    return $this->fail('该角色发布内容需审核，请由管理员或审核角色操作');
                }
                $data = ['status' => 1, 'audit_uid' => (int)session('admin_id'), 'audit_time' => time(), 'audit_status' => 0];
            } elseif ($newStatus === 0 && $oldStatus === 1) {
                $data = ['status' => 0, 'audit_status' => 0];
            } else {
                $data = ['status' => $newStatus];
            }
        } else {
            $ts = strtotime($value);
            if (!$ts) {
                return $this->fail('时间格式不正确（应为 Y-m-d H:i）');
            }
            $data = [$field => $ts];
        }
        $oldStatus = (int)$row['status'];
        $row->save($data);
        // 审核日志：行内 0→1 发布 = 审核通过；1→0 下架/存草稿 = 记录变化
        if ($field === 'status') {
            if ((int)$row['status'] == 1 && $oldStatus == 0) {
                ContentAuditLog::record($id, 3, (int)session('admin_id'), '');
            } elseif ((int)$row['status'] == 0 && $oldStatus == 1) {
                ContentAuditLog::record($id, 4, (int)session('admin_id'), '');
            }
        }
        // 静态模式联动：发布→生成/刷新；下架/草稿→删除静态详情页并刷新栏目列表页
        if (get_config('base.static_mode') == 1) {
            if ($field === 'status') {
                if ((int)$row['status'] == 1 && $oldStatus == 0) {
                    StaticPage::refreshContent($row['id'], $row['catid']);
                } elseif ((int)$row['status'] == 0 && $oldStatus == 1) {
                    StaticPage::remove((string)$row['static_path']);
                    StaticPage::category((int)$row['catid']);
                }
            }
        }
        AdminLog::record('content_field', '内容#' . $id . ' 修改 ' . $field);
        return $this->ok('已保存');
    }

}
