<?php
// +----------------------------------------------------------------------
// | 素材库模型（表 yy_{lang}_asset）
// +----------------------------------------------------------------------
namespace app\common\model;

class Asset extends LangModel
{
    protected $name = 'asset';
    protected $autoWriteTimestamp = false;

    /**
     * 新增素材
     */
    public static function add($name, $path, $type, $ext, $size)
    {
        return static::create([
            'name' => (string)$name,
            'path' => (string)$path,
            'type' => in_array($type, ['image', 'file'], true) ? $type : 'file',
            'ext'  => strtolower((string)$ext),
            'size' => (int)$size,
            'create_time' => time(),
        ]);
    }
}
