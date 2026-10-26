<?php
// +----------------------------------------------------------------------
// | 大数据量分表控制器（v3.10.2）
// | 内容表按年份归档分表：yy_{lang}_content_YYYY
// | 前台详情页自动路由：主表未命中时按年份倒序查归档表（URL 不变）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Lang;
use app\common\service\ShardService;
use think\facade\Db;

class Shard extends AdminBase
{
    /**
     * 分表管理首页：数据量 + 归档表 + 设置
     */
    public function index()
    {
        $langs   = Lang::where('is_open', 1)->order('sort', 'asc')->select()->toArray();
        $curLang = session('admin_lang') ?: default_lang();

        // 各语言主表行数 + 归档表
        $stats = [];
        foreach ($langs as $l) {
            $stats[$l['code']] = [
                'name'  => $l['name'],
                'code'  => $l['code'],
                'main'  => Db::name($l['code'] . '_content')->count(),
                'archs' => [],
            ];
            foreach (content_his_tables($l['code']) as $tbl) {
                $stats[$l['code']]['archs'][$tbl] = Db::table($tbl)->count();
            }
        }

        return view('shard/index', [
            'stats'    => $stats,
            'curLang'  => $curLang,
            'keepYears'=> (int)get_config('shard.keep_years', 2),
        ]);
    }

    /**
     * 保存保留年数设置
     */
    public function setting()
    {
        $keep = max(0, min(10, (int)input('post.keep_years', 2)));
        set_config('shard.keep_years', $keep);
        AdminLog::record('shard_setting', '设置分表保留年数：' . $keep);
        return $this->ok('已保存', (string)url('admin/shard/index'));
    }

    /**
     * 执行分表（POST）：把早于阈值年份的内容迁入对应年份归档表
     */
    public function run()
    {
        if (!request()->isPost()) return json(['code' => 0, 'msg' => '非法请求']);
        $lang = (string)input('post.lang', 'cn');
        $keep = (int)input('post.keep_years', 2);
        $result = ShardService::run($lang, $keep);
        if ($result['code'] === 1) {
            AdminLog::record('shard_run', '执行分表：' . $lang . ' 迁移 ' . $result['moved'] . ' 条');
        }
        return json($result);
    }

    /**
     * 合并回主表（POST）
     */
    public function merge()
    {
        if (!request()->isPost()) return json(['code' => 0, 'msg' => '非法请求']);
        $lang = (string)input('post.lang', 'cn');
        $year = (int)input('post.year', 0);
        $result = ShardService::merge($lang, $year);
        if ($result['code'] === 1) {
            AdminLog::record('shard_merge', '归档表回迁：' . $lang . '_content_' . $year . '（' . $result['moved'] . '条）');
        }
        return json($result);
    }
}
