<?php
// +----------------------------------------------------------------------
// | 应用公共文件
// +----------------------------------------------------------------------
// | 全局公共函数：多语言 / 全局配置 / 站点设置 / 链接 / 设备识别
// | 前台模板标签与各控制器均可直接调用
// +----------------------------------------------------------------------

if (!function_exists('clear_site_cache')) {
    /**
     * 清空站群相关缓存（站点增删改/开关切换后调用）
     */
    function clear_site_cache()
    {
        \think\facade\Cache::delete('yy_site_hosts');
        \think\facade\Cache::delete('yy_site_paths');
        \think\facade\Cache::delete('yy_site_langs');
        \think\facade\Cache::delete('yy_site_default');
        \think\facade\Cache::delete('yy_lang_codes');
        \think\facade\Cache::delete('yy_default_lang');
        \think\facade\Cache::delete('yy_lang_tpldir');
    }
}

if (!function_exists('site_match')) {
    /**
     * 站群：匹配当前请求命中的站点（v3.13 域名模式 / v3.18 路径模式）
     * 站群开关关闭或未命中任何站点 → 返回 null（单站点模式）
     * 1) 路径模式（mode=path）：URL 首段命中站点 alias，如 newcms.com/shenyang/ → 沈阳站
     * 2) 域名模式（mode=domain）：Host 命中站点 domain，支持精确 newcms.com / newcms.com:8080、通配 *.example.com
     * @return array|null 站点记录
     */
    function site_match()
    {
        try {
            $req = request();
            $injected = $req->middleware('_cms_site');
            if ($injected) return $injected;
        } catch (\Throwable $e) {
        }
        if ((int)get_config('site.multi', 0) !== 1) return null;

        // 1) 路径模式：URL 首段命中站点 alias
        $path = trim((string)request()->pathinfo(), '/');
        $seg  = strtolower(strtok($path, '/'));
        if ($seg !== '') {
            $pmap = \think\facade\Cache::get('yy_site_paths');
            if (!is_array($pmap)) {
                $pmap = [];
                try {
                    foreach (\think\facade\Db::name('site')->where('status', 1)->order('sort', 'asc')->select() as $r) {
                        if ((string)($r['mode'] ?? 'domain') === 'path' && trim((string)$r['alias']) !== '') {
                            $pmap[strtolower(trim((string)$r['alias']))] = $r;
                        }
                    }
                } catch (\Throwable $e) {
                    $pmap = [];
                }
                \think\facade\Cache::set('yy_site_paths', $pmap, 3600);
            }
            if (isset($pmap[$seg])) return $pmap[$seg];
        }

        // 2) 域名模式（原逻辑）
        $host = strtolower(trim((string)request()->host(), '/'));
        if ($host === '') return null;
        $map = \think\facade\Cache::get('yy_site_hosts');
        if (!is_array($map)) {
            $map = [];
            try {
                foreach (\think\facade\Db::name('site')->where('status', 1)->order('sort', 'asc')->select() as $r) {
                    $map[strtolower(trim((string)$r['domain']))] = $r;
                }
            } catch (\Throwable $e) {
                $map = [];
            }
            \think\facade\Cache::set('yy_site_hosts', $map, 3600);
        }
        if (!$map) return null;
        // 精确匹配（含端口）
        if (isset($map[$host])) return $map[$host];
        // 通配匹配 *.example.com
        foreach ($map as $domain => $row) {
            $domain = (string)$domain;
            if (strpos($domain, '*.') === 0) {
                $base = substr($domain, 2);
                if ($host !== $base && (strpos($host, $base) !== false) && substr($host, -strlen($base)) === $base) {
                    return $row;
                }
            }
        }
        return null;
    }
}

if (!function_exists('copy_dir')) {
    /**
     * 递归复制目录（站群/语言模板文件夹复制用）
     * @param string $src 源目录绝对路径
     * @param string $dst 目标目录绝对路径
     * @return bool
     */
    function copy_dir($src, $dst)
    {
        $src = rtrim((string)$src, '/\\');
        $dst = rtrim((string)$dst, '/\\');
        if (!is_dir($src)) return false;
        if (!is_dir($dst)) @mkdir($dst, 0755, true);
        if (!is_dir($dst)) return false;
        foreach (scandir($src) as $f) {
            if ($f === '.' || $f === '..') continue;
            $s = $src . DIRECTORY_SEPARATOR . $f;
            $d = $dst . DIRECTORY_SEPARATOR . $f;
            if (is_dir($s)) {
                copy_dir($s, $d);
            } else {
                @copy($s, $d);
            }
        }
        return true;
    }
}

if (!function_exists('lang_clone_tables')) {
    /**
     * 克隆语言数据表（站群新建 / 多语言新建共用）
     * 动态枚举 {prefix}{srcLang}_ 前缀的全部表，逐个 CREATE TABLE LIKE + 可选 INSERT SELECT 数据
     * @param string $code     目标语言代码（如 shenyang）
     * @param string $srcLang  源语言代码（如 cn）
     * @param bool   $withData 是否连数据一起复制（站群站点=true，多语言=仅结构+设置/留言板）
     * @return array 复制的表名列表
     * @throws \RuntimeException
     */
    function lang_clone_tables($code, $srcLang, $withData = false)
    {
        $prefix = (string)(\think\facade\Config::get('database.connections.mysql.prefix') ?: 'yy_');
        $code = strtolower(trim($code));
        $srcLang = strtolower(trim($srcLang));
        if (!preg_match('/^[a-z]{2,20}$/', $code) || !preg_match('/^[a-z]{2,20}$/', $srcLang)) {
            throw new \RuntimeException('语言代码不合法');
        }
        $done = [];
        try {
            $rows = \think\facade\Db::query(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?",
                [$prefix . $srcLang . '\_%']
            );
            $srcTables = [];
            foreach ($rows as $r) {
                $t = isset($r['table_name']) ? (string)$r['table_name'] : '';
                // 排除源语言下的归档/日志分表？日志/搜索日志也克隆（站群完整独立）
                $srcTables[] = $t;
            }
            sort($srcTables);
            foreach ($srcTables as $srcTbl) {
                $suffix = substr($srcTbl, strlen($prefix . $srcLang));
                $dstTbl = $prefix . $code . $suffix;
                \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `{$dstTbl}` LIKE `{$srcTbl}`");
                if ($withData) {
                    // 克隆数据（内容/栏目/留言/块/广告/链接/模型/设置等全套）
                    \think\facade\Db::execute("INSERT INTO `{$dstTbl}` SELECT * FROM `{$srcTbl}`");
                } else {
                    // 多语言新增：仅复制站点设置 + 默认留言板（保持新语言从空开始可运行）
                    if ($suffix === '_setting') {
                        \think\facade\Db::execute(
                            "INSERT INTO `{$dstTbl}` (`name`,`value`,`create_time`,`update_time`)
                             SELECT `name`,`value`,UNIX_TIMESTAMP(),UNIX_TIMESTAMP() FROM `{$srcTbl}`"
                        );
                    } elseif ($suffix === '_message_board') {
                        $cols = ['id','name','title','description','fields','status','captcha','show_public','default_public','sort','create_time','update_time'];
                        \think\facade\Db::execute(
                            "INSERT INTO `{$dstTbl}` (`" . implode('`,`', $cols) . "`)
                             SELECT `" . implode('`,`', $cols) . "`,UNIX_TIMESTAMP(),UNIX_TIMESTAMP() FROM `{$srcTbl}`"
                        );
                    }
                }
                $done[] = $dstTbl;
            }
            if (!$done) {
                throw new \RuntimeException('源语言 ' . $srcLang . ' 无任何数据表');
            }
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException) throw $e;
            throw new \RuntimeException('数据表克隆失败：' . $e->getMessage());
        }
        return $done;
    }
}

if (!function_exists('site_langs')) {
    /**
     * 站群：当前站点启用的语言白名单（未命中站点/未配置返回 null=全部）
     * @return array|null
     */
    function site_langs()
    {
        $site = site_match();
        if (!$site || empty($site['langs'])) return null;
        $arr = json_decode((string)$site['langs'], true);
        if (!is_array($arr)) return null;
        $arr = array_values(array_filter(array_map('strval', $arr), fn($v) => $v !== ''));
        return $arr ?: null;
    }
}

if (!function_exists('lang_codes')) {
    /**
     * 已启用的语言代码列表（如 ['cn','en']），含默认语言
     * 站群模式（命中站点且站点配置语言白名单）→ 返回站点白名单∩全局启用语言
     */
    function lang_codes()
    {
        // 站群：站点语言白名单
        $site = site_match();
        if ($site) {
            $sid = (int)$site['id'];
            $map = \think\facade\Cache::get('yy_site_langs');
            if (is_array($map) && array_key_exists($sid, $map)) return $map[$sid];
            $whitelist = site_langs();
            if ($whitelist) {
                $global = [];
                try {
                    $global = \app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->column('code');
                } catch (\Throwable $e) {
                    $global = [];
                }
                $codes = array_values(array_intersect($whitelist, $global));
                if (!$codes) $codes = [$site['default_lang'] ?: 'cn'];
                if (!is_array($map)) $map = [];
                $map[$sid] = $codes;
                \think\facade\Cache::set('yy_site_langs', $map, 3600);
                return $codes;
            }
            $codes = \think\facade\Cache::get('yy_lang_codes');
            if ($codes) return $codes;
        }
        $codes = \think\facade\Cache::get('yy_lang_codes');
        if ($codes) return $codes;
        try {
            $codes = \app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->column('code');
        } catch (\Throwable $e) {
            $codes = [];
        }
        if (empty($codes)) {
            $codes = ['cn'];
        }
        \think\facade\Cache::set('yy_lang_codes', $codes, 3600);
        return $codes;
    }
}

if (!function_exists('default_lang')) {
    /**
     * 默认语言代码（优先 is_default=1 的已启用语言，否则取排序第一个）
     * 站群模式（命中站点）→ 站点 default_lang（须在站点白名单/全局启用语言内）
     */
    function default_lang()
    {
        $site = site_match();
        if ($site) {
            $sid = (int)$site['id'];
            $map = \think\facade\Cache::get('yy_site_default');
            if (is_array($map) && array_key_exists($sid, $map)) return $map[$sid];
            $codes = lang_codes();
            $dl = (string)$site['default_lang'];
            $d  = in_array($dl, $codes) ? $dl : reset($codes);
            if (!is_array($map)) $map = [];
            $map[$sid] = $d;
            \think\facade\Cache::set('yy_site_default', $map, 3600);
            return $d;
        }
        $d = \think\facade\Cache::get('yy_default_lang');
        if ($d) return $d;
        $d = '';
        try {
            $row = \app\common\model\Lang::where('is_open', 1)->order('is_default', 'desc')->order('sort', 'asc')->find();
            if ($row) {
                $d = $row['code'];
            }
        } catch (\Throwable $e) {
        }
        if (!$d) {
            $codes = lang_codes();
            $d     = reset($codes);
        }
        \think\facade\Cache::set('yy_default_lang', $d, 3600);
        return $d;
    }
}

if (!function_exists('lang_tpl_dir')) {
    /**
     * 语言对应的前台模板文件夹名称（后台「语言版本」可单独指定）
     * 例：语言 code=en，模板文件夹可能是 en 或 english
     * @param string|null $code 语言代码，默认当前语言
     */
    function lang_tpl_dir($code = null)
    {
        $code = $code ?: lang_code();
        $dirs = \think\facade\Cache::get('yy_lang_tpldir');
        if (!is_array($dirs)) {
            $dirs = [];
            try {
                foreach (\app\common\model\Lang::column('tpl_dir', 'code') as $c => $d) {
                    $dirs[$c] = $d ?: $c;
                }
            } catch (\Throwable $e) {
            }
            \think\facade\Cache::set('yy_lang_tpldir', $dirs, 3600);
        }
        return isset($dirs[$code]) ? $dirs[$code] : $code;
    }
}

if (!function_exists('lang_code')) {
    /**
     * 当前生效语言代码
     * 后台：session('admin_lang')；前台：中间件 via withMiddleware 注入的 _cms_lang
     */
    function lang_code()
    {
        try {
            $req = request();
            if ($req) {
                $injected = $req->middleware('_cms_lang');
                if ($injected && in_array($injected, lang_codes())) {
                    return $injected;
                }
                $injected = $req->param('_cms_lang');
                if ($injected && in_array($injected, lang_codes())) {
                    return $injected;
                }
            }
        } catch (\Throwable $e) {
        }
        try {
            if (app('http')->getName() === 'admin') {
                $l = session('admin_lang');
                if ($l && in_array($l, lang_codes())) {
                    return $l;
                }
            }
        } catch (\Throwable $e) {
        }
        return default_lang();
    }
}

if (!function_exists('lang_set')) {
    /**
     * 注入当前语言（中间件 / 静态生成按语言渲染前调用）
     * 使用 withMiddleware 通道，避免与请求参数合并的缓存冲突
     */
    function lang_set($code)
    {
        request()->withMiddleware(['_cms_lang' => $code]);
        \think\facade\Cache::set('yy_lang_codes', null); // 无操作占位，保留缓存
    }
}

if (!function_exists('site_path_prefix')) {
    /**
     * 站群路径模式站点前缀（v3.18）
     * 命中 mode=path 站点时返回 /别名/（如 /shenyang/），否则空串
     */
    function site_path_prefix()
    {
        $site = site_match();
        if ($site && (string)($site['mode'] ?? 'domain') === 'path') {
            $a = trim((string)$site['alias'], '/');
            if ($a !== '') return '/' . $a . '/';
        }
        return '';
    }
}

if (!function_exists('lang_is_default')) {
    function lang_is_default()
    {
        $site = site_match();
        if ($site && (string)($site['mode'] ?? 'domain') === 'path') {
            // 站群路径站点：相对站点默认语言判断（站点默认语言在站内无语言前缀）
            return lang_code() === (string)$site['default_lang'];
        }
        return lang_code() === default_lang();
    }
}

if (!function_exists('is_mobile')) {
    /**
     * 判断当前访问是否为移动设备（User-Agent）
     */
    function is_mobile()
    {
        $ua = strtolower((string)request()->header('user-agent', ''));
        if (!$ua) return false;
        $patterns = [
            'iphone', 'ipad', 'ipod', 'android', 'mobile', 'phone',
            'windows phone', 'blackberry', 'iemobile', 'opera mini',
            'nokia', 'symbian', 'webos', 'fennec', 'midp', 'ucweb',
        ];
        foreach ($patterns as $p) {
            if (strpos($ua, $p) !== false) return true;
        }
        return false;
    }
}

if (!function_exists('get_config')) {
    /**
     * 读取全局配置（非语言相关，如 wap.* / content.pagesize / base.admin_path / lang.default）
     * 存储于共享表 yy_config
     */
    function get_config($name = '', $default = '')
    {
        $config = \think\facade\Cache::get('yy_config_all');
        if (!$config) {
            $config = [];
            try {
                foreach (\think\facade\Db::name('config')->select() as $row) {
                    $config[$row['name']] = $row['value'];
                }
            } catch (\Throwable $e) {
            }
            \think\facade\Cache::set('yy_config_all', $config, 3600);
        }
        if ($name === '') return $config;
        // 全局配置以完整点键扁平存储（如 wap.open），优先按整键读取
        if (isset($config[$name])) return $config[$name];
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            $val   = $config;
            foreach ($parts as $p) {
                if (isset($val[$p])) {
                    $val = $val[$p];
                } else {
                    return $default;
                }
            }
            return $val;
        }
        return isset($config[$name]) ? $config[$name] : $default;
    }
}

if (!function_exists('set_config')) {
    /**
     * 写入全局配置并刷新缓存
     */
    function set_config($name, $value)
    {
        $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        $row = \think\facade\Db::name('config')->where('name', $name)->find();
        if ($row) {
            \think\facade\Db::name('config')->where('name', $name)->update(['value' => $value]);
        } else {
            \think\facade\Db::name('config')->insert(['name' => $name, 'value' => $value]);
        }
        \think\facade\Cache::delete('yy_config_all');
    }
}

if (!function_exists('get_setting')) {
    /**
     * 读取语言相关站点配置（存于 yy_{lang}_setting）
     * @param string $name  配置名（支持点语法，如 'site.name'）
     * @param mixed  $default 默认值
     */
    function get_setting($name = '', $default = '')
    {
        $lang  = lang_code();
        $key   = 'setting_all_' . $lang;
        $settings = \think\facade\Cache::get($key);
        if (!$settings) {
            try {
                $settings = \app\common\model\Setting::select()->column('value', 'name');
            } catch (\Throwable $e) {
                $settings = [];
            }
            \think\facade\Cache::set($key, $settings, 3600);
        }
        if ($name === '') return $settings;
        // 站点设置以完整点键扁平存储（如 site.name），优先按整键读取
        if (isset($settings[$name])) return $settings[$name];
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            $val   = $settings;
            foreach ($parts as $p) {
                if (isset($val[$p])) {
                    $val = $val[$p];
                } else {
                    return $default;
                }
            }
            return $val;
        }
        return isset($settings[$name]) ? $settings[$name] : $default;
    }
}

if (!function_exists('set_setting')) {
    /**
     * 写入语言相关站点配置并刷新缓存（写入当前语言的 setting 表）
     */
    function set_setting($name, $value)
    {
        $model = \app\common\model\Setting::where('name', $name)->find();
        $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        if ($model) {
            $model->value = $value;
            $model->save();
        } else {
            \app\common\model\Setting::create(['name' => $name, 'value' => $value]);
        }
        \think\facade\Cache::delete('setting_all_' . lang_code());
    }
}

if (!function_exists('content_pagesize')) {
    /**
     * 前台列表每页条数（后台可设置，默认 10）
     */
    function content_pagesize()
    {
        return max(1, (int)get_config('content.pagesize', 10));
    }
}

if (!function_exists('get_category')) {
    /**
     * 获取栏目信息（前台标签用）
     */
    function get_category($id)
    {
        return \app\common\model\Category::find($id);
    }
}

if (!function_exists('site_url')) {
    /**
     * 生成带语言前缀的前台动态链接（不含 index/ 应用前缀）
     * @param string $controller 控制器（小写）
     * @param string $action     方法（小写）
     * @param array  $params     GET 参数
     */
    function site_url($controller, $action, $params = [])
    {
        $prefix = lang_is_default() ? '' : lang_code() . '/';
        $q      = http_build_query($params);
        return site_path_prefix() . $prefix . $controller . '/' . $action . ($q ? '?' . $q : '');
    }
}

if (!function_exists('category_path')) {
    /**
     * 栏目静态路径（按全局开关 base.static_url_mode 生成）
     * 精准(accurate，默认)：拼接全部祖先 ename，如 pro/pro1/pro2
     * 精简(compact)：仅本栏目 ename，如 pro2
     * @param array|object|int $cat 栏目
     * @return string
     */
    function category_path($cat)
    {
        if (is_numeric($cat)) {
            $cat = \app\common\model\Category::find($cat);
        }
        if (!$cat) return '';
        $mode = get_config('base.static_url_mode', 'accurate');
        $own  = $cat['ename'] ?: 'cat_' . $cat['id'];
        if ($mode === 'compact') {
            return $own;
        }
        $names = [$own];
        $c     = $cat;
        $guard = 0;
        while ((int)$c['parent_id'] > 0 && $guard++ < 20) {
            $p = \app\common\model\Category::find($c['parent_id']);
            if (!$p) break;
            // 跳转/外链栏目不产生真实页面，不出现在静态路径中（如公司简介 redirect 顶级不参与 /aboutus 路径）
            if (in_array($p['type'] ?? '', ['redirect', 'external'], true)) {
                $c = $p;
                continue;
            }
            $names[] = $p['ename'] ?: 'cat_' . $p['id'];
            $c       = $p;
        }
        return implode('/', array_reverse($names));
    }
}

if (!function_exists('write_robots')) {
    /**
     * 生成 public/robots.txt（v3.10）：允许所有，指定 sitemap
     */
    function write_robots()
    {
        $sitemap = 'Sitemap: ' . rtrim((string)get_setting('site.url', ''), '/') . "/sitemap.xml\n";
        $txt = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /upload/\n" . $sitemap;
        $ok = @file_put_contents(root_path() . 'public' . DIRECTORY_SEPARATOR . 'robots.txt', $txt, LOCK_EX);
        return $ok !== false;
    }
}

if (!function_exists('sync_pretty_firewall')) {
    /**
     * 同步伪静态规则文件（Nginx public/nginx.htaccess + Apache public/.htaccess）
     * 主语言静态页 URL 无前缀、文件放主语言文件夹；其他语言带前缀。
     * 在「设为默认语言」「静态页生成」时调用，自动把规则中的主语言代码改为 $lang。
     * @param string $lang 主语言（默认语言）代码
     * @return bool
     */
    function sync_pretty_firewall($lang = null)
    {
        $lang     = $lang ?: default_lang();
        $others   = array_values(array_diff(lang_codes(), [$lang]));
        $others_s = implode('|', array_map(function ($o) {
            return preg_quote($o, '/');
        }, $others));

        $nginx = <<<NG
# ============================================================
# Nginx 伪静态规则（由易云CMS 自动生成，请勿手工修改）
# 主语言：{$lang}
# 1) 主语言静态页 URL 无前缀：栏目/内容/单页文件在 /{$lang}/ 文件夹，
#    URL 由下方规则隐藏 {$lang} 前缀（访问 /news 实际读取 /{$lang}/news/index.html）
# 2) 其他语言静态页带前缀（/en/news 等）直接命中 public/ 对应文件夹
# 3) 未命中静态则走 ThinkPHP 入口动态路由
# ============================================================

# 无斜杠的一级栏目（如 /pro）→ 主语言 /{$lang}/pro/index.html（内部，不泄露前缀）
# 动态控制器名单（lists/show/search/tag/message/guestbook/index 等）不参与静态重写
rewrite ^/((?!{$others_s}|admin/|upload/|static/|download|index\.php|favicon\.ico|robots\.txt|lists|show|search|tag|message|guestbook|index)[A-Za-z0-9_\-]+)$ /{$lang}/\$1/index.html last;

# 栏目/内容/单页 + 其他语言 + 动态 统一走 try_files
try_files \$uri \$uri/ /{$lang}\$uri /{$lang}\$uri/ /index.php?s=\$uri&\$args;
NG;

        $apache = <<<AP
<IfModule mod_rewrite.c>
  Options +FollowSymlinks -Multiviews
  RewriteEngine On

  # ------------------------------------------------------------------
  # Apache 伪静态（由易云CMS 自动生成，请勿手工修改；主语言：{$lang}）
  # 主语言静态页 URL 无前缀：栏目/内容/单页文件在 /{$lang}/ 文件夹，前缀由下方规则隐藏
  # 其他语言带前缀直接命中；未命中走 ThinkPHP 动态路由
  # ------------------------------------------------------------------

  # 主语言目录型（列表/栏目）→ {lang}/index.html（仅当静态 index.html 存在，否则回落动态）
  RewriteCond %{REQUEST_URI} !^/$
  RewriteCond %{REQUEST_URI} !^/({$others_s}|admin|upload|static|download|index\.php|favicon\.ico|robots\.txt|lists|show|search|tag|message|guestbook|index)/? [NC]
  RewriteCond %{DOCUMENT_ROOT}/{$lang}%{REQUEST_URI}/index.html -f
  RewriteRule ^(.*)$ {$lang}/\$1/index.html [L]

  # 主语言文件型（单页/内容页 .html）
  RewriteCond %{REQUEST_URI} !^/$
  RewriteCond %{REQUEST_URI} !^/({$others_s}|admin|upload|static|download|index\.php|favicon\.ico|robots\.txt)/? [NC]
  RewriteCond %{DOCUMENT_ROOT}/{$lang}%{REQUEST_URI} -f
  RewriteRule ^(.*)$ {$lang}/\$1 [L]

  # ThinkPHP 标准路由（非文件非目录）
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ index.php?/\$1 [QSA,PT,L]
</IfModule>
AP;

        $pub = root_path() . 'public' . DIRECTORY_SEPARATOR;
        $ok  = @file_put_contents($pub . 'nginx.htaccess', $nginx, LOCK_EX);
        $ok2 = @file_put_contents($pub . '.htaccess', $apache, LOCK_EX);
        return $ok !== false && $ok2 !== false;
    }
}

if (!function_exists('url_cat')) {
    /**
     * 生成栏目链接（静态优先，否则带语言前缀的动态链接）
     * 外链(external) / 跳转(redirect) 栏目直接返回目标链接
     * @param array|int $category 栏目
     * @param int       $depth    内部递归深度（防止跳转循环）
     */
    function url_cat($category, $depth = 0)
    {
        if (is_numeric($category)) {
            $category = \app\common\model\Category::find($category);
        }
        if (!$category) return '#';
        if ($depth > 5) return '#';

        $type = isset($category['type']) ? $category['type'] : 'list';
        // 外链：直接返回地址
        if ($type === 'external') {
            return $category['external_url'] ?: '#';
        }
        // 跳转：解析 @id / @ename / http 目标
        if ($type === 'redirect') {
            $target = redirect_target_url($category, $depth);
            if ($target) return $target;
            // 无有效目标时回落为列表链接
        }
        if (!empty($category['static_path'])) {
            $sp = '/' . ltrim($category['static_path'], '/');
            // 列表栏目链接补尾斜杠：避免 Nginx/Apache 对目录做 301 时暴露主语言前缀
            if (($category['type'] ?? 'list') === 'list' && $sp !== '/' && !preg_match('/\.html$/i', $sp)) {
                $sp = rtrim($sp, '/') . '/';
            }
            // 站群路径站点：静态路径补 /别名/ 前缀（避免跳回主站）
            $p = site_path_prefix();
            if ($p !== '' && strpos($sp, $p) !== 0) {
                $sp = $p . ltrim($sp, '/');
            }
            return $sp;
        }
        return site_url('lists', 'index', ['id' => $category['id']]);
    }
}

if (!function_exists('redirect_target_url')) {
    /**
     * 解析栏目的跳转目标地址
     * 支持：@栏目ID / @栏目ename（内部跳转到目标栏目）、http(s):// 或 / 开头的外部链接
     * @param array|object $category 栏目
     * @param int          $depth    递归深度（防循环）
     */
    function redirect_target_url($category, $depth = 0)
    {
        $eu = trim((string)($category['external_url'] ?? ''));
        if ($eu === '' || $depth > 5) return '';

        // 内部跳转：@id 或 @ename
        if ($eu[0] === '@') {
            $ref = trim(substr($eu, 1));
            if ($ref === '') return '';
            $target = is_numeric($ref)
                ? \app\common\model\Category::find((int)$ref)
                : \app\common\model\Category::where('ename', $ref)->find();
            if (!$target) return '';
            return url_cat($target, $depth + 1);
        }

        // 外部链接 / 站点内路径
        return $eu;
    }
}

if (!function_exists('url_content')) {
    /**
     * 生成内容详情链接
     */
    function url_content($content, $category = null)
    {
        if (!$category && isset($content['catid'])) {
            $category = \app\common\model\Category::find($content['catid']);
        }
        if (!empty($content['static_path'])) {
            $sp = '/' . ltrim($content['static_path'], '/');
            // 站群路径站点：静态路径补 /别名/ 前缀
            $p = site_path_prefix();
            if ($p !== '' && strpos($sp, $p) !== 0) {
                $sp = $p . ltrim($sp, '/');
            }
            return $sp;
        }
        $catId = $category ? $category['id'] : (isset($content['catid']) ? $content['catid'] : 0);
        return site_url('show', 'index', ['id' => $content['id'], 'catid' => $catId]);
    }
}

if (!function_exists('url_home')) {
    function url_home()
    {
        $p = site_path_prefix();
        if ($p !== '') {
            return lang_is_default() ? $p : $p . lang_code() . '/index/index';
        }
        return lang_is_default() ? '/' : '/' . lang_code() . '/index/index';
    }
}

if (!function_exists('url_homepage')) {
    /**
     * 引导页"进入网站"正常首页链接
     * 主语言：/home（路由 index/home）；其他语言：/{lang}/home.html（静态文件，伪静态模式可用）
     */
    function url_homepage()
    {
        $p = site_path_prefix();
        if ($p !== '') {
            return lang_is_default() ? $p : $p . lang_code() . '/home.html';
        }
        return lang_is_default() ? '/home' : '/' . lang_code() . '/home.html';
    }
}

if (!function_exists('url_lang')) {
    /**
     * 语言切换链接（跳转到该语言首页）
     */
    function url_lang($code)
    {
        $p = site_path_prefix();
        if ($p !== '') {
            $site = site_match();
            $def  = $site ? (string)$site['default_lang'] : default_lang();
            return ($code === $def) ? $p : $p . $code . '/index/index';
        }
        return ($code === default_lang()) ? '/' : '/' . $code . '/index/index';
    }
}

if (!function_exists('get_child_cats')) {
    /**
     * 获取某栏目的全部子栏目 id（含自身，用于内容聚合查询）
     */
    function get_child_cats($id)
    {
        return \app\common\model\Category::getChildIds($id);
    }
}

if (!function_exists('attach_download_rewrite')) {
    /**
     * 把内容 HTML 中的附件链接（/upload/ 非图片）重写为下载端点
     * 下载文件名 = 链接文字 + 原后缀：用户在编辑器里改附件链接文字即可改名，
     * 前台点击下载时按改名后的名称 + 原后缀命名，无需手写后缀。
     * 图片链接（jpg/png 等）保持原样直接显示。
     * @param string $html 富文本内容
     * @return string
     */
    function attach_download_rewrite($html)
    {
        if (!$html || !is_string($html) || strpos($html, '/upload/') === false) {
            return $html;
        }
        return preg_replace_callback(
            '#(<a[^>]+href=["\'])/upload/([^"\']+?)(["\'][^>]*>)(.*?)(</a>)#is',
            function ($m) {
                $ext = strtolower(pathinfo($m[2], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                    return $m[0]; // 图片链接保持原样
                }
                $text = trim(strip_tags($m[4]));
                $href = '/download/index?file=' . rawurlencode($m[2]) . '&name=' . rawurlencode($text);
                return $m[1] . $href . $m[3] . $m[4] . $m[5];
            },
            $html
        );
    }
}

if (!function_exists('content_people')) {
    /**
     * 内容发稿人/审核人信息（供前台模板调用：{$content.author_name} / {$content.audit_name}）
     * @param int $authorUid 发稿人管理员ID
     * @param int $auditUid  审核人管理员ID
     * @return array ['author_name'=>, 'audit_name'=>]
     */
    function content_people($authorUid, $auditUid)
    {
        $authorUid = (int)$authorUid;
        $auditUid  = (int)$auditUid;
        $ids = [];
        if ($authorUid > 0) $ids[$authorUid] = 1;
        if ($auditUid > 0)  $ids[$auditUid] = 1;
        $map = [];
        if ($ids) {
            try {
                $map = \app\common\model\Admin::whereIn('id', array_keys($ids))->column('nickname', 'id');
            } catch (\Throwable $e) {
                $map = [];
            }
        }
        return [
            'author_name' => $authorUid ? (string)($map[$authorUid] ?? '') : '',
            'audit_name'  => $auditUid ? (string)($map[$auditUid] ?? '') : '',
        ];
    }
}

if (!function_exists('str2sub')) {
    /**
     * 云优模板函数：截取字符串（自动去HTML标签、压缩空白）
     * 模板调用：{$var|str2sub=140,true}（第二个参数 true=去HTML标签）
     */
    function str2sub($str, $len = 100, $strip = true)
    {
        $str = (string)$str;
        if ($strip === 'true' || $strip === true) {
            $str = strip_tags($str);
        }
        $str = preg_replace('/\s+/u', ' ', $str);
        return mb_substr($str, 0, max(0, (int)$len), 'utf-8');
    }
}

if (!function_exists('str2img')) {
    /**
     * 云优模板函数：从正文HTML中提取第N张图片地址
     * 模板调用：{$var|str2img='1'} 或 {$var|str2img=2}
     */
    function str2img($str, $n = 1)
    {
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string)$str, $m)) {
            $idx = max(1, (int)$n) - 1;
            return isset($m[1][$idx]) ? $m[1][$idx] : ($m[1][0] ?? '');
        }
        return '';
    }
}

if (!function_exists('str2arr')) {
    /**
     * 云优模板函数：按分隔符拆分为数组
     * 模板调用：{$var|str2arr='***'}（默认 *** 分隔，云优多图/多值存储分隔符）
     */
    function str2arr($str, $sep = '***')
    {
        $arr = explode((string)$sep, (string)$str);
        return array_values(array_filter(array_map('trim', $arr), function ($v) {
            return $v !== '';
        }));
    }
}

if (!function_exists('sitelink')) {
    /**
     * 云优模板函数：站内内容处理（等价于附件下载链接重写）
     * 模板调用：{$content.content|sitelink}
     */
    function sitelink($str)
    {
        return attach_download_rewrite((string)$str);
    }
}

if (!function_exists('content_his_tables')) {
    /**
     * 内容归档表列表（分表机制，v3.10.2）
     * 返回形如 ['yy_cn_content_2024','yy_cn_content_2023']，年份倒序（新→旧）
     * @param string|null $lang 语言代码，默认当前语言
     */
    function content_his_tables($lang = null)
    {
        $lang  = $lang ?: lang_code();
        $prefix = 'yy_' . $lang . '_content_';
        try {
            // information_schema 参数化查询，仅保留 _YYYY 年份归档表（排除 content_log 等）
            $rows = \think\facade\Db::query(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?",
                [$prefix . '%']
            );
            $tables = [];
            foreach ($rows as $r) {
                $v = isset($r['table_name']) ? $r['table_name'] : '';
                if (strpos($v, $prefix) === 0 && preg_match('#_20\d{2}$#', $v)) {
                    $tables[] = $v;
                }
            }
            rsort($tables);
            return $tables;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
