<?php
// +----------------------------------------------------------------------
// | 系统设置控制器（多语言 + 全局配置）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;

class Setting extends AdminBase
{
    /**
     * 网站设置
     * 语言相关设置写入 yy_{lang}_setting；全局设置写入 yy_config
     */
    public function index()
    {
        if (request()->isPost()) {
            $data  = input('post.');
            $groups = $this->groups();
            foreach ($groups as $group) {
                foreach ($group['items'] as $item) {
                    $name = $item[0];
                    // TP6 input() 会把键中的点 . 转为下划线 _（如 qiniu.open -> qiniu_open），需映射查找
                    $key  = str_replace('.', '_', $name);
                    if (!array_key_exists($key, $data)) continue;
                    if (($item[3] ?? 'lang') === 'global') {
                        set_config($name, $data[$key]);
                    } else {
                        set_setting($name, $data[$key]);
                    }
                }
            }
            AdminLog::record('setting', '更新网站设置[' . lang_code() . ']');
            return $this->ok('设置已保存');
        }
        $groups = $this->groups();
        foreach ($groups as &$group) {
            foreach ($group['items'] as &$item) {
                $item[5] = (($item[3] ?? 'lang') === 'lang') ? '{cms:setting name="' . $item[0] . '" /}' : '';
            }
        }
        return view('setting/index', ['groups' => $groups]);
    }

    /**
     * 站点开关
     */
    public function site()
    {
        $close = intval(input('post.close', 0));
        set_setting('site.close', $close);
        set_setting('site.close_tip', input('post.close_tip', '站点维护中'));
        AdminLog::record('setting_site', '站点开关：' . ($close ? '关闭' : '开启'));
        return $this->ok('操作成功');
    }

    /**
     * 语言切换（后台录入语言）
     */
    public function setLang()
    {
        $code = input('post.code', '');
        if (!in_array($code, lang_codes())) {
            return $this->fail('语言不存在');
        }
        session('admin_lang', $code);
        return $this->ok('已切换到：' . $code);
    }

    /**
     * 设置分组定义
     * item: [名称, 标签, 控件类型(text/textarea/select), store(lang/global), 选项(select用)]
     */
    protected function groups()
    {
        return [
            '基础信息(当前语言)' => [
                'items' => [
                    ['site.name', '网站名称', 'text', 'lang'],
                    ['site.url', '网站地址', 'text', 'lang'],
                    ['site.logo', 'LOGO图片', 'image', 'lang'],
                    ['site.icp', '备案号', 'text', 'lang'],
                    ['site.keywords', '网站关键词', 'text', 'lang'],
                    ['site.description', '网站描述', 'textarea', 'lang'],
                    ['site.copyright', '版权信息', 'textarea', 'lang'],
                ],
            ],
            'SEO设置(当前语言)' => [
                'items' => [
                    ['seo.title', 'SEO标题', 'text', 'lang'],
                    ['seo.keywords', 'SEO关键词', 'text', 'lang'],
                    ['seo.description', 'SEO描述', 'textarea', 'lang'],
                ],
            ],
            '内容与留言(当前语言)' => [
                'items' => [
                    ['message.audit', '留言是否审核', 'select', 'lang', [['1', '需要审核'], ['0', '无需审核']]],
                    ['message.notify', '新留言邮件通知', 'select', 'lang', [['0', '关闭'], ['1', '开启']]],
                    ['site.close', '站点开关', 'select', 'lang', [['0', '开启'], ['1', '关闭维护']]],
                ],
            ],
            '内容审核(全局)' => [
                'items' => [
                    ['content.audit', '内容审核', 'select', 'global', [['1', '开启(投稿需审核/驳回/通过)'], ['0', '关闭(发布即上架,草稿用于下架)']]],
                ],
            ],
            '功能开关(全局)' => [
                'items' => [
                    ['member.open', '会员系统', 'select', 'global', [['1', '开启'], ['0', '关闭(前台会员页面禁止访问,后台会员管理菜单隐藏)']]],
                    ['shop.open', '商城系统', 'select', 'global', [['1', '开启'], ['0', '关闭(前台商城禁止访问,后台商城管理菜单隐藏)']]],
                    ['service.open', '客服系统', 'select', 'global', [['1', '开启'], ['0', '关闭(前台客服禁止访问,后台客服会话菜单隐藏)']]],
                    ['comment.open', '评论功能', 'select', 'global', [['1', '开启'], ['0', '关闭(详情页不显示评论,后台评论管理菜单隐藏)']]],
                    ['favorite.open', '收藏功能', 'select', 'global', [['1', '开启'], ['0', '关闭(详情页收藏按钮隐藏)']]],
                ],
            ],
            '静态页设置(全局)' => [
                'items' => [
                    ['base.static_url_mode', '栏目静态URL规则', 'select', 'global', [['accurate', '精准链接(一级/二级/三级+详情页)'], ['compact', '精简链接(只留最后栏目+详情页)']]],
                ],
            ],
            '前台翻页样式(全局)' => [
                'items' => [
                    ['base.page_mode', '翻页数字模式', 'select', 'global', [['full', '完整数字(超过阈值显示省略号)'], ['window', '固定滑动窗口']]],
                    ['base.page_window', '滑动窗口数字个数(默认7)', 'text', 'global'],
                    ['base.page_first_last', '显示首页/尾页按钮', 'select', 'global', [['1', '显示'], ['0', '不显示']]],
                    ['base.page_full_limit', '完整模式省略阈值(默认15页)', 'text', 'global'],
                ],
            ],
            '手机版设置(全局)' => [
                'items' => [
                    ['wap.open', '启用手机版前端', 'select', 'global', [['0', '关闭'], ['1', '开启']]],
                    ['wap.auto_redirect', '自动识别PC/手机跳转', 'select', 'global', [['0', '关闭'], ['1', '开启']]],
                ],
            ],
            '全局设置' => [
                'items' => [
                    ['base.code_hint', '前台代码提示', 'select', 'global', [['1', '开启(后台显示前台调用代码)'], ['0', '关闭(隐藏代码提示,页面更简洁)']]],
                    ['base.splash', '引导页开关', 'select', 'global', [['0', '关闭(直接进入首页)'], ['1', '开启(访问站点先显示引导页,引导页模板 index_index.html,进入网站链接用 {yunu:url name="homepage" /})']]],
                    ['base.session_timeout', '后台无操作自动退出', 'timeout', 'global', [['30', '30分钟'], ['60', '1小时'], ['360', '6小时'], ['custom', '自定义(分钟)']]],
                    ['base.admin_path', '后台路径(默认admin)', 'text', 'global'],
                    ['base.static_mode', '静态模式(1开0关)', 'select', 'global', [['0', '关闭'], ['1', '开启']]],
                    ['admin.captcha', '后台登录验证码', 'select', 'global', [['1', '开启(登录需输入验证码)'], ['0', '关闭']]],
                    ['base.upload_size', '上传大小限制(MB)', 'text', 'global'],
                    ['base.upload_type', '允许上传类型', 'text', 'global'],
                ],
            ],
            '站群设置(全局)' => [
                'items' => [
                    ['site.multi', '站群开关', 'select', 'global', [['0', '关闭(单站点模式)'], ['1', '开启(站群模式:路径别名+域名命中)']]],
                ],
            ],
            '七牛云存储(全局)' => [
                'items' => [
                    ['qiniu.open', '启用七牛云上传', 'select', 'global', [['0', '关闭(本地存储)'], ['1', '开启(上传到七牛)']]],
                    ['qiniu.access_key', 'AccessKey', 'text', 'global'],
                    ['qiniu.secret_key', 'SecretKey', 'text', 'global'],
                    ['qiniu.bucket', '空间名(Bucket)', 'text', 'global'],
                    ['qiniu.domain', '访问域名(外链前缀)', 'text', 'global'],
                    ['qiniu.zone', '区域', 'select', 'global', [['z0', '华东'], ['z1', '华北'], ['z2', '华南'], ['na0', '北美'], ['as0', '东南亚']]],
                ],
            ],
            '邮件通知(全局)' => [
                'items' => [
                    ['smtp.host', 'SMTP服务器', 'text', 'global'],
                    ['smtp.port', 'SMTP端口', 'text', 'global'],
                    ['smtp.secure', '加密方式', 'select', 'global', [['ssl', 'SSL(465,QQ/163邮箱推荐)'], ['tls', 'STARTTLS(587)'], ['none', '不加密(25)']]],
                    ['smtp.user', '邮箱账号', 'text', 'global'],
                    ['smtp.pass', '邮箱授权码', 'password', 'global'],
                    ['smtp.from', '发件人邮箱', 'text', 'global'],
                    ['smtp.from_name', '发件人名称', 'text', 'global'],
                    ['smtp.to', '通知收件人(多个用英文逗号分隔)', 'text', 'global'],
                ],
            ],
            '通知中心(全局, v3.24)' => [
                'items' => [
                    ['sms.api_url', '短信接口地址(留空停用短信)', 'text', 'global'],
                    ['sms.tpl', '短信参数模板(占位符 {phone}/{content})', 'text', 'global'],
                ],
            ],
        ];
    }
}
