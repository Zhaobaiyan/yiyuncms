<?php
// +----------------------------------------------------------------------
// | 动态语言应用映射服务
// | think-multi-app 的 MultiApp 中间件在 HttpRun 时最先注册、最先执行，
// | 只有命中 app_map 的语言前缀才会被剥离并落到 index 前台应用。
// | 因此必须在 App::initialize() 的 BootService 阶段（早于 dispatch）把
// | 后台启用的所有语言动态写入 app.app_map，实现自由添加语言版本。
// +----------------------------------------------------------------------
namespace app\common\Service;

use think\facade\Cache;
use think\Service;

class LangAppService extends Service
{
    // 保留名：不能作为语言代码（与真实应用/后台路径冲突）
    public const RESERVED = ['admin', 'index', 'common'];

    public function boot(): void
    {
        $map = $this->buildAppMap();
        $this->app->config->set(['app_map' => $map], 'app');
    }

    /**
     * 生成 app_map：所有已启用语言 -> index 前台应用
     * 使用缓存，避免每个请求都查询数据库
     */
    protected function buildAppMap(): array
    {
        try {
            $map = Cache::get('yy_lang_appmap');
            if (is_array($map)) {
                return $map;
            }
        } catch (\Throwable $e) {
        }
        $map = [];
        try {
            // boot 阶段 think-orm 的 Db 管理器尚未初始化（Model::$db 为 null），需先 connect 并注入
            app('db')->connect();
            \think\Model::setDb(app('db'));
            $codes = \app\common\model\Lang::where('is_open', 1)->column('code');
            foreach ($codes as $code) {
                if (!in_array($code, self::RESERVED)) {
                    $map[$code] = 'index';
                }
            }
        } catch (\Throwable $e) {
            // 数据库不可用（如尚未导入/未配置）：回退为空映射，前台将只能访问默认应用
        }
        try {
            Cache::set('yy_lang_appmap', $map, 3600);
        } catch (\Throwable $e) {
        }
        return $map;
    }
}