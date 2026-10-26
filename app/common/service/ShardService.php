<?php
// +----------------------------------------------------------------------
// | 分表服务（v3.10.2）
// | 内容表按年份归档：主表 yy_{lang}_content，归档表 yy_{lang}_content_{YYYY}
// | 详情页由 Show 控制器自动路由归档表（content_his_tables + Db 回查）
// +----------------------------------------------------------------------
namespace app\common\service;

use think\facade\Db;

class ShardService
{
    /**
     * 执行分表：把早于 (今年-keep)年1月1日 的整年数据迁入对应年份归档表
     * @param string $lang 语言代码
     * @param int $keep 主表保留年数（0=全部归档）
     * @return array ['code'=>1,'msg'=>...,'moved'=>int,'tables'=>[]]
     */
    public static function run($lang, $keep)
    {
        $lang = preg_replace('/[^a-z0-9]/', '', strtolower((string)$lang));
        $keep = max(0, min(10, (int)$keep));
        $main = 'yy_' . $lang . '_content';
        if (!Db::query("SHOW TABLES LIKE '" . $main . "'")) {
            return ['code' => 0, 'msg' => '内容表不存在：' . $main];
        }

        $year  = (int)date('Y');
        $thr   = mktime(0, 0, 0, 1, 1, $year - $keep);
        $total = 0;
        $tables = [];
        for ($y = $year - $keep - 1; $y >= 2000; $y--) {
            $start = mktime(0, 0, 0, 1, 1, $y);
            $end   = mktime(0, 0, 0, 1, 1, $y + 1);
            if ($end > $thr) continue;
            $cnt = Db::table($main)->where('create_time', '>=', $start)->where('create_time', '<', $end)->count();
            if ($cnt <= 0) continue;
            $arch = $main . '_' . $y;
            Db::execute("CREATE TABLE IF NOT EXISTS `{$arch}` LIKE `{$main}`");
            Db::execute("INSERT IGNORE INTO `{$arch}` SELECT * FROM `{$main}` WHERE `create_time`>={$start} AND `create_time`<{$end}");
            Db::execute("DELETE FROM `{$main}` WHERE `create_time`>={$start} AND `create_time`<{$end}");
            $total += $cnt;
            $tables[] = $arch;
        }
        return [
            'code'   => 1,
            'msg'    => $total > 0 ? '分表完成，共迁移 ' . $total . ' 条（' . implode('、', $tables) . '）' : '无需分表：主表数据都在保留期内',
            'moved'  => $total,
            'tables' => $tables,
        ];
    }

    /**
     * 归档表合并回主表（主键冲突自动跳过）
     * @return array
     */
    public static function merge($lang, $year)
    {
        $lang = preg_replace('/[^a-z0-9]/', '', strtolower((string)$lang));
        $year = (int)$year;
        if ($year < 2000 || $year > date('Y')) {
            return ['code' => 0, 'msg' => '年份不正确'];
        }
        $main = 'yy_' . $lang . '_content';
        $arch = $main . '_' . $year;
        if (!Db::query("SHOW TABLES LIKE '" . $arch . "'")) {
            return ['code' => 0, 'msg' => '归档表不存在：' . $arch];
        }
        $cnt = Db::table($arch)->count();
        Db::execute("INSERT IGNORE INTO `{$main}` SELECT * FROM `{$arch}`");
        Db::execute("DELETE FROM `{$arch}`");
        return ['code' => 1, 'msg' => '已回迁 ' . $cnt . ' 条到主表', 'moved' => $cnt];
    }

    /**
     * 归档表列表（新→旧）
     */
    public static function tables($lang)
    {
        return content_his_tables($lang);
    }
}
