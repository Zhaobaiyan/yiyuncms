<?php
// +----------------------------------------------------------------------
// | 中间件配置（全局注册，AdminAuth 会自动放行前台与登录白名单）
// +----------------------------------------------------------------------
return [
    // 全局中间件
    'default'         => [
        \app\common\middleware\AdminAuth::class,
    ],

    // 中间件分组
    'group'           => [],

    // 中间件别名
    'alias'           => [
        'adminAuth' => \app\common\middleware\AdminAuth::class,
        'pageCache' => \app\common\middleware\PageCache::class,
    ],

    // 优先级
    'priority'        => [],
];
