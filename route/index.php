<?php
// +----------------------------------------------------------------------
// | 路由定义（v3.10）—— index 前台应用
// | 后台应用（admin）为自动多应用模式，无需路由文件；前台 index 路由定义于此：
// | 收藏接口 / 公开内容 API / 兜底 404 定制页
// +----------------------------------------------------------------------

use think\facade\Route;


// 收藏接口（v3.10）：POST /api/favorite 匿名收藏/取消，GET /api/favorite_count?id=N 收藏数
Route::post('api/favorite', 'api/favorite');
Route::get('api/favorite_count', 'api/favoriteCount');

// 公开内容 API（v3.10）：GET /api/content?catid=&limit=&order= 列表；GET /api/content_detail?id=N 详情
Route::get('api/content', 'api/content');
Route::get('api/content_detail', 'api/contentDetail');

// 兜底 404：前台自定义 404 页由 ExceptionHandle 统一处理（本文件规则在部分环境下不被加载，保留以备启用）
// 见 app/ExceptionHandle.php render()
