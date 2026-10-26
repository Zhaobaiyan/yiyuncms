<?php
// +----------------------------------------------------------------------
// | 栏目管理控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Category as CategoryModel;
use app\common\model\CatModelField;
use app\common\model\CmsModel;

class Category extends AdminBase
{
    /**
     * 栏目列表（树形）
     */
    public function index()
    {
        $isTrash = input('get.status', '') === 'trash';
        if ($isTrash) {
            $tree = CategoryModel::withTrashed()->where('delete_time', '>', 0)->order('delete_time', 'desc')->select()->toArray();
            $flat = [];
            foreach ($tree as $c) $flat[] = $c;
        } else {
            $tree = CategoryModel::getTree();
            $flat = CategoryModel::getFlatList();
        }
        // 数据范围过滤：受限角色只显示可管理的栏目（含子级）
        $scope = $this->dataScopeCats();
        if ($scope !== null) {
            $tree = $this->filterCategoryTree($tree, $scope);
            $flat = array_values(array_filter($flat, fn($c) => in_array($c['id'], $scope)));
        }
        $childCount = [];
        foreach ($flat as $c) { $childCount[$c['parent_id']] = ($childCount[$c['parent_id']] ?? 0) + 1; }
        foreach ($flat as &$c) {
            $c['has_child'] = !empty($childCount[$c['id']]);
            // 回收站模式行无 depth（getTree 才计算），兜底为 0
            $c['depth'] = isset($c['depth']) ? (int)$c['depth'] : 0;
            // 分级缩进：一级 12px，每深一级 +26px（供列表直观区分层级）
            $c['indent'] = 12 + (int)$c['depth'] * 44;
        }
        unset($c);
        return view('category/index', ['tree' => $tree, 'flat' => $flat, 'is_trash' => $isTrash]);
    }

    /**
     * 受限角色下，校验父栏目是否可管理
     */
    protected function assertParentManageable($parentId)
    {
        $scope = $this->dataScopeCats();
        if ($scope === null) return true;
        if ((int)$parentId === 0) return $this->fail('当前角色只能在有权限的栏目下添加子栏目');
        if (!in_array((int)$parentId, $scope)) return $this->fail('无权限在该父栏目下操作');
        return true;
    }

    /**
     * 添加栏目
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['content'] = input('post.content', '', null);
            $data['extend']  = input('post.extend', '', null);
            if ($this->assertParentManageable((int)($data['parent_id'] ?? 0)) !== true) return;
            $cat = CategoryModel::create($data);
            AdminLog::record('category_add', '添加栏目：' . $data['name']);
            return $this->ok('添加成功', (string)url('admin/category/index'));
        }
        $data = $this->catFormData();
        return view('category/edit', ['info' => null, 'parents' => $data['parents'], 'models' => $data['models'], 'catModels' => $data['catModels'], 'tpls' => $this->tplOptions(), 'catExtend' => []]);
    }

    /**
     * 编辑栏目
     */
    public function edit($id = 0)
    {
        $info = CategoryModel::find($id);
        if (!$info) return $this->fail('栏目不存在');
        if (!$this->canManageCat($id)) return $this->fail('无权限操作该栏目');

        if (request()->isPost()) {
            $data = input('post.');
            $data['content'] = input('post.content', '', null);
            $data['extend']  = input('post.extend', '', null);
            if ($this->assertParentManageable((int)($data['parent_id'] ?? $info['parent_id'])) !== true) return;
            $info->save($data);
            AdminLog::record('category_edit', '编辑栏目：' . $data['name']);
            return $this->ok('保存成功', (string)url('admin/category/index'));
        }

        $data = $this->catFormData();
        return view('category/edit', ['info' => $info, 'parents' => $data['parents'], 'models' => $data['models'], 'catModels' => $data['catModels'], 'tpls' => $this->tplOptions(), 'catExtend' => $this->mergeTplFields($info)]);
    }

    /**
     * 合并栏目模型模板字段到栏目自定义字段
     * 模板字段来自 yy_cat_model_field（定义），值从栏目 extend 中取（各栏目单独填写），
     * 标记 is_tpl=1 供前端显示"模板"角标（不可删除/改类型）
     */
    protected function mergeTplFields($info)
    {
        $extend = json_decode((string)$info['extend'], true) ?: [];
        $cmid = (int)$info['cat_model_id'];
        if (!$cmid) return $extend;
        $tpls = CatModelField::fieldsOf($cmid);
        foreach ($tpls as $tf) {
            $key = $tf['name'];
            if (isset($extend[$key])) {
                $extend[$key]['is_tpl'] = 1;
            } else {
                $extend[$key] = [
                    'label' => $tf['title'], 'type' => $tf['type'],
                    'options' => $tf['options'], 'value' => '', 'is_tpl' => 1,
                ];
            }
        }
        return $extend;
    }

    /**
     * 扫描当前语言模板目录，自动识别可用模板（下拉用）
     * 命名规范：列表 list_xxx.html / 内容 show_xxx.html / 首页 index_index.html
     */
    protected function tplOptions(): array
    {
        $dir  = root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir();
        $lists = ['list'];
        $shows = ['show'];
        if (is_dir($dir)) {
            foreach ((array)glob($dir . DIRECTORY_SEPARATOR . 'list_*.html') as $f) {
                $lists[] = basename($f, '.html');
            }
            foreach ((array)glob($dir . DIRECTORY_SEPARATOR . 'show_*.html') as $f) {
                $shows[] = basename($f, '.html');
            }
        }
        return ['lists' => $lists, 'shows' => $shows];
    }

    /**
     * 删除栏目（含子栏目）
     */
    public function delete($id = 0)
    {
        $cat = CategoryModel::find($id);
        if (!$cat) return $this->fail('栏目不存在');
        if (!$this->canManageCat($id)) return $this->fail('无权限操作该栏目');
        $force = (int)input('post.force', 0);
        if (!$force) {
            $childCount = CategoryModel::where('parent_id', $id)->count();
            $contentCount = \app\common\model\Content::where('catid', $id)->count();
            if ($childCount || $contentCount) {
                return $this->fail('该栏目下存在子栏目或内容，勾选“连同子栏目与内容一起删除”后再操作');
            }
        }
        // 收集全部后代栏目 id
        $allIds = [$id];
        $loop = true;
        while ($loop) {
            $loop = false;
            foreach (CategoryModel::withTrashed()->whereIn('parent_id', $allIds)->column('id') as $cid) {
                if (!in_array($cid, $allIds)) { $allIds[] = $cid; $loop = true; }
            }
        }
        if ($force) {
            // 级联软删：所有后代栏目 + 其下内容
            CategoryModel::whereIn('id', $allIds)->delete();
            \app\common\model\Content::whereIn('catid', $allIds)->delete();
        } else {
            $cat->delete();
        }
        AdminLog::record('category_delete', '删除栏目（回收站）：' . $cat['name']);
        return $this->ok('已移入回收站（可在栏目管理顶部“回收站”恢复或彻底删除）', (string)url('admin/category/index'));
    }

    /**
     * 回收站：恢复栏目（仅恢复所选栏目本身；其下内容可在内容回收站单独恢复）
     */
    public function restore()
    {
        $ids = input('post.ids', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids = array_map('intval', $ids);
        if (!$ids) return $this->fail('请选择栏目');
        $scope = $this->dataScopeCats();
        $rows = CategoryModel::withTrashed()->whereIn('id', $ids)->where('delete_time', '>', 0)->select();
        $count = 0;
        foreach ($rows as $row) {
            if ($scope !== null && !in_array((int)$row['id'], $scope)) continue;
            // 父栏目若仍在回收站则先恢复父级，保证树形完整
            if ((int)$row['parent_id'] > 0) {
                $parent = CategoryModel::withTrashed()->find($row['parent_id']);
                if ($parent && (int)$parent['delete_time'] > 0) $parent->restore();
            }
            $row->restore();
            $count++;
        }
        AdminLog::record('category_restore', '恢复栏目 ' . $count . ' 个');
        return $this->ok('已恢复 ' . $count . ' 个栏目');
    }

    /**
     * 回收站：彻底删除栏目（批量，不可恢复；其下内容一并彻底删除）
     */
    public function purge()
    {
        $ids = input('post.ids', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids = array_map('intval', $ids);
        if (!$ids) return $this->fail('请选择栏目');
        $scope = $this->dataScopeCats();
        $count = 0;
        foreach ($ids as $cid) {
            if ($scope !== null && !in_array((int)$cid, $scope)) continue;
            $cat = CategoryModel::withTrashed()->find($cid);
            if (!$cat || (int)$cat['delete_time'] === 0) continue;
            // 收集后代栏目 id
            $allIds = [$cid];
            $loop = true;
            while ($loop) {
                $loop = false;
                foreach (CategoryModel::withTrashed()->whereIn('parent_id', $allIds)->column('id') as $x) {
                    if (!in_array($x, $allIds)) { $allIds[] = $x; $loop = true; }
                }
            }
            // 彻底删除内容 + 静态页 + 栏目
            foreach (\app\common\model\Content::withTrashed()->whereIn('catid', $allIds)->select() as $row) {
                StaticPage::remove((string)$row['static_path']);
                $row->forceDelete();
            }
            CategoryModel::withTrashed()->whereIn('id', $allIds)->delete(true);
            $count++;
        }
        AdminLog::record('category_purge', '彻底删除栏目 ' . $count . ' 个');
        return $this->ok('已彻底删除 ' . $count . ' 个栏目');
    }

    /**
     * 排序
     */
    public function sort()
    {
        $ids = input('post.sort/a', []);
        $scope = $this->dataScopeCats();
        foreach ($ids as $id => $sort) {
            if ($scope !== null && !in_array((int)$id, $scope)) continue;
            CategoryModel::where('id', $id)->update(['sort' => intval($sort)]);
        }
        return $this->ok('排序已保存');
    }

    /**
     * 批量添加所需的下拉数据：父栏目/模型/模板
     */
    public function batchData()
    {
        $flat = CategoryModel::getFlatList();
        $scope = $this->dataScopeCats();
        if ($scope !== null) $flat = array_values(array_filter($flat, fn($c) => in_array($c['id'], $scope)));
        $cats = [];
        foreach ($flat as $c) {
            $cats[] = ['id' => (int)$c['id'], 'name' => $c['name'], 'depth' => (int)$c['depth']];
        }
        return json(['code' => 1, 'data' => [
            'cats' => $cats,
            'models' => CmsModel::order('id')->select()->toArray(),
            'tpls' => $this->tplOptions(),
        ]]);
    }

    /**
     * 批量添加栏目（可视化表单，rows 为 JSON 数组）
     */
    public function batchAdd()
    {
        $rowsJson = trim((string)input('post.rows', ''));
        if ($rowsJson === '') return $this->fail('请填写要批量添加的栏目');
        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || !$rows) return $this->fail('数据格式不正确');
        $count = 0; $errors = [];
        foreach ($rows as $r) {
            $name = trim((string)($r['name'] ?? ''));
            $ename = trim((string)($r['ename'] ?? ''));
            $parentId = (int)($r['parent_id'] ?? 0);
            $modelId = (int)($r['model_id'] ?? 1);
            $listTpl = trim((string)($r['list_template'] ?? ''));
            $contentTpl = trim((string)($r['content_template'] ?? ''));
            if ($name === '') { $errors[] = '空名称'; continue; }
            if ($this->assertParentManageable($parentId) !== true) { $errors[] = '无权限的父栏目：' . ($parentId ?: '顶级'); continue; }
            if (CategoryModel::where('name', $name)->count()) { $errors[] = '已存在：' . $name; continue; }
            if ($ename !== '' && CategoryModel::where('ename', $ename)->count()) { $errors[] = '别名重复：' . $ename; continue; }
            CategoryModel::create([
                'parent_id' => $parentId, 'model_id' => $modelId,
                'name' => $name, 'ename' => $ename ?: '',
                'type' => 'list', 'is_show' => 1, 'is_nav' => 1, 'sort' => 100,
                'template' => $listTpl, 'content_template' => $contentTpl,
            ]);
            $count++;
        }
        AdminLog::record('category_batchadd', '批量添加栏目 ' . $count . ' 个');
        $msg = '成功添加 ' . $count . ' 个栏目';
        if ($errors) $msg .= '；跳过：' . implode('；', array_slice($errors, 0, 5));
        return $this->ok($msg);
    }

    /**
     * 列表页内联编辑字段（别名 ename / 排序 sort / 导航 is_nav / 显示 is_show / 名称 name）
     * 别名变更后清空 static_path，静态生成将按新别名重新生成目录
     */
    public function editField($id = 0)
    {
        $id    = (int)$id;
        $field = input('post.field', '');
        $value = trim((string)input('post.value', ''));

        $allow = ['ename', 'sort', 'is_show', 'is_nav', 'name', 'pagesize'];
        if (!in_array($field, $allow)) {
            return $this->fail('不允许的字段');
        }
        $cat = CategoryModel::find($id);
        if (!$cat) {
            return $this->fail('栏目不存在');
        }
        if (!$this->canManageCat($id)) return $this->fail('无权限操作该栏目');
        if ($field === 'ename') {
            if ($value === '') {
                return $this->fail('别名不能为空');
            }
            if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
                return $this->fail('别名只能由字母、数字、下划线、横线组成');
            }
            if (CategoryModel::where('ename', $value)->where('id', '<>', $id)->count()) {
                return $this->fail('该别名已被其他栏目使用');
            }
            $data = ['ename' => $value, 'static_path' => ''];
        } elseif ($field === 'sort') {
            $data = ['sort' => (int)$value];
        } elseif ($field === 'pagesize') {
            $data = ['pagesize' => max(1, (int)$value)];
        } elseif ($field === 'is_show' || $field === 'is_nav') {
            $data = [$field => in_array($value, ['1', '0', 1, 0]) ? (int)$value : 1];
        } else {
            $data = [$field => $value];
        }
        $cat->save($data);
        AdminLog::record('category_field', '栏目#' . $id . ' 修改 ' . $field . '=' . $value);
        return $this->ok('已保存');
    }

    /**
     * 跨语言复制栏目：递归复制整个子树（保持层级）及各自内容到目标语言
     * @param int $id 源栏目
     */
    public function copyLang($id = 0)
    {
        $id           = (int)$id;
        $targetLang   = input('post.target_lang', '');
        $targetParent = (int)input('post.target_parent', 0);
        if (!in_array($targetLang, lang_codes()) || $targetLang === lang_code()) {
            return $this->fail('请选择有效的目标语言');
        }
        $cat = CategoryModel::find($id);
        if (!$cat) {
            return $this->fail('栏目不存在');
        }
        if (!$this->canManageCat($id)) return $this->fail('无权限操作该栏目');
        if ($targetParent > 0 && !$this->targetCatExists($targetLang, $targetParent)) {
            return $this->fail('目标父栏目不存在');
        }
        $prev  = lang_code();
        $node  = $this->exportTree($cat); // 源语言读取整棵子树
        $count = 0;
        lang_set($targetLang);
        try {
            $this->rebuildTree($node, $targetParent, $count);
        } finally {
            lang_set($prev);
        }
        AdminLog::record('category_copylang', '栏目[' . $targetLang . ']复制子树（' . $count . '个栏目含内容）源id=' . $id);
        return $this->ok('已复制栏目及其子栏目（共 ' . $count . ' 个栏目）到 ' . $targetLang . '，请在目标语言中翻译', (string)url('admin/category/index'));
    }

    /**
     * 批量跨语言复制栏目：把选中的栏目子树复制到目标语言，保持层级
     * 可选复制到顶级(parent=0)或指定目标语言的一级/二级栏目下
     */
    public function batchCopyLang()
    {
        $ids          = input('post.ids', '');
        $targetLang   = input('post.target_lang', '');
        $targetParent = (int)input('post.target_parent', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return $this->fail('请选择栏目');
        if (!in_array($targetLang, lang_codes()) || $targetLang === lang_code()) {
            return $this->fail('请选择有效的目标语言');
        }
        if ($targetParent > 0 && !$this->targetCatExists($targetLang, $targetParent)) {
            return $this->fail('目标父栏目不存在');
        }

        $prev   = lang_code();
        $sel    = CategoryModel::whereIn('id', $ids)->select()->toArray(); // 源语言
        $scope  = $this->dataScopeCats();
        if ($scope !== null) $sel = array_values(array_filter($sel, fn($s) => in_array((int)$s['id'], $scope)));
        if (!$sel) return $this->fail('无权限复制所选栏目');
        $selIds = array_column($sel, 'id');
        // 只保留顶层选中（祖先未被选中的），避免父子重复复制（一次查询取全部父子关系，避免 N+1）
        $idMap  = CategoryModel::whereIn('id', $selIds)->column('parent_id', 'id');
        $roots = [];
        foreach ($sel as $s) {
            $anc = false;
            $pid = (int)$s['parent_id'];
            $g   = 0;
            while ($pid > 0 && $g++ < 20) {
                if (isset($idMap[$pid])) { $anc = true; break; }
                $pp  = CategoryModel::where('id', $pid)->value('parent_id');
                if (!$pp) break;
                $pid = (int)$pp;
            }
            if (!$anc) $roots[] = $s;
        }
        $trees = [];
        foreach ($roots as $r) { $trees[] = $this->exportTree($r); }

        $count = 0;
        lang_set($targetLang);
        try {
            foreach ($trees as $node) { $this->rebuildTree($node, $targetParent, $count); }
        } finally {
            lang_set($prev);
        }
        AdminLog::record('category_batchcopylang', '批量跨语言复制栏目[' . $targetLang . ']：' . $count . ' 个栏目（含内容）');
        return $this->ok('已复制 ' . $count . ' 个栏目（含内容）到 ' . $targetLang . '，请在目标语言中翻译', (string)url('admin/category/index'));
    }

    /**
     * 在源语言导出栏目整棵子树（结构 + 各栏目内容），供跨语言重建
     */
    protected function exportTree($cat)
    {
        if (!is_array($cat)) $cat = $cat->toArray(); // 兼容模型 / 数组
        $node = [
            'cat'      => $cat,
            'contents' => [],
            'children' => [],
        ];
        $node['contents'] = \app\common\model\Content::where('catid', $cat['id'])->select()->toArray();
        foreach (CategoryModel::where('parent_id', $cat['id'])->order('sort', 'asc')->select() as $ch) {
            $node['children'][] = $this->exportTree($ch);
        }
        return $node;
    }

    /**
     * 在目标语言重建一棵子树（递归，parent_id 逐层挂到新 id，保持层级）
     */
    protected function rebuildTree($node, $tgtParent, &$count)
    {
        $src = $node['cat'];
        $new = CategoryModel::create([
            'parent_id' => $tgtParent, 'model_id' => $src['model_id'],
            'cat_model_id' => $src['cat_model_id'] ?? 0,
            'name' => $src['name'], 'ename' => $src['ename'], 'type' => $src['type'],
            'external_url' => $src['external_url'], 'seo_title' => $src['seo_title'],
            'keywords' => $src['keywords'], 'description' => $src['description'],
            'image' => $src['image'], 'content' => $src['content'],
            'is_show' => $src['is_show'], 'is_nav' => $src['is_nav'], 'sort' => $src['sort'],
            'pagesize' => (int)($src['pagesize'] ?? 10),
        ]);
        $count++;
        $fields = ['model_id', 'title', 'short_title', 'author', 'source', 'thumb', 'image',
                   'keywords', 'description', 'content', 'extend', 'is_top', 'is_recommend', 'is_hot', 'sort', 'create_time'];
        foreach ($node['contents'] as $row) {
            $d = [];
            foreach ($fields as $f) { $d[$f] = isset($row[$f]) ? $row[$f] : ''; }
            $d['catid'] = $new['id'];
            $d['status'] = 1;
            $d['views'] = 0;
            $d['static_path'] = '';
            \app\common\model\Content::create($d);
        }
        foreach ($node['children'] as $ch) { $this->rebuildTree($ch, $new['id'], $count); }
    }
}
