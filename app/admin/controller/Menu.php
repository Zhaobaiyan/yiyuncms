<?php
// +----------------------------------------------------------------------
// | 菜单管理控制器：侧边栏菜单显示/隐藏开关、拖动排序/改层级/行内改名（v3.22）
// | 菜单树持久化到 yy_admin_menu；显隐仍走 admin.menu_hidden 全局配置
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Db;

class Menu extends AdminBase
{
    /**
     * 当前隐藏的菜单 url 列表（全局配置 admin.menu_hidden，JSON 数组）
     */
    protected function hiddenList()
    {
        $v = json_decode((string)get_config('admin.menu_hidden', '[]'), true);
        return is_array($v) ? array_values(array_filter($v, 'is_string')) : [];
    }

    /**
     * 建表（幂等）+ 空表时从默认菜单导入种子
     */
    protected function ensureSeed()
    {
        try {
            $exists = Db::query("SHOW TABLES LIKE 'yy_admin_menu'");
            if (!$exists) {
                Db::execute("CREATE TABLE IF NOT EXISTS `yy_admin_menu` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `pid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '父级id，0=一级菜单组',
                  `key` varchar(30) NOT NULL DEFAULT '' COMMENT '一级组内部标识（如 content），仅组行使用',
                  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '显示名称',
                  `icon` varchar(30) NOT NULL DEFAULT '' COMMENT '图标（仅组行使用）',
                  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '路由地址（子项必填；组可为空）',
                  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '组内排序（数字越小越靠前）',
                  `create_time` datetime DEFAULT NULL,
                  `update_time` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_pid` (`pid`),
                  KEY `idx_key` (`key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台菜单树（拖动排序/改层级/改名）'");
            }
            $cnt = Db::name('admin_menu')->count();
            if ((int)$cnt > 0) return;
            $now  = date('Y-m-d H:i:s');
            $rows = [];
            $gs   = 0;
            foreach (static::defaultMenus() as $key => $g) {
                $gs++;
                $gid = null;
                // 组行
                Db::name('admin_menu')->insertGetId([
                    'pid' => 0, 'key' => $key, 'name' => $g['name'] ?? $key,
                    'icon' => $g['icon'] ?? '', 'url' => $g['url'] ?? '',
                    'sort' => $gs, 'create_time' => $now, 'update_time' => $now,
                ]);
                $gid = Db::name('admin_menu')->getLastInsID();
                $is  = 0;
                foreach ($g['child'] ?? [] as $item) {
                    $is++;
                    Db::name('admin_menu')->insert([
                        'pid' => $gid, 'key' => '', 'name' => $item['name'] ?? '',
                        'icon' => '', 'url' => $item['url'] ?? '',
                        'sort' => $is, 'create_time' => $now, 'update_time' => $now,
                    ]);
                }
            }
        } catch (\Throwable $e) {}
    }

    public function index()
    {
        $this->ensureSeed();
        $hidden = $this->hiddenList();
        // 读数据库菜单树（含 id/pid/sort），供拖拽/改名编辑
        $groups = [];
        try {
            $rows = Db::name('admin_menu')->select()->toArray();
        } catch (\Throwable $e) {
            $rows = [];
        }
        if ($rows) {
            // 内存分组：组按 sort 升序、组内子项按 sort 升序（避免整表排序跨组交错）
            usort($rows, function ($a, $b) {
                if ((int)$a['sort'] !== (int)$b['sort']) return (int)$a['sort'] - (int)$b['sort'];
                return (int)$a['id'] - (int)$b['id'];
            });
            $gIdx = [];
            foreach ($rows as $r) {
                if ((int)$r['pid'] === 0) {
                    $key = trim((string)$r['key']) !== '' ? trim((string)$r['key']) : ('g' . $r['id']);
                    $groups[$key] = [
                        'id' => (int)$r['id'], 'pid' => 0, 'key' => $key,
                        'name' => (string)$r['name'], 'icon' => (string)$r['icon'],
                        'url' => (string)$r['url'], 'sort' => (int)$r['sort'],
                        'child' => [],
                    ];
                    $gIdx[(int)$r['id']] = $key;
                }
            }
            foreach ($rows as $r) {
                if ((int)$r['pid'] > 0 && isset($gIdx[(int)$r['pid']])) {
                    $groups[$gIdx[(int)$r['pid']]]['child'][] = [
                        'id' => (int)$r['id'], 'pid' => (int)$r['pid'],
                        'name' => (string)$r['name'], 'url' => (string)$r['url'],
                        'sort' => (int)$r['sort'],
                    ];
                }
            }
        } else {
            // 兜底：默认菜单（无 id，只读展示）
            foreach (static::defaultMenus() as $key => $g) {
                $groups[$key] = [
                    'id' => 0, 'pid' => 0, 'key' => $key,
                    'name' => $g['name'] ?? $key, 'icon' => $g['icon'] ?? '',
                    'url' => $g['url'] ?? '', 'sort' => 0,
                    'child' => array_map(function ($it) {
                        return ['id' => 0, 'pid' => 0, 'name' => $it['name'] ?? '', 'url' => $it['url'] ?? '', 'sort' => 0];
                    }, $g['child'] ?? []),
                ];
            }
        }
        return view('menu/index', [
            'groups' => $groups,
            'hidden' => $hidden,
        ]);
    }

    /**
     * 整树保存（拖动排序/改层级/行内改名后调用，POST tree=JSON 数组）
     * tree: [{id,pid,key,name,icon,url,sort}]，按数组顺序重写各组内 sort
     */
    public function savetree()
    {
        $tree = input('post.tree', '', 'trim');
        $tree = json_decode($tree, true);
        if (!is_array($tree) || !$tree) return $this->fail('参数错误');
        // 收集有效组 id
        $validIds = [];
        foreach ($tree as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id > 0) $validIds[$id] = true;
        }
        Db::startTrans();
        try {
            $now  = date('Y-m-d H:i:s');
            foreach ($tree as $r) {
                $id = (int)($r['id'] ?? 0);
                if ($id <= 0) continue;
                $pid = (int)($r['pid'] ?? 0);
                if ($pid !== 0 && !isset($validIds[$pid])) $pid = 0; // 父级不存在则视为一级
                $data = [
                    'pid'         => $pid,
                    'name'        => trim((string)($r['name'] ?? '')) !== '' ? trim((string)$r['name']) : Db::raw('name'),
                    'url'         => (string)($r['url'] ?? ''),
                    'icon'        => (string)($r['icon'] ?? ''),
                    'sort'        => (int)($r['sort'] ?? 0),
                    'update_time' => $now,
                ];
                if ($pid === 0) {
                    $data['key'] = trim((string)($r['key'] ?? '')) !== '' ? trim((string)$r['key']) : '';
                }
                Db::name('admin_menu')->where('id', $id)->update($data);
            }
            Db::commit();
            AdminLog::record('menu_savetree', '菜单拖动排序/改层级/改名已保存');
            return $this->ok('保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->fail('保存失败：' . $e->getMessage());
        }
    }

    /**
     * 新增菜单（POST pid/key/name/icon/url；pid=0 新增一级菜单组，pid>0 新增该组子菜单）
     */
    public function add()
    {
        $pid  = (int)input('post.pid', 0);
        $name = trim((string)input('post.name', ''));
        if ($name === '') return $this->fail('请填写菜单名称');
        $key  = trim((string)input('post.key', ''));
        $icon = trim((string)input('post.icon', ''));
        $url  = trim((string)input('post.url', ''));
        $now  = date('Y-m-d H:i:s');
        if ($pid === 0) {
            // 一级菜单组：内部标识 key 唯一
            if ($key === '') $key = 'menu' . time();
            if (Db::name('admin_menu')->where('key', $key)->find()) {
                return $this->fail('内部标识已存在，请换一个');
            }
            $max = (int)Db::name('admin_menu')->where('pid', 0)->max('sort');
            Db::name('admin_menu')->insert([
                'pid' => 0, 'key' => $key, 'name' => $name, 'icon' => $icon,
                'url' => $url, 'sort' => $max + 1, 'create_time' => $now, 'update_time' => $now,
            ]);
        } else {
            $parent = Db::name('admin_menu')->find($pid);
            if (!$parent || (int)$parent['pid'] !== 0) return $this->fail('父级菜单不存在');
            if ($url === '') return $this->fail('子菜单请填写路由地址');
            $max = (int)Db::name('admin_menu')->where('pid', $pid)->max('sort');
            Db::name('admin_menu')->insert([
                'pid' => $pid, 'key' => '', 'name' => $name, 'icon' => '',
                'url' => $url, 'sort' => $max + 1, 'create_time' => $now, 'update_time' => $now,
            ]);
        }
        AdminLog::record('menu_add', '新增菜单：' . $name);
        return $this->ok('已添加，刷新侧边栏生效');
    }

    /**
     * 删除菜单（POST id；一级菜单删除时连带删除其全部子菜单）
     */
    public function del()
    {
        $id = (int)input('post.id', 0);
        if ($id <= 0) return $this->fail('参数错误');
        $row = Db::name('admin_menu')->find($id);
        if (!$row) return $this->fail('菜单不存在');
        Db::startTrans();
        try {
            Db::name('admin_menu')->where('id', $id)->delete();
            Db::name('admin_menu')->where('pid', $id)->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->fail('删除失败：' . $e->getMessage());
        }
        AdminLog::record('menu_del', '删除菜单：' . $row['name']);
        return $this->ok('已删除，刷新侧边栏生效');
    }

    /**
     * 单条排序保存（数字输入框，POST id, val）
     */
    public function sortone()
    {
        $id  = (int)input('post.id', 0);
        $val = (int)input('post.val', 0);
        if ($id <= 0 || $val < 0) return $this->fail('参数错误');
        Db::name('admin_menu')->where('id', $id)->update([
            'sort' => $val, 'update_time' => date('Y-m-d H:i:s'),
        ]);
        AdminLog::record('menu_sort', '菜单排序：id=' . $id . ' = ' . $val);
        return $this->ok('排序已保存');
    }

    /**
     * 菜单排序（POST type=group|item, key, val）——旧配置接口，保留兼容
     */
    public function sort()
    {
        $type = (string)input('post.type', '');
        $key  = (string)input('post.key', '');
        $val  = (int)input('post.val', 0);
        if (!in_array($type, ['group', 'item'], true) || $key === '' || $val < 0) {
            return $this->fail('参数错误');
        }
        $cfg = json_decode((string)get_config('admin.menu_sort', '{}'), true);
        if (!is_array($cfg)) $cfg = [];
        $cfg[$type][$key] = $val;
        set_config('admin.menu_sort', json_encode($cfg, JSON_UNESCAPED_UNICODE));
        AdminLog::record('menu_sort', '菜单排序：' . $type . ' / ' . $key . ' = ' . $val);
        return $this->ok('排序已保存');
    }

    /**
     * 切换菜单项显隐（POST url）——配置 admin.menu_hidden
     */
    public function toggle()
    {
        $url = (string)input('post.url', '');
        if ($url === '') return $this->fail('参数错误');
        $hidden = $this->hiddenList();
        $idx = array_search($url, $hidden, true);
        if ($idx !== false) {
            unset($hidden[$idx]);
            $msg = '已显示';
        } else {
            $hidden[] = $url;
            $msg = '已隐藏';
        }
        set_config('admin.menu_hidden', json_encode(array_values($hidden), JSON_UNESCAPED_UNICODE));
        AdminLog::record('menu_toggle', $msg . '菜单：' . $url);
        return $this->ok($msg . '，刷新侧边栏生效');
    }
}
