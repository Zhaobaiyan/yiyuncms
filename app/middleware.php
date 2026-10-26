<?php
// +----------------------------------------------------------------------
// | 应用全局中间件（按顺序执行）
// +----------------------------------------------------------------------
return [
    \think\middleware\SessionInit::class,
    \app\common\middleware\LangRouter::class,
    \app\common\middleware\CsrfCheck::class,
    \app\common\middleware\AdminAuth::class,
];
