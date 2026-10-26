<?php
// +----------------------------------------------------------------------
// | 内容模型
// +----------------------------------------------------------------------
namespace app\common\model;

use think\model\concern\SoftDelete;

class Content extends LangModel
{
    use SoftDelete;

    protected $name = 'content';
    protected $autoWriteTimestamp = 'int';

    // 软删除（delete_time=0 为未删；delete() 写删除时间，查询自动过滤）
    protected $defaultSoftDelete = 0;
    protected $deleteTime = 'delete_time';

    /**
     * 前台已发布内容查询范围：status=1 且已到定时发布时间
     * 用法：ContentModel::published()->where(...)
     */
    public function scopePublished($query)
    {
        $now = time();
        $query->where('status', 1)->where(function ($q) use ($now) {
            $q->where('publish_time', 0)->whereOr('publish_time', '<=', $now);
        });
    }

    /**
     * 关联栏目
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'catid');
    }

    /**
     * 关联内容模型
     */
    public function cmsModel()
    {
        return $this->belongsTo(CmsModel::class, 'model_id');
    }

    /**
     * 发布时间格式化
     */
    public function getCreateTimeTextAttr($value, $data)
    {
        return isset($data['create_time']) && $data['create_time']
            ? date('Y-m-d H:i', $data['create_time']) : '';
    }

    /**
     * 描述（摘要）别名 desc：前台 {$xxx.desc} 调用
     */
    public function getDescAttr($value, $data)
    {
        return isset($data['description']) ? $data['description'] : '';
    }

    /**
     * 缩略图别名 pic（云优兼容）：前台 {$xxx.pic} 调用
     * 优先 thumb；其次 image；再其次图片集第一张（extend.piclist *** 分隔）
     */
    public function getPicAttr($value, $data)
    {
        if (!empty($data['thumb'])) return $data['thumb'];
        if (!empty($data['image'])) {
            $img = $data['image'];
            if (strpos($img, '***') !== false) {
                $img = explode('***', $img)[0];
            }
            return $img;
        }
        if (!empty($data['extend'])) {
            $ext = is_array($data['extend']) ? $data['extend'] : json_decode($data['extend'], true);
            if (is_array($ext) && !empty($ext['piclist'])) {
                $img = is_array($ext['piclist']) ? (implode('***', $ext['piclist'])) : (string)$ext['piclist'];
                if (strpos($img, '***') !== false) {
                    $img = explode('***', $img)[0];
                }
                return $img;
            }
        }
        return '';
    }

    /**
     * 图片集别名 piclist（云优兼容）：{$xxx.piclist} 返回 *** 分隔串
     */
    public function getPiclistAttr($value, $data)
    {
        if (!empty($data['image']) && strpos($data['image'], '***') !== false) {
            return $data['image'];
        }
        if (!empty($data['extend'])) {
            $ext = is_array($data['extend']) ? $data['extend'] : json_decode($data['extend'], true);
            if (is_array($ext) && !empty($ext['piclist'])) {
                return is_array($ext['piclist']) ? implode('***', $ext['piclist']) : (string)$ext['piclist'];
            }
        }
        return '';
    }

    /**
     * 浏览数自增
     */
    public function incViews($id)
    {
        static::where('id', $id)->inc('views')->update();
    }

    /**
     * 列表查询：按栏目聚合子栏目 + 状态 + 排序
     */
    public static function listByCategory($catid, $limit = 10, $page = 1)
    {
        $ids = Category::getChildIds($catid);
        $query = static::where('status', 1)->whereIn('catid', $ids);
        return $query->order('is_top', 'desc')
            ->order('sort', 'asc')
            ->order('create_time', 'desc')
            ->paginate($limit);
    }
}
