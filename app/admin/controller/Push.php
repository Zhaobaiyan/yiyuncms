<?php
// +----------------------------------------------------------------------
// | 百度/Google 收录推送控制器（v3.10）
// | 百度站长平台主动推送（普通收录）：POST https://data.zz.baidu.com/urls?site=..&token=..
// | token 配置：网站设置 → SEO 参数 → 百度推送 Token；或本页内直接填写保存
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Content;

class Push extends AdminBase
{
    public function index()
    {
        // 最新已发布内容（供勾选推送）
        $list = Content::published()
            ->field('id,title,catid,create_time')
            ->order('id', 'desc')
            ->limit(50)
            ->select();

        $token = (string)get_config('seo.baidu_token', '');
        $site  = rtrim((string)get_config('site.url', ''), '/');

        if (request()->isPost()) {
            // 保存 token
            $token = trim((string)input('post.baidu_token', ''));
            \app\common\model\Config::setValue('seo.baidu_token', $token);
            AdminLog::record('push_token', '更新百度推送 Token');
            return $this->ok('Token 已保存', (string)url('admin/push/index'));
        }

        return view('push/index', [
            'list'  => $list,
            'token' => $token,
            'site'  => $site,
        ]);
    }

    /**
     * 执行推送：POST ids=1,2,3
     */
    public function push()
    {
        $ids = input('post.ids', 0);
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids = array_map('intval', $ids);
        if (!$ids) return $this->fail('请先选择要推送的内容');

        $token = (string)get_config('seo.baidu_token', '');
        if ($token === '') return $this->fail('请先在网站设置中配置百度推送 Token（百度站长平台获取）');
        $site = rtrim((string)get_config('site.url', ''), '/');
        if ($site === '') return $this->fail('请先配置网站地址 site.url');

        // 生成要推送的 URL（动态链接）
        $urls = [];
        $rows = Content::whereIn('id', $ids)->select();
        foreach ($rows as $row) {
            $urls[] = $site . url('show/index', ['id' => $row['id']])->build();
        }
        if (!$urls) return $this->fail('未找到内容');

        $resp = $this->baiduPush($site, $token, $urls);
        AdminLog::record('push', '百度收录推送 ' . count($urls) . ' 条：' . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $this->ok('推送完成：' . json_encode($resp, JSON_UNESCAPED_UNICODE), (string)url('admin/push/index'));
    }

    protected function baiduPush($site, $token, $urls)
    {
        $api = 'https://data.zz.baidu.com/urls?site=' . urlencode($site) . '&token=' . urlencode($token);
        $body = implode("\n", $urls);
        $ch = curl_init($api);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: text/plain'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $out = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($out === false) return ['error' => $err];
        $data = json_decode((string)$out, true);
        return is_array($data) ? $data : ['raw' => $out];
    }
}
