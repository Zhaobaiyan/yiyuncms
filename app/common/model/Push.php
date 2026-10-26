<?php
// +----------------------------------------------------------------------
// | 跨站/跨语言内容推送模型（全局共享表 yy_push，v3.20）
// +----------------------------------------------------------------------
namespace app\common\model;

use think\model\Pivot;

class Push extends Pivot
{
    protected $name = 'push';

    /**
     * 批量创建推送记录（内容管理/推送页共用）
     * @param string $fromLang 来源语言代码
     * @param string $fromSite 来源站点标识
     * @param string $toSite   目标站点标识
     * @param string $toLang   目标语言代码
     * @param int    $toCat    目标栏目ID
     * @param array  $ids      来源内容ID数组
     * @param int    $uid      发起人ID
     * @param string $name     发起人昵称
     * @param array|null $scope 栏目范围限制（null=不限制）
     * @return int 成功条数
     */
    public static function createPush($fromLang, $fromSite, $toSite, $toLang, $toCat, array $ids, $uid, $name, $scope = null)
    {
        $count = 0;
        $rows = \think\facade\Db::name($fromLang . '_content')->whereIn('id', $ids)->select();
        foreach ($rows as $r) {
            if ($scope !== null && !in_array((int)$r['catid'], $scope, true)) continue;
            \think\facade\Db::name('push')->insert([
                'from_site' => $fromSite, 'to_site' => $toSite,
                'from_lang' => $fromLang, 'from_catid' => (int)$r['catid'], 'from_content_id' => (int)$r['id'],
                'to_lang' => $toLang, 'to_catid' => $toCat,
                'title' => (string)$r['title'], 'status' => 0,
                'creator_uid' => (int)$uid, 'creator_name' => (string)$name,
                'create_time' => time(),
            ]);
            $count++;
        }
        return $count;
    }

    // 站点标识 → 语言代码：main=主站（全局默认语言），其他=站点别名本身
    public static function siteToLang($site)
    {
        if ($site === 'main') {
            try { return default_lang(); } catch (\Throwable $e) {}
            return 'cn';
        }
        return (string)$site;
    }

    /**
     * 把一条内容从来源语言完整复制到目标语言指定栏目（跨站推送核心）
     * @param string $fromLang 来源语言代码
     * @param int    $fromId   来源内容ID
     * @param string $toLang   目标语言代码
     * @param int    $toCatid  目标栏目ID（目标语言）
     * @param int    $creatorUid 推送发起人（作为目标内容发稿人）
     * @return array [newId, newRow]
     */
    public static function copyContent($fromLang, $fromId, $toLang, $toCatid, $creatorUid)
    {
        $src = \think\facade\Db::name($fromLang . '_content')->where('id', (int)$fromId)->find();
        if (!$src) return ['error' => '来源内容不存在或已删除'];

        $tcat = \think\facade\Db::name($toLang . '_category')->where('id', (int)$toCatid)->find();
        if (!$tcat) return ['error' => '目标栏目不存在'];

        $fields = ['model_id', 'title', 'short_title', 'author', 'source', 'thumb', 'image', 'keywords',
                   'description', 'content', 'extend', 'is_top', 'is_recommend', 'is_hot', 'sort', 'is_shop'];
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = $src[$f] ?? '';
        }
        // 图片集等扩展字段 JSON 原样复制
        $data['extend'] = (string)($src['extend'] ?? '');
        $data['catid']     = (int)$toCatid;
        $data['views']     = 0;
        $data['static_path'] = '';
        $data['status']    = 1; // 推送采纳后直接发布
        $data['audit_status'] = 0;
        $data['author_uid'] = (int)$creatorUid;
        $data['audit_uid']  = (int)$creatorUid;
        $data['audit_time'] = time();
        $data['submit_count'] = 0;
        $data['create_time'] = (int)($src['create_time'] ?? time());
        if ((int)($src['update_time'] ?? 0) > 0) $data['update_time'] = (int)$src['update_time'];
        $data['add_time'] = (int)($src['add_time'] ?? time());

        $newId = \think\facade\Db::name($toLang . '_content')->insertGetId($data);
        return ['newId' => (int)$newId, 'newRow' => $data];
    }
}
