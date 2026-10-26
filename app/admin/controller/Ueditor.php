<?php
// +----------------------------------------------------------------------
// | UEditor 服务端控制器
// | 兼容 UEditor（云优风格）：config / uploadimage / uploadvideo / uploadfile / listimage
// | 上传走易云上传逻辑（本地 /upload 或七牛直传），返回 UEditor 标准 JSON
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\service\UploadService;

class Ueditor extends AdminBase
{
    protected $allowedImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    protected $allowedFile  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'zip', 'rar', 'txt', 'mp4', 'mp3'];

    public function index()
    {
        $action = input('get.action', 'config');
        switch ($action) {
            case 'config':
                return json($this->config());
            case 'uploadimage':
            case 'uploadvideo':
            case 'uploadfile':
                return $this->upload($action);
            case 'listimage':
                return $this->listMedia('image');
            case 'listfile':
                return $this->listMedia('file');
            default:
                return json(['state' => '无效的 action']);
        }
    }

    protected function config()
    {
        $base = (string)url('admin/ueditor/index');
        return [
            'imageActionName' => 'uploadimage', 'videoActionName' => 'uploadvideo', 'fileActionName' => 'uploadfile',
            'imageFieldName' => 'upfile', 'videoFieldName' => 'upfile', 'fileFieldName' => 'upfile',
            'imageMaxSize' => 2048000, 'videoMaxSize' => 10240000, 'fileMaxSize' => 20480000,
            'imageUrlPrefix' => '', 'videoUrlPrefix' => '', 'fileUrlPrefix' => '',
            'imageAllowFiles' => ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'],
            'videoAllowFiles' => ['.mp4', '.avi', '.wmv', '.mov', '.webm', '.m3u8'],
            'fileAllowFiles' => $this->allowedFile,
            'imageCompressEnable' => true, 'imageCompressBorder' => 1600,
            'imageCompressAngle' => 90,
            'serverUrl' => $base,
            'uploadType' => 'local',
            'imageManagerActionName' => 'listimage',
            'fileManagerActionName' => 'listfile',
            'imageManagerUrlPrefix' => '',
            'fileManagerUrlPrefix' => '',
            'catchRemoteImageEnable' => false,
        ];
    }

    /**
     * 上传（UEditor 标准返回）
     */
    protected function upload($action)
    {
        try {
            $file = request()->file('upfile');
            if (!$file) {
                return json(['state' => '未收到文件']);
            }
            $ext   = strtolower($file->getOriginalExtension());
            $size  = $file->getSize(); // move 前先取值（move 后临时文件句柄失效）
            $max   = $action === 'uploadimage' ? 2048000 : ($action === 'uploadvideo' ? 10240000 : 20480000);
            $types = $action === 'uploadimage' ? $this->allowedImage : ($action === 'uploadvideo' ? ['mp4', 'avi', 'wmv', 'mov', 'webm', 'm3u8'] : $this->allowedFile);
            $res = UploadService::store($file, $max, $types);
            if (!$res['ok']) {
                return json(['state' => $res['error']]);
            }
            \app\common\model\AdminLog::record('ueditor_upload', 'UEditor上传：' . $res['url']);
            return json(['state' => 'SUCCESS', 'url' => $res['url'], 'title' => $res['name'], 'original' => $file->getOriginalName(), 'type' => '.' . $ext, 'size' => $size]);
        } catch (\Throwable $e) {
            return json(['state' => '上传失败：' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine()]);
        }
    }

    /**
     * 媒体列表（图片选择器）
     */
    protected function listMedia($kind)
    {
        // 与 Upload::images 共用 UploadService 扫描（image=图片；file=附件）
        $list = UploadService::listMedia($kind, 100);
        $out = [];
        foreach ($list as $it) {
            $out[] = ['url' => $it['url'], 'mtime' => $it['time']];
        }
        return json(['state' => 'SUCCESS', 'start' => 0, 'total' => count($out), 'list' => $out]);
    }
}
