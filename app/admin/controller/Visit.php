<?php
// +----------------------------------------------------------------------
// | 浏览管理控制器：前台浏览量统计（日/周/月/年 + 地区/关键词/搜索引擎/浏览器/设备排行）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\VisitorLog;

class Visit extends AdminBase
{
    public function index()
    {
        $range = (string)input('get.range', 'today');
        if (!in_array($range, ['today', 'yesterday', 'week', 'month', 'year', 'all'])) $range = 'today';
        [$from, $to] = VisitorLog::range($range);

        // 总览：今日/昨日/本周/本月/本年
        [$tf, $tt] = VisitorLog::range('today');
        [$yf, $yt] = VisitorLog::range('yesterday');
        [$wf, $wt] = VisitorLog::range('week');
        [$mf, $mt] = VisitorLog::range('month');
        [$yaf, $yat] = VisitorLog::range('year');

        $data = [
            'today'     => VisitorLog::countPv($tf, $tt),
            'yesterday' => VisitorLog::countPv($yf, $yt),
            'week'      => VisitorLog::countPv($wf, $wt),
            'month'     => VisitorLog::countPv($mf, $mt),
            'year'      => VisitorLog::countPv($yaf, $yat),
            'rangePv'   => VisitorLog::countPv($from, $to),
        ];

        $ranks = [
            'area'     => VisitorLog::rank('area', $from, $to, 10),
            'keyword'  => VisitorLog::rank('keyword', $from, $to, 10),
            'engine'   => VisitorLog::rank('engine', $from, $to, 10),
            'browser'  => VisitorLog::rank('browser', $from, $to, 10),
            'device'   => VisitorLog::rank('device', $from, $to, 5),
            'os'       => VisitorLog::rank('os', $from, $to, 8),
            'page'     => VisitorLog::rank('page_url', $from, $to, 10),
        ];

        $recent = VisitorLog::recent(20);

        return view('visit/index', [
            'range'  => $range,
            'data'   => $data,
            'ranks'  => $ranks,
            'recent' => $recent,
        ]);
    }

    /**
     * 清理浏览统计（可选）
     */
    public function clear()
    {
        if ((int)input('post.only_spider', 0) === 1) {
            VisitorLog::where('device', 'spider')->delete();
            AdminLog::record('visit_clear', '清理爬虫浏览记录');
            return $this->ok('爬虫浏览记录已清理', (string)url('admin/visit/index'));
        }
        VisitorLog::where('id', '>', 0)->delete();
        AdminLog::record('visit_clear', '清空全部浏览统计');
        return $this->ok('浏览统计已清空', (string)url('admin/visit/index'));
    }
}
