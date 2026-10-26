<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------
return [
    // 应用地址
    'app_host'         => env('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（自动创建的多应用）
    // 语言前缀动态写入：由 app/common/Service/LangAppService 在初始化阶段
    // 根据后台「语言版本」自动构建（所有已启用语言 -> index 前台应用），此处留空
    'app_map'          => [],
    // 域名绑定
    'domain_bind'      => [],
    // 禁止 URL 访问的应用列表
    'deny_app_list'    => [],
    // 启用 URL 路由表达式（非应用目录的首段直接作为默认应用控制器访问，cn 前台走裸路径）
    'app_express'      => true,

    // 异常页面的模板文件
    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => false,

    // 后缀
    'url_html_suffix'  => 'html',

    // 伪静态后缀，如开启了静态页功能建议设为 'html'
    'url_common_param' => false,
];
