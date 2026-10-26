<?php
// +----------------------------------------------------------------------
// | 前台控制器基类
// | 按当前语言 + 设备选择模板目录：template/{lang} 或 template/{lang}/wap
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\BaseController;
use think\facade\Cookie;
use think\facade\View;

class FrontBase extends BaseController
{
    /** 当前是否为手机版视图 */
    protected $isWap = false;

    /**
     * 功能关闭提示页（v3.16）
     * 优先渲染 template/{语言}/sys_close.html（可选 sys_close 自定义），无则内置提示
     */
    protected function renderSystemClosed($feature)
    {
        $names = [
            'member'   => '会员系统',
            'shop'     => '商城系统',
            'service'  => '客服系统',
            'comment'  => '评论功能',
            'favorite' => '收藏功能',
        ];
        $name = $names[$feature] ?? '该功能';
        $lang = lang_code();
        $dir  = lang_tpl_dir($lang) . ($this->isWap ? DIRECTORY_SEPARATOR . 'wap' : '');
        $tpl  = root_path() . 'template' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'sys_close.html';
        if (is_file($tpl)) {
            View::assign('sys_close_name', $name);
            $html = View::fetch('/sys_close');
        } else {
            $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>功能未开启</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4f6fb;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0} .box{background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(30,60,120,.12);padding:48px 56px;text-align:center} h1{font-size:22px;color:#1e293b;margin:0 0 12px} p{color:#64748b;margin:0 0 24px} a{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 28px;border-radius:8px;font-size:14px}</style></head><body><div class="box"><h1>功能未开启</h1><p>' . $name . '暂未开启，如需使用请联系网站管理员。</p><a href="' . url('index/index')->build() . '">返回网站首页</a></div></body></html>';
        }
        throw new \think\exception\HttpResponseException(response($html));
    }

    protected function initialize()
    {
        parent::initialize();

        // 前台分页使用自定义驱动（翻页样式见网站设置 → 前台翻页样式）
        \think\Paginator::maker(function (...$args) {
            return new \app\common\paginator\YyPaginator(...$args);
        });

        $lang     = lang_code();
        $wapOpen  = (int)get_config('wap.open', 0) === 1;
        $client   = (string)Cookie::get('force_client', '');
        $isMobile = is_mobile();

        if ($wapOpen) {
            if ($client === 'pc') {
                $this->isWap = false;
            } elseif ($client === 'mobile') {
                $this->isWap = true;
            } else {
                $auto = (int)get_config('wap.auto_redirect', 0) === 1;
                $this->isWap = $auto && $isMobile;
            }
        }

        $dir = lang_tpl_dir($lang) . ($this->isWap ? DIRECTORY_SEPARATOR . 'wap' : '');
        View::config([
            'view_path' => root_path() . 'template' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR,
        ]);

        // 供模板使用
        View::assign('is_wap', $this->isWap);
        View::assign('lang_code', $lang);
        View::assign('cur_url', (string)request()->url(true));

        // 系统功能开关（v3.16）：注入模板供隐藏入口，并拦截对应控制器
        $sysOpen = [
            'member'   => (int)get_config('member.open', 1) === 1,
            'shop'     => (int)get_config('shop.open', 1) === 1,
            'service'  => (int)get_config('service.open', 1) === 1,
            'comment'  => (int)get_config('comment.open', 1) === 1,
            'favorite' => (int)get_config('favorite.open', 1) === 1,
        ];
        View::assign('sys_open', $sysOpen);

        // 控制器级拦截：关闭的功能页面禁止访问
        $ctrl = strtolower(request()->controller());
        $ctrlSwitch = ['member' => 'member', 'shop' => 'shop', 'service' => 'service'];
        if (isset($ctrlSwitch[$ctrl]) && !$sysOpen[$ctrlSwitch[$ctrl]]) {
            $this->renderSystemClosed($ctrlSwitch[$ctrl]);
        }

        // 会员登录状态 + 购物车数量（v3.14 前台功能展示）
        $member = session('member_id') ? session('member') : null;
        View::assign('front_member', $member);
        $cartCount = 0;
        if (session('member_id') && $sysOpen['shop']) {
            try {
                $cartCount = (int)\think\facade\Db::name('cart')->where('member_id', (int)session('member_id'))->sum('num');
            } catch (\Throwable $e) {
                $cartCount = 0;
            }
        }
        View::assign('cart_count', $cartCount);

        // 前台访问统计（排除设备切换/附件下载/非 GET 请求）
        $ctrl = strtolower(request()->controller());
        if (!in_array($ctrl, ['device', 'download']) && request()->isGet()) {
            try {
                \app\common\model\VisitorLog::record(
                    (string)request()->url(true),
                    (string)request()->header('referer', ''),
                    $lang
                );
            } catch (\Throwable $e) {
                // 统计失败不影响页面
            }
        }
    }
}
