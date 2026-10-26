<?php
// +----------------------------------------------------------------------
// | 前台设备切换控制器（电脑版 / 手机版切换，存 Cookie）
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\BaseController;
use think\facade\Cookie;

class Device extends BaseController
{
    public function set()
    {
        $client = strtolower((string)$this->request->param('client', ''));
        $back   = (string)$this->request->param('back', '/');
        if (in_array($client, ['pc', 'mobile'])) {
            Cookie::set('force_client', $client);
        }
        return redirect($back);
    }
}
