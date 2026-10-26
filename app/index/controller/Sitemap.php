<?php
// +----------------------------------------------------------------------
// | 前台站点地图控制器（v3.10）
// | 动态输出 /sitemap（XML）；静态构建会生成 public/sitemap.xml 物理文件，
// | 搜索引擎请求 /sitemap.xml 时由 Web 服务器直接读取物理文件，无需伪静态规则
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\BaseController;
use app\common\service\SitemapService;

class Sitemap extends BaseController
{
    public function index()
    {
        $xml = SitemapService::build();
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
