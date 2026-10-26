<?php
// +----------------------------------------------------------------------
// | 轻量插件钩子（v3.10）
// | 供二次开发/插件在系统关键动作处挂载自定义逻辑，无需修改核心代码
// | 用法：
// |   注册监听：Hook::on('content_save', function($data){ ... });
// |   触发事件：Hook::listen('content_save', ['id'=>1, 'action'=>'add']);
// | 插件目录：/addons/插件名/hooks.php 定义监听数组（详见文档《后台操作指南》）
// +----------------------------------------------------------------------
namespace app\common;

class Hook
{
    protected static $listeners = [];

    /**
     * 注册监听器（可传闭包或 [类, 方法]）
     */
    public static function on($event, $callback)
    {
        self::$listeners[$event][] = $callback;
    }

    /**
     * 触发事件
     */
    public static function listen($event, $data = null)
    {
        if (empty(self::$listeners[$event])) return;
        foreach (self::$listeners[$event] as $cb) {
            try {
                if (is_callable($cb)) {
                    call_user_func($cb, $data);
                } elseif (is_array($cb) && class_exists($cb[0])) {
                    call_user_func($cb, $data);
                }
            } catch (\Throwable $e) {
                // 插件异常不影响主流程
            }
        }
    }

    /**
     * 加载 /addons 下全部插件的 hooks.php（首次调用时执行一次）
     */
    public static function loadAddons()
    {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;
        $dir = root_path() . 'addons';
        if (!is_dir($dir)) return;
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'hooks.php') ?: [] as $file) {
            $reg = include $file;
            if (is_array($reg)) {
                foreach ($reg as $event => $cb) self::on($event, $cb);
            }
        }
    }
}
