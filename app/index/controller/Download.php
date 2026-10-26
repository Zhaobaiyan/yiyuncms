<?php
// +----------------------------------------------------------------------
// | 前台附件下载控制器
// | 供内容/栏目富文本中的附件链接使用：/download/index?file=20261008/xxx.doc&name=下载显示名
// | 下载文件名 = name（用户改的显示名）+ 原文件后缀，后缀自动补，无需手写
// +----------------------------------------------------------------------
namespace app\index\controller;

class Download extends FrontBase
{
    public function index()
    {
        $file = trim((string)input('get.file', ''));
        $name = trim((string)input('get.name', ''));

        // 路径安全校验：仅允许 public/upload 目录内的相对路径
        if ($file === '' || strpos($file, '..') !== false || $file[0] === '/'
            || preg_match('/[\\\\:]/', $file) || !preg_match('#^[0-9A-Za-z_\-/]+(\.[A-Za-z0-9]+)?$#', $file)) {
            abort(404, '文件不存在');
        }

        $base = root_path() . 'public' . DIRECTORY_SEPARATOR . 'upload';
        $real = realpath($base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
        if (!$real || strpos($real, realpath($base) . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)) {
            abort(404, '文件不存在');
        }

        $ext          = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        $downloadName = $name !== '' ? $name : pathinfo($real, PATHINFO_FILENAME);
        // 去掉用户输入中可能已带的后缀，统一追加原后缀（防止双后缀）
        $downloadName = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '', trim($downloadName));
        if ($downloadName === '') {
            $downloadName = pathinfo($real, PATHINFO_FILENAME);
        }
        $downloadName .= '.' . $ext;

        return download($real, $downloadName);
    }
}
