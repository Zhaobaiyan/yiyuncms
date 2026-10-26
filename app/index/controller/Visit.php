<?php
// +----------------------------------------------------------------------
// | 前台访问统计打点接口（静态页兜底：JS 上报访问，去重由模型处理）
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\VisitorLog;

class Visit extends FrontBase
{
    /**
     * JS 打点：POST {url, referer}
     */
    public function track()
    {
        if (!request()->isPost()) return json(['code' => 0, 'msg' => 'method']);
        $url = (string)input('post.url', '');
        $ref = (string)input('post.referer', '');
        VisitorLog::record($url, $ref, lang_code());
        return json(['code' => 1]);
    }
}
