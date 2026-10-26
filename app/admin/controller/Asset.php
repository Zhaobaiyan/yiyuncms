<?php
// +----------------------------------------------------------------------
// | 素材库控制器（v3.10 → v3.21 磁盘扫描版）
// | 素材库直接扫描 public/upload 目录中的全部媒体文件（七牛模式仍读素材记录），
// | 因此历史上传的图片/文件也会全部展示，不再依赖素材收录表是否写入
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Asset as AssetModel;
use app\common\service\Qiniu;
use app\common\service\UploadService;

class Asset extends AdminBase
{
    public function index()
    {
        $type    = input('get.type', '');
        $keyword = trim((string)input('get.keyword', ''));
        $page    = max(1, (int)input('get.page', 1));
        $pageSize = (int)input('get.page_size', 30);
        if (!in_array($pageSize, [30, 60, 120], true)) $pageSize = 30;

        // 七牛模式：素材收录表为主；本地模式：扫描磁盘
        if (Qiniu::enabled()) {
            $query = AssetModel::order('id', 'desc');
            if (in_array($type, ['image', 'file'], true)) $query->where('type', $type);
            if ($keyword !== '') $query->where('name', 'like', '%' . $keyword . '%');
            $list = $query->paginate([
                'list_rows' => $pageSize,
                'page'      => $page,
                'query'     => ['type' => $type, 'keyword' => $keyword],
            ]);
            return view('asset/index', [
                'list' => $list, 'type' => $type, 'keyword' => $keyword, 'page_size' => $pageSize,
            ]);
        }

        $all = UploadService::mediaAll('all');
        if (in_array($type, ['image', 'file'], true)) {
            $all = array_values(array_filter($all, function ($v) use ($type) {
                return $type === 'image' ? $v['is_img'] : !$v['is_img'];
            }));
        }
        if ($keyword !== '') {
            $kw = mb_strtolower($keyword);
            $all = array_values(array_filter($all, function ($v) use ($kw) {
                return mb_strpos(mb_strtolower((string)$v['name']), $kw) !== false;
            }));
        }
        $total = count($all);
        $slice = array_slice($all, ($page - 1) * $pageSize, $pageSize);
        $rows  = [];
        foreach ($slice as $i => $v) {
            $rows[] = [
                'id'          => 'f' . md5((string)$v['url']),
                'type'        => $v['is_img'] ? 'image' : 'file',
                'path'        => $v['url'],
                'name'        => $v['name'],
                'ext'         => strtolower(pathinfo((string)$v['url'], PATHINFO_EXTENSION)),
                'size'        => $v['size'],
                'create_time' => $v['time'],
            ];
        }
        // 构造分页对象（保持视图 {list|raw} 渲染页码）
        $paginator = \think\paginator\driver\Bootstrap::make(
            $rows, $pageSize, $page, $total, false,
            ['path' => request()->baseUrl(), 'query' => ['type' => $type, 'keyword' => $keyword, 'page_size' => $pageSize]]
        );

        return view('asset/index', [
            'list' => $paginator, 'type' => $type, 'keyword' => $keyword, 'page_size' => $pageSize,
        ]);
    }

    public function delete($id = 0)
    {
        // 本地模式：id 形如 f{md5}（由 URL 派生）→ 删除磁盘文件
        $id = (string)$id;
        if (strpos($id, 'f') === 0 && strlen($id) === 33) {
            $all  = UploadService::mediaAll('all');
            $hit  = null;
            foreach ($all as $v) {
                if ('f' . md5((string)$v['url']) === $id) { $hit = $v; break; }
            }
            if (!$hit) return $this->fail('素材不存在');
            $file = public_path() . ltrim((string)$hit['url'], '/');
            $ok = false;
            if (is_file($file)) {
                $ok = @unlink($file);
            }
            // 清理素材收录表同名记录（含所有语言表）
            $langs = \app\common\model\Lang::where('is_open', 1)->column('code');
            foreach ($langs as $lc) {
                try {
                    \think\facade\Db::name($lc . '_asset')->where('path', (string)$hit['url'])->delete();
                } catch (\Throwable $e) {}
            }
            if (!$ok) return $this->fail('文件删除失败（可能被占用或只读）');
            AdminLog::record('asset_delete', '删除素材：' . $hit['name']);
            return $this->ok('已删除', (string)url('admin/asset/index'));
        }
        // 兼容旧版：按素材记录 ID 删除
        $row = AssetModel::find((int)$id);
        if (!$row) return $this->fail('素材不存在');
        $row->delete();
        AdminLog::record('asset_delete', '删除素材：' . $row['name']);
        return $this->ok('已删除', (string)url('admin/asset/index'));
    }
}
