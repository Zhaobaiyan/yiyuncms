<?php
// +----------------------------------------------------------------------
// | 后台文件上传控制器
// | 返回 wangEditor v4 兼容格式：{errno:0, data:{url:...}}
// | 支持本地 /upload 与七牛云（配置开启后直传七牛，返回 https 外链）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\service\Qiniu;
use app\common\service\UploadService;

class Upload extends AdminBase
{
    public function index()
    {
        $file = request()->file('file');
        if (!$file) {
            return json(['errno' => 1, 'message' => '未收到文件']);
        }
        $size  = (int)get_config('base.upload_size', 2) * 1024 * 1024;
        $types = explode(',', (string)get_config('base.upload_type', 'jpg,png,gif,webp,mp4,pdf,zip'));

        try {
            $res = UploadService::store($file, $size, $types);
        } catch (\Throwable $e) {
            return json(['errno' => 1, 'message' => '上传失败：' . $e->getMessage()]);
        }
        if (!$res['ok']) {
            return json(['errno' => 1, 'message' => $res['error']]);
        }

        AdminLog::record('upload', '上传文件：' . $res['url']);
        // 素材库收录（v3.10）
        try {
            $isImg = in_array(strtolower(pathinfo((string)$res['url'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
            \app\common\model\Asset::add(
                (string)$file->getOriginalName(),
                (string)$res['url'],
                $isImg ? 'image' : 'file',
                (string)pathinfo((string)$res['url'], PATHINFO_EXTENSION),
                (int)$file->getSize()
            );
        } catch (\Throwable $e) {}
        return json(['errno' => 0, 'data' => ['url' => $res['url'], 'alt' => '', 'href' => '']]);
    }

    /**
     * 图片选择器：返回最近上传的图片列表（扫描本地 /upload，七牛模式返回空提示）
     */
    public function images()
    {
        $list = UploadService::listMedia('image', 50);
        return json(['errno' => 0, 'data' => ['list' => $list, 'qiniu' => Qiniu::enabled()]]);
    }
}
