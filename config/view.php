<?php
// +----------------------------------------------------------------------
// | 视图设置（模板引擎）
// +----------------------------------------------------------------------
return [
    // 模板引擎类型使用 Think
    'type'          => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
    'auto_rule'     => 1,
    // 视图目录名（默认 view）
    'view_dir_name' => 'view',
    // 模板起始路径
    'view_path'     => '',
    // 模板文件后缀
    'view_suffix'   => 'html',
    // 模板文件名分隔符
    'view_depr'     => DIRECTORY_SEPARATOR,
    // 模板引擎普通标签开始标记
    'tpl_begin'     => '{',
    // 模板引擎普通标签结束标记
    'tpl_end'       => '}',
    // 标签库标签开始标记
    'taglib_begin'  => '{',
    // 标签库标签结束标记
    'taglib_end'    => '}',
    // 内置标签扩展名
    'taglib_build_in' => 'cx',
    // 预加载的自定义标签库（前台 {cms:xxx} + 云优兼容 {yunu:xxx} 标签）
    'taglib_pre_load' => 'app\common\taglib\Cms,app\common\taglib\Yunu',

    // 模板缓存
    'tpl_cache'     => true,
    // 模板缓存前缀
    'cache_prefix'  => '',
    // 模板缓存有效期 0 为永久
    'cache_expire'  => 0,
    // 模板缓存目录
    'tpl_cache_path'=> '',
    // 是否开启模板编译缓存
    'tpl_cache_on'  => true,

    // 页面资源路径（后台/前台统一静态资源，可通过系统设置覆盖）
    'tpl_replace_string' => [
        '__STATIC__' => '/static',
        '__PUBLIC__' => '/assets/index?path=', // 前台模板资源入口（template/{语言}/ 目录，见 app/index/controller/Assets.php）
        '__UEDITOR__' => '/static/admin/ueditor',
    ],
];
