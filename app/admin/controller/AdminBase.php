<?php
// +----------------------------------------------------------------------
// | 后台控制器基类
// | 所有后台控制器继承本类，统一注入后台通用数据（含按角色过滤的菜单）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\BaseController;
use app\common\model\AdminRole;
use app\common\model\Category as CategoryModel;
use think\facade\View;

class AdminBase extends BaseController
{
    // 菜单是否来自数据库树（yy_admin_menu，v3.22 拖动排序/改层级/改名）
    protected $menuFromDb = false;
    // 后台菜单树（initialize 中由 loadMenus() 赋值）
    protected $menus = [];

    /**
     * 后台默认菜单（云优风格：左侧导航）
     * 首次访问菜单管理时作为种子导入 yy_admin_menu，此后以数据库树为准
     */
    public static function defaultMenus()
    {
        return [
        'index'   => ['name' => '控制台', 'icon' => 'home', 'url' => 'admin/index/index', 'child' => []],
        'content' => [
            'name' => '内容管理', 'icon' => 'edit', 'url' => '',
            'child' => [
                ['name' => '栏目管理', 'url' => 'admin/category/index'],
                ['name' => '内容管理', 'url' => 'admin/content/index'],
                ['name' => '栏目模型', 'url' => 'admin/cat_model/index'],
                ['name' => '内容模型', 'url' => 'admin/model/index'],
                ['name' => '留言板', 'url' => 'admin/board/index'],
                ['name' => '留言管理', 'url' => 'admin/message/index'],
                ['name' => '评论管理', 'url' => 'admin/comment/index'],
                ['name' => '会员管理', 'url' => 'admin/member_admin/index'],
                ['name' => '会员设置', 'url' => 'admin/member_admin/setting'],
            ],
        ],
        'shop'    => [
            'name' => '商城管理', 'icon' => 'shopping-cart', 'url' => '',
            'child' => [
                ['name' => '商品价格', 'url' => 'admin/shop/product'],
                ['name' => '订单管理', 'url' => 'admin/shop/order'],
                ['name' => '售后管理', 'url' => 'admin/refund/index'],
                ['name' => '钱包管理', 'url' => 'admin/wallet/index'],
                ['name' => '支付设置', 'url' => 'admin/shop/setting'],
            ],
        ],
        'service' => [
            'name' => '客服系统', 'icon' => 'comment', 'url' => '',
            'child' => [
                ['name' => '客服会话', 'url' => 'admin/service/index'],
            ],
        ],
        'tpl'     => [
            'name' => '模板与样式', 'icon' => 'layout', 'url' => '',
            'child' => [
                ['name' => '自定义块', 'url' => 'admin/block/index'],
                ['name' => '广告位', 'url' => 'admin/ad/index'],
                ['name' => '模板管理', 'url' => 'admin/template/index'],
                ['name' => '静态页生成', 'url' => 'admin/static_page/index'],
            ],
        ],
        'expand'  => [
            'name' => '扩展功能', 'icon' => 'grid', 'url' => '',
            'child' => [
                ['name' => '浏览管理', 'url' => 'admin/visit/index'],
                ['name' => '友情链接', 'url' => 'admin/link/index'],
                ['name' => '素材库', 'url' => 'admin/asset/index'],
                ['name' => '搜索日志', 'url' => 'admin/search_log/index'],
                ['name' => '采集器', 'url' => 'admin/collect/index'],
                ['name' => '大数据分表', 'url' => 'admin/shard/index'],
                ['name' => '收录推送', 'url' => 'admin/push/index'],
                ['name' => '推送管理', 'url' => 'admin/push_site/index'],
                ['name' => '管理员', 'url' => 'admin/admin/index'],
                ['name' => '角色管理', 'url' => 'admin/admin_role/index'],
            ],
        ],
        'system'  => [
            'name' => '系统设置', 'icon' => 'setting', 'url' => '',
            'child' => [
                ['name' => '网站设置', 'url' => 'admin/setting/index'],
                ['name' => '语言版本', 'url' => 'admin/language/index'],
                ['name' => '菜单管理', 'url' => 'admin/menu/index'],
                ['name' => '日志管理', 'url' => 'admin/log/index'],
                ['name' => '数据备份', 'url' => 'admin/backup/index'],
                ['name' => '文件完整性', 'url' => 'admin/check/index'],
                ['name' => '缓存管理', 'url' => 'admin/cache/index'],
            ],
        ],
        ];
    }

    /**
     * 加载菜单树：优先 yy_admin_menu（拖动排序/改层级/改名持久化）
     * 表为空或不存在时回退默认菜单
     */
    protected function loadMenus()
    {
        try {
            $rows = \think\facade\Db::name('admin_menu')->select()->toArray();
        } catch (\Throwable $e) {
            return static::defaultMenus();
        }
        if (!$rows) return static::defaultMenus();
        $this->menuFromDb = true;
        // 内存分组：组按 sort 升序、组内子项按 sort 升序（避免整表排序跨组交错）
        usort($rows, function ($a, $b) {
            if ((int)$a['sort'] !== (int)$b['sort']) return (int)$a['sort'] - (int)$b['sort'];
            return (int)$a['id'] - (int)$b['id'];
        });
        $menus  = [];
        $gIndex = []; // 组行id => key
        foreach ($rows as $r) {
            if ((int)$r['pid'] === 0) {
                $key = trim((string)$r['key']) !== '' ? trim((string)$r['key']) : ('g' . $r['id']);
                $menus[$key] = [
                    'name'  => (string)$r['name'],
                    'icon'  => (string)$r['icon'],
                    'url'   => (string)$r['url'],
                    'child' => [],
                    '_id'   => (int)$r['id'],
                    '_sort' => (int)$r['sort'],
                ];
                $gIndex[(int)$r['id']] = $key;
            }
        }
        foreach ($rows as $r) {
            if ((int)$r['pid'] > 0) {
                $pk = (int)$r['pid'];
                if (isset($gIndex[$pk], $menus[$gIndex[$pk]])) {
                    $menus[$gIndex[$pk]]['child'][] = [
                        'name'  => (string)$r['name'],
                        'url'   => (string)$r['url'],
                        '_id'   => (int)$r['id'],
                        '_sort' => (int)$r['sort'],
                    ];
                }
            }
        }
        return $menus;
    }

    protected function initialize()
    {
        parent::initialize();
        // 菜单树优先从数据库读取（v3.22 拖动排序/改层级/改名）；空表回退默认菜单
        $this->menus = $this->loadMenus();
        // 判断后台入口路径
        $appPath = request()->root();
        // 当前语言与语言列表（顶部语言切换，仅已启用语言）
        $langs = \app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->select();
        $curLang = session('admin_lang') ?: default_lang();
        if (!in_array($curLang, lang_codes())) {
            $curLang = default_lang();
        }

        // 管理员站点/语言范围（v3.20）：非超管按 site_scope（JSON 语言代码数组）过滤语言切换
        $role = session('admin_role') ?: 'admin';
        $siteScope = [];
        if ($role !== 'super') {
            try {
                $me = \app\common\model\Admin::where('id', (int)session('admin_id'))->find();
                if ($me && trim((string)$me['site_scope']) !== '') {
                    $siteScope = array_values(array_filter(array_map('trim', json_decode((string)$me['site_scope'], true) ?: [])));
                    $langs = $langs->filter(function ($l) use ($siteScope) {
                        return in_array($l['code'], $siteScope, true);
                    })->values();
                }
            } catch (\Throwable $e) {}
        }
        if (!$langs->count()) {
            $langs = \app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->limit(1)->select();
        }
        if (!in_array($curLang, array_column($langs->toArray(), 'code'))) {
            $curLang = $langs->count() ? $langs[0]['code'] : default_lang();
        }
        session('admin_lang', $curLang);

        // 当前管理员可管理站点/语言列表（供顶栏与页面提示）
        $adminSites = [['key' => 'main', 'name' => '主站']];
        try {
            if ((int)get_config('site.multi', 0) === 1) {
                foreach (\think\facade\Db::name('site')->where('status', 1)->order('sort', 'asc')->select() as $st) {
                    $adminSites[] = ['key' => (string)$st['alias'], 'name' => $st['name'] . '（' . $st['alias'] . '）'];
                }
            }
            foreach ($langs->column('code') as $code) {
                $hit = false;
                foreach ($adminSites as $s) { if ($s['key'] === $code) { $hit = true; break; } }
                if (!$hit) $adminSites[] = ['key' => $code, 'name' => '语言站 ' . $code];
            }
        } catch (\Throwable $e) {}
        if ($siteScope) {
            $adminSites = array_values(array_filter($adminSites, function ($s) use ($siteScope) {
                return $s['key'] === 'main' ? in_array(default_lang(), $siteScope, true) : in_array($s['key'], $siteScope, true);
            }));
        }

        // 当前管理员角色权限 → 过滤菜单
        $rules = $this->roleRules($role);
        $menus = $this->filterMenus($this->menus, $rules);
        // 菜单管理：按全局显隐配置过滤（admin.menu_hidden）
        $hidden = json_decode((string)get_config('admin.menu_hidden', '[]'), true);
        if (is_array($hidden) && $hidden) $menus = $this->applyHiddenMenus($menus, $hidden);
        // 系统功能开关：关闭时隐藏对应后台菜单（v3.16）
        $menus = $this->applySystemSwitches($menus);
        // 菜单管理：按全局排序配置重排（admin.menu_sort，v3.21.2；有数据库菜单树时以 DB sort 为准，v3.22）
        if (!$this->menuFromDb) $menus = $this->applyMenuSort($menus);

        // 站群管理（v3.18 常显，开关状态在站群页提示；开启后站点才生效）
        $hasSite = false;
        foreach ($menus['system']['child'] ?? [] as $mi) {
            if ($mi['url'] === 'admin/site/index') { $hasSite = true; break; }
        }
        if (!$hasSite && $this->canCtrl('admin/site/index', $rules)) {
            $menus['system']['child'][] = ['name' => '站群管理', 'url' => 'admin/site/index'];
        }

        View::assign([
            'admin_menus' => $menus,
            'admin_name'  => session('admin_name'),
            'admin_nick'  => session('admin_nick'),
            'admin_role'  => $role,
            'app_root'    => $appPath,
            'controller'  => strtolower(request()->controller()),
            'active_url'  => strtolower(request()->pathinfo()),
            'admin_langs' => $langs,
            'admin_lang'  => $curLang,
            'admin_sites' => $adminSites,
            'admin_session_timeout' => (int)get_config('base.session_timeout', 60),
            // v3.21 侧边栏滚动保持：后台改为「外层框架 + 右侧 iframe 内容区」
            // is_frame=1 时 layout 只渲染内容块（iframe 内）；外层框架 iframe 指向 iframe_src（强制带 frame=1）
            'is_frame'  => (int)input('get.frame', 0),
            'iframe_src'=> (function () {
                $params = request()->param();
                $params['frame'] = 1;
                return request()->baseUrl() . ($params ? '?' . http_build_query($params) : '');
            })(),
        ]);
    }

    /**
     * 当前角色允许的控制器白名单（'*' 表示全部）
     */
    protected function roleRules($role)
    {
        if ($role === 'super') return ['*'];
        $row = AdminRole::where('code', $role)->find();
        if (!$row) return [];
        return AdminRole::parseRules((string)$row['rules']);
    }

    /**
     * 读取当前角色的语言化数据范围配置（cat_ids / board_ids，JSON 按语言分组）
     * 返回某语言下允许的 id 数组；null = 未配置该语言（不限制，全部）
     */
    protected function roleScope($field)
    {
        $role = session('admin_role') ?: 'admin';
        if ($role === 'super') return null;
        $row = AdminRole::where('code', $role)->find();
        if (!$row) return null;
        $lang = session('admin_lang') ?: default_lang();
        $map  = json_decode((string)$row[$field], true);
        $ids  = isset($map[$lang]) ? array_filter(array_map('intval', (array)$map[$lang]), fn($v) => $v > 0) : [];
        if (!$ids) return null;
        return array_values($ids);
    }

    /**
     * 数据范围：可管理栏目 id（含全部后代）；null = 全部栏目
     */
    protected function dataScopeCats()
    {
        $ids = $this->roleScope('cat_ids');
        if ($ids === null) return null;
        $out = [];
        foreach ($ids as $id) {
            foreach (CategoryModel::getChildIds($id) as $cid) $out[$cid] = $cid;
        }
        return $out ? array_values($out) : [];
    }

    /**
     * 数据范围：可查看留言板 id；null = 全部留言板
     */
    protected function dataScopeBoards()
    {
        return $this->roleScope('board_ids');
    }

    /**
     * 当前管理员能否管理某栏目（super/未限制=true）
     */
    protected function canManageCat($catid)
    {
        $scope = $this->dataScopeCats();
        return $scope === null || in_array((int)$catid, $scope);
    }

    /**
     * 当前管理员能否查看某留言板
     */
    protected function canManageBoard($boardId)
    {
        $scope = $this->dataScopeBoards();
        return $scope === null || in_array((int)$boardId, $scope);
    }

    /**
     * 按数据范围过滤栏目树（只保留权限栏目及其子级）
     */
    protected function filterCategoryTree($tree, $scope)
    {
        $out = [];
        foreach ($tree as $item) {
            if (in_array($item['id'], $scope)) {
                if (!empty($item['children'])) {
                    $item['children'] = $this->filterCategoryTree($item['children'], $scope);
                }
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * 菜单 url 对应的控制器段是否被允许
     */
    protected function canCtrl($url, $rules)
    {
        if (in_array('*', $rules)) return true;
        $seg = explode('/', (string)$url);
        $controller = strtolower($seg[1] ?? '');
        // 控制台 / 登录 / 上传 总允许
        if (in_array($controller, ['index', 'login', 'upload', 'ueditor'])) return true;
        return in_array($controller, $rules);
    }

    /**
     * 按角色过滤菜单（无权限的组/项隐藏）
     */
    protected function filterMenus($menus, $rules)
    {
        $out = [];
        foreach ($menus as $k => $g) {
            if (!empty($g['child'])) {
                $ch = [];
                foreach ($g['child'] as $item) {
                    if ($this->canCtrl($item['url'], $rules)) $ch[] = $item;
                }
                if ($ch) { $g['child'] = $ch; $out[$k] = $g; }
            } else {
                if ($this->canCtrl($g['url'], $rules)) $out[$k] = $g;
            }
        }
        return $out;
    }

    /**
     * 按菜单管理显隐配置过滤（隐藏 url 在 admin.menu_hidden 中的菜单项）
     * 支持组级隐藏：标记 "group:{组key}" 表示整个一级菜单组隐藏
     */
    protected function applyHiddenMenus($menus, $hidden)    {
        foreach ($menus as $k => $g) {
            // 一级菜单组开关：group:{key}
            if (in_array('group:' . $k, $hidden, true)) { unset($menus[$k]); continue; }
            if (!empty($g['child'])) {
                $ch = [];
                foreach ($g['child'] as $item) {
                    if (!in_array($item['url'], $hidden, true)) $ch[] = $item;
                }
                $g['child'] = $ch;
                $menus[$k]  = $g;
            } else {
                if (in_array($g['url'], $hidden, true)) unset($menus[$k]);
            }
        }
        return $menus;
    }

    /**
     * 按菜单管理排序配置重排（admin.menu_sort）
     * 未配置排序值的项保持代码原始顺序（稳定排序）
     */
    protected function applyMenuSort($menus)
    {
        $cfg = json_decode((string)get_config('admin.menu_sort', '{}'), true);
        if (!is_array($cfg)) return $menus;
        $gOrder = isset($cfg['group']) && is_array($cfg['group']) ? $cfg['group'] : [];
        $iOrder = isset($cfg['item']) && is_array($cfg['item']) ? $cfg['item'] : [];
        if (!$gOrder && !$iOrder) return $menus;
        // 组间排序
        $idx = 0;
        $gArr = [];
        foreach ($menus as $k => $g) { $gArr[] = ['k' => $k, 'g' => $g, 'i' => $idx++]; }
        usort($gArr, function ($a, $b) use ($gOrder) {
            $sa = isset($gOrder[$a['k']]) ? (int)$gOrder[$a['k']] : 9999;
            $sb = isset($gOrder[$b['k']]) ? (int)$gOrder[$b['k']] : 9999;
            if ($sa !== $sb) return $sa - $sb;
            return $a['i'] - $b['i'];
        });
        $menus = [];
        foreach ($gArr as $e) {
            $k = $e['k'];
            $g = $e['g'];
            if (!empty($g['child'])) {
                $ci = 0;
                $cArr = [];
                foreach ($g['child'] as $item) { $cArr[] = ['item' => $item, 'i' => $ci++]; }
                usort($cArr, function ($a, $b) use ($iOrder) {
                    $sa = isset($iOrder[$a['item']['url']]) ? (int)$iOrder[$a['item']['url']] : 9999;
                    $sb = isset($iOrder[$b['item']['url']]) ? (int)$iOrder[$b['item']['url']] : 9999;
                    if ($sa !== $sb) return $sa - $sb;
                    return $a['i'] - $b['i'];
                });
                $g['child'] = array_column($cArr, 'item');
            }
            $menus[$k] = $g;
        }
        return $menus;
    }

    /**
     * 系统功能开关过滤菜单（v3.16）
     * 关闭对应功能时隐藏其后天菜单项：会员管理/会员设置、商城管理整组（含售后/钱包）、客服会话、评论管理
     */
    protected function applySystemSwitches($menus)
    {
        $switchMap = [
            'member.open'  => ['admin/member_admin/'],
            'shop.open'    => ['admin/shop/', 'admin/refund/', 'admin/wallet/'],
            'service.open' => ['admin/service/'],
            'comment.open' => ['admin/comment/'],
        ];
        foreach ($switchMap as $key => $prefixes) {
            if ((int)get_config($key, 1) === 1) continue;
            foreach ($menus as $gk => $g) {
                if (empty($g['child'])) continue;
                $ch = [];
                foreach ($g['child'] as $item) {
                    $hide = false;
                    foreach ($prefixes as $pfx) {
                        if (strpos((string)$item['url'], $pfx) === 0) { $hide = true; break; }
                    }
                    if (!$hide) $ch[] = $item;
                }
                if ($ch) { $g['child'] = $ch; $menus[$gk] = $g; } else { unset($menus[$gk]); }
            }
        }
        return $menus;
    }

    /**
     * 统一操作返回
     */
    protected function ok($msg = '操作成功', $url = ''): \think\response\Json
    {
        return json(['code' => 1, 'msg' => $msg, 'url' => $url]);
    }

    protected function fail($msg = '操作失败'): \think\response\Json
    {
        return json(['code' => 0, 'msg' => $msg]);
    }

    /**
     * 栏目树 + 内容模型 + 栏目模型（栏目相关表单共用，已按角色数据范围过滤）
     * @return array ['parents'=>树, 'models'=>内容模型, 'catModels'=>栏目模型]
     */
    protected function catFormData()
    {
        $parents = CategoryModel::getTree();
        $scope = $this->dataScopeCats();
        if ($scope !== null) $parents = $this->filterCategoryTree($parents, $scope);
        return [
            'parents'   => $parents,
            'models'    => \app\common\model\CmsModel::order('id')->select(),
            'catModels' => \app\common\model\CatModel::order('id')->select(),
        ];
    }

    /**
     * 指定语言下的栏目树（跨语言复制弹窗选择目标父栏目用）
     */
    public function langCats()
    {
        $lang = input('get.lang', default_lang());
        if (!in_array($lang, lang_codes())) {
            return json(['code' => 0, 'data' => []]);
        }
        $prev = lang_code();
        lang_set($lang);
        try {
            $tree = CategoryModel::getTree();
        } finally {
            lang_set($prev);
        }
        return json(['code' => 1, 'data' => $tree]);
    }

    /**
     * 校验目标语言下是否存在指定栏目
     */
    protected function targetCatExists($lang, $catId)
    {
        $prev = lang_code();
        lang_set($lang);
        try {
            $exists = (bool)CategoryModel::find($catId);
        } finally {
            lang_set($prev);
        }
        return $exists;
    }
}
