<?php
// +----------------------------------------------------------------------
// | 缓存设置（文件缓存，生产可换 Redis）
// +----------------------------------------------------------------------
return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => 'yy_',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // redis缓存
        'redis' => [
            'type'       => 'redis',
            'host'       => env('redis.hostname', '127.0.0.1'),
            'port'       => env('redis.port', 6379),
            'password'   => env('redis.password', ''),
            'select'     => 0,
            'timeout'    => 0,
            'expire'     => 0,
            'persistent' => false,
            'prefix'     => 'yy_',
            'tag_prefix' => 'tag:',
        ],
    ],
];
