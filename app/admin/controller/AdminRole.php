<?php
// +----------------------------------------------------------------------
// | 后台角色管理控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminRole as RoleModel;
use app\common\model\Admin as AdminModel;
use app\common\model\AdminLog;
use app\common\model\Category as CategoryModel;
use app\common\model\MessageBoard;

class AdminRole extends AdminBase
{
    // 不在左侧菜单中的特殊权限（内容编辑用上传能力）
    protected $modules = [
        'upload'      => '图片/文件上传',
        'ueditor'     => '富文本上传',
    ];

    /**
     * 从左侧菜单自动派生的权限模块清单（控制器名 => 中文名）
     * 保证与 AdminBase::$menus 永远同步，新增菜单无需再手工改这里
     */
    protected function menuModules()
    {
        $out = [];
        foreach ($this->menus as $g) {
            foreach ($g['child'] ?? [] as $item) {
                $u = (string)($item['url'] ?? '');
                if (preg_match('#^admin/([a-z0-9_]+)/#i', $u, $m)) {
                    $ctrl = strtolower($m[1]);
                    $name = $item['name'];
                    if (isset($out[$ctrl])) {
                        // 同一控制器多个菜单项（如 商品价格/订单管理/支付设置）合并展示
                        if (strpos($out[$ctrl], $name) === false) $out[$ctrl] .= ' / ' . $name;
                    } else {
                        $out[$ctrl] = $name;
                    }
                }
            }
        }
        return $out;
    }

    public function index()
    {
        $list = RoleModel::order('sort', 'asc')->order('id', 'asc')->select();
        // 使用中的角色（被哪些管理员使用）
        $used = [];
        foreach (AdminModel::field('role,username,nickname')->select() as $a) {
            $used[$a['role']][] = $a['nickname'] ?: $a['username'];
        }
        foreach ($list as &$r) {
            $r['rules_arr'] = RoleModel::parseRules((string)$r['rules']);
            $r['used_by'] = $used[$r['code']] ?? [];
            // 当前语言数据范围概要（null=不限制全部；数组=仅这些栏目/留言板）
            $lang = session('admin_lang') ?: default_lang();
            $cm = json_decode((string)($r['cat_ids'] ?? ''), true);
            $bm = json_decode((string)($r['board_ids'] ?? ''), true);
            $r['scope_cats']   = isset($cm[$lang]) ? array_values(array_filter(array_map('intval', (array)$cm[$lang]), fn($v) => $v > 0)) : null;
            $r['scope_boards'] = isset($bm[$lang]) ? array_values(array_filter(array_map('intval', (array)$bm[$lang]), fn($v) => $v > 0)) : null;
            $r['is_super'] = $r['code'] === 'super';
        }
        unset($r);
        return view('admin_role/index', ['list' => $list, 'modules' => $this->menuModules() + $this->modules]);
    }

    public function edit($id = 0)
    {
        if (request()->isPost()) {
            $name   = trim((string)input('post.name', ''));
            $code   = trim((string)input('post.code', ''));
            $remark = trim((string)input('post.remark', ''));
            $rules  = input('post.rules/a', []);
            $status = (int)input('post.status', 1);
            $sort   = (int)input('post.sort', 0);
            $needAudit = (int)input('post.need_audit', 0);
            if ($name === '' || $code === '') return $this->fail('请填写角色名称与标识');
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $code)) return $this->fail('角色标识只能由字母、数字、下划线组成');
            $rules = $rules ? array_values(array_unique(array_map('strtolower', $rules))) : [];

            // 数据范围：按当前后台语言保存栏目范围 / 留言板范围
            $lang = session('admin_lang') ?: default_lang();
            $catIds   = array_values(array_unique(array_filter(array_map('intval', (array)input('post.cat_ids/a', [])), fn($v) => $v > 0)));
            $boardIds = array_values(array_unique(array_filter(array_map('intval', (array)input('post.board_ids/a', [])), fn($v) => $v > 0)));
            $mergeScope = function ($oldJson, $langCode, $newIds) {
                $map = $oldJson ? json_decode($oldJson, true) : [];
                if (!is_array($map)) $map = [];
                if ($newIds) { $map[$langCode] = $newIds; } else { unset($map[$langCode]); }
                return json_encode($map, JSON_UNESCAPED_UNICODE);
            };
            $role = $id ? RoleModel::find($id) : null;
            if ($id && !$role) return $this->fail('角色不存在');
            $oldCat = $role ? (string)($role['cat_ids'] ?? '') : '';
            $oldBrd = $role ? (string)($role['board_ids'] ?? '') : '';
            $catIdsJson   = $mergeScope($oldCat, $lang, $catIds);
            $boardIdsJson = $mergeScope($oldBrd, $lang, $boardIds);

            if ($id) {
                if ($role['is_system'] == 1 && $role['code'] === 'super') {
                    return $this->fail('超级管理员角色不可修改');
                }
                $role->save([
                    'name' => $name, 'code' => $code, 'remark' => $remark,
                    'rules' => json_encode($rules, JSON_UNESCAPED_UNICODE),
                    'cat_ids' => $catIdsJson, 'board_ids' => $boardIdsJson,
                    'status' => $status, 'sort' => $sort, 'need_audit' => $needAudit,
                ]);
                AdminLog::record('admin_role_edit', '编辑角色：' . $name);
            } else {
                if (RoleModel::where('code', $code)->find()) return $this->fail('角色标识已存在');
                RoleModel::create([
                    'code' => $code, 'name' => $name, 'remark' => $remark,
                    'rules' => json_encode($rules, JSON_UNESCAPED_UNICODE),
                    'cat_ids' => $catIdsJson, 'board_ids' => $boardIdsJson,
                    'status' => $status, 'sort' => $sort, 'is_system' => 0,
                    'need_audit' => $needAudit,
                ]);
                AdminLog::record('admin_role_add', '新增角色：' . $name);
            }
            return $this->ok('保存成功', (string)url('admin/admin_role/index'));
        }

        $info = $id ? RoleModel::find($id) : null;
        if ($info) {
            $info['rules_arr'] = RoleModel::parseRules((string)$info['rules']);
        } else {
            $info = ['id' => 0, 'name' => '', 'code' => '', 'remark' => '', 'sort' => 0, 'status' => 1, 'need_audit' => 0, 'rules_arr' => []];
        }
        // 当前语言栏目树 / 留言板 + 该语言已选范围
        $lang    = session('admin_lang') ?: default_lang();
        $cats    = CategoryModel::getTree();
        $boards  = MessageBoard::order('sort', 'asc')->order('id', 'asc')->select();
        $selCats = [];
        $selBoards = [];
        if ($info) {
            $cm = json_decode((string)($info['cat_ids'] ?? ''), true);
            $selCats = isset($cm[$lang]) ? array_map('intval', (array)$cm[$lang]) : [];
            $bm = json_decode((string)($info['board_ids'] ?? ''), true);
            $selBoards = isset($bm[$lang]) ? array_map('intval', (array)$bm[$lang]) : [];
        }
        return view('admin_role/edit', [
            'info' => $info, 'modules' => $this->menuModules() + $this->modules,
            'cats' => $cats, 'flatCats' => CategoryModel::getFlatList(),
            'boards' => $boards, 'selCats' => $selCats, 'selBoards' => $selBoards,
        ]);
    }

    public function delete($id = 0)
    {
        $role = RoleModel::find($id);
        if (!$role) return $this->fail('角色不存在');
        if ($role['is_system'] == 1) return $this->fail('内置角色不可删除');
        if (AdminModel::where('role', $role['code'])->count()) {
            return $this->fail('该角色下存在管理员，请先调整管理员角色后再删除');
        }
        $role->delete();
        AdminLog::record('admin_role_delete', '删除角色：' . $role['name']);
        return $this->ok('删除成功', (string)url('admin/admin_role/index'));
    }
}
