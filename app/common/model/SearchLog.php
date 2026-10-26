<?php
// +----------------------------------------------------------------------
// | 搜索词日志模型（表 yy_{lang}_search_log）
// | 记录前台搜索词，供热门搜索/后台搜索词排行
// +----------------------------------------------------------------------
namespace app\common\model;

class SearchLog extends LangModel
{
    protected $name = 'search_log';
    protected $autoWriteTimestamp = false;

    /**
     * 记录一次搜索
     */
    public static function record($keyword, $resultCount)
    {
        $kw = trim((string)$keyword);
        if ($kw === '' || mb_strlen($kw, 'utf-8') > 100) return;
        static::create([
            'keyword' => $kw,
            'result_count' => (int)$resultCount,
            'ip' => (string)(request()->ip() ?: ''),
            'create_time' => time(),
        ]);
    }

    /**
     * 热门搜索词 Top N（全部时间）
     */
    public static function hot($num = 10)
    {
        return static::field('keyword, COUNT(*) AS cnt')
            ->group('keyword')
            ->order('cnt', 'desc')
            ->limit($num)
            ->select();
    }
}
