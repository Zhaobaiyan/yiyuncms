<?php
// +----------------------------------------------------------------------
// | 前台内容详情控制器
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\Category;
use app\common\model\Content;

class Show extends FrontBase
{
    /**
     * 内容详情页
     */
    public function index()
    {
        $id = (int)input('param.id', 0);
        $content = Content::where('id', $id)->find();
        // 分表归档路由（v3.10.2）：主表未命中时按年份倒序查归档表，URL 不变
        $isHis = false;
        if (!$content) {
            foreach (content_his_tables(lang_code()) as $tbl) {
                $row = Db::table($tbl)->where('id', $id)->find();
                if ($row) { $content = $row; $isHis = true; break; }
            }
        }
        if (!$content) {
            abort(404, '内容不存在');
        }
        // 下架/草稿/未到定时发布时间的详情页仅限后台登录者可预览；未登录者弹窗提示“内容未公开”，3秒后返回首页
        $notPublished = $content['status'] != 1
            || ((int)$content['publish_time'] > 0 && (int)$content['publish_time'] > time());
        if ($notPublished && !(int)session('admin_id')) {
            $home = (lang_code() != default_lang() ? '/' . lang_code() : '/');
            $html = '<!DOCTYPE html><html lang="zh"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>内容未公开</title><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,"Microsoft YaHei",sans-serif;background:#f5f6f8;height:100vh;display:flex;align-items:center;justify-content:center}.modal{background:#fff;border-radius:12px;padding:40px 50px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.12);max-width:420px;width:90%}.modal .icon{width:64px;height:64px;margin:0 auto 18px;border-radius:50%;background:#fef3c7;line-height:64px;font-size:30px;color:#d97706}.modal h2{font-size:20px;color:#333;margin-bottom:10px}.modal p{color:#888;font-size:14px;margin-bottom:24px}.modal .btn{display:inline-block;padding:10px 32px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-size:15px}.modal .count{color:#aaa;font-size:12px;margin-top:14px}</style></head><body><div class="modal"><div class="icon">!</div><h2>内容未公开</h2><p>该内容尚未公开发布，即将返回网站首页</p><a class="btn" href="' . $home . '">返回网站首页</a><div class="count"><span id="s">3</span> 秒后自动返回首页</div></div><script>var t=3;var el=document.getElementById(\'s\');var timer=setInterval(function(){t--;el.textContent=t;if(t<=0){clearInterval(timer);location.href=\'' . $home . '\';}},1000);</script></body></html>';
            return response($html);
        }

        $cat = Category::find($content['catid']);
        if ($isHis) {
            // 归档数据只自增主表浏览量失败无碍，跳过
        } elseif ($content['status'] == 1 && is_object($content)) {
            $content->incViews($id);
        }

        // 附件链接重写：正文/描述中的 /upload/ 附件改为下载端点（按链接文字 + 原后缀命名）
        $content['content']     = attach_download_rewrite($content['content']);
        $content['description'] = attach_download_rewrite($content['description']);

        // 发稿人/审核人（后台角色投稿审核机制，前台可调用 author_name / audit_name）
        $people = content_people($content['author_uid'] ?? 0, $content['audit_uid'] ?? 0);
        $content['author_name'] = $people['author_name'];
        $content['audit_name']  = $people['audit_name'];
        $content['author_uid']  = (int)($content['author_uid'] ?? 0);
        $content['audit_uid']   = (int)($content['audit_uid'] ?? 0);
        $content['audit_time']  = (int)($content['audit_time'] ?? 0);
        // 审核状态：0正常 1待审核 2已驳回；提交次数；最新驳回原因（前台模板可调用）
        $content['audit_status']  = (int)($content['audit_status'] ?? 0);
        $content['submit_count']  = (int)($content['submit_count'] ?? 0);
        $content['reject_reason'] = (string)($content['reject_reason'] ?? '');

        // 云优模板兼容：为内容行注入云优别名键（desc/click/pic/ftitle/piclist），
        // 使 {$content.desc} / {$content.click} / {$content.pic} / {$content.piclist} 可直接调用
        $content['desc']    = $content['description'] ?? '';
        $content['click']   = $content['views'] ?? 0;
        $content['pic']     = $content['thumb'] ?? '';
        $content['ftitle']  = $content['short_title'] ?? '';
        $_ext = json_decode((string)($content['extend'] ?? ''), true);
        $content['piclist'] = is_array($_ext) && !empty($_ext['piclist']) ? implode('***', (array)$_ext['piclist']) : '';

        // 云优模板兼容：上下条（{$content.nexturl/nexttitle/prevurl/prevtitle}，云优命名与直觉相反）
        $prevRow = Content::published()->where('catid', $content['catid'])->where('id', '<', $content['id'])->order('id', 'desc')->find();
        $nextRow = Content::published()->where('catid', $content['catid'])->where('id', '>', $content['id'])->order('id', 'asc')->find();
        $content['nexturl']   = $prevRow ? url_content($prevRow) : '';
        $content['nexttitle'] = $prevRow ? $prevRow['title'] : '';
        $content['prevurl']   = $nextRow ? url_content($nextRow) : '';
        $content['prevtitle'] = $nextRow ? $nextRow['title'] : '';

        // 内容模板：优先所属栏目 content_template（show_xxx），否则默认 show
        $tpl = ($cat && $cat['content_template']) ? $cat['content_template'] : 'show';
        $tplDir = root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir();
        if (!is_file($tplDir . DIRECTORY_SEPARATOR . $tpl . '.html')) $tpl = 'show';

        // 评论列表（v3.10）：已通过评论，模板 {foreach $comments as $cm} 调用；评论开关关闭则不注入（v3.16）
        $comments = [];
        if ((int)get_config('comment.open', 1) === 1) {
            try { $comments = \app\common\model\Comment::listByContent((int)$content['id'], 100); } catch (\Throwable $e) { $comments = []; }
        }

        // 商品价格信息（v3.14 商城演示）：有 yy_shop 记录则注入 price/original_price/stock/sales；商城开关关闭时不注入（v3.16）
        $shop = null;
        if ((int)get_config('shop.open', 1) === 1) {
            try {
                $shop = \think\facade\Db::name('shop')->where('lang', lang_code())->where('conid', (int)$content['id'])->find();
            } catch (\Throwable $e) {
                $shop = null;
            }
        }
        if ($shop) {
            $content['price']          = (float)$shop['price'];
            $content['original_price'] = (float)$shop['original_price'];
            $content['stock']          = (int)$shop['stock'];
            $content['sales']          = (int)$shop['sales'];
            $content['shop_status']    = (int)$shop['status'];
        } else {
            $content['price'] = $content['original_price'] = $content['stock'] = $content['sales'] = 0;
            $content['shop_status'] = 0;
        }

        // 收藏状态（v3.14）：当前会员是否已收藏本文；收藏开关关闭则恒 false（v3.16）
        $favStatus = false;
        if ((int)get_config('favorite.open', 1) === 1 && session('member_id')) {
            try {
                $favStatus = \app\common\model\Favorite::where('member_id', (string)(int)session('member_id'))->where('content_id', (int)$content['id'])->count() > 0;
            } catch (\Throwable $e) {
                $favStatus = false;
            }
        }

        return view('/' . $tpl, [
            'category' => $cat,
            'content'  => $content,
            'comments' => $comments,
            'extend_fields' => $this->extendFields($content),
            'is_single' => false,
            'cur_catid' => $cat ? $cat['id'] : 0,
            'cid'      => $cat ? $cat['id'] : 0,
            'shop'     => $shop,
            'fav_status' => $favStatus,
        ]);
    }

    /**
     * 评论提交接口（v3.10）：POST /show/comment?id=内容ID
     * 参数：name 称呼、content 评论内容、code 验证码（后台设置开启时必填）
     * 审核开关：网站设置 comment.audit=1（默认）时评论先待审，管理员后台通过后展示
     */
    public function comment()
    {
        if (!request()->isPost()) return json(['code' => 0, 'msg' => '非法请求']);
        // 评论开关（v3.16）：关闭则拒绝提交
        if ((int)get_config('comment.open', 1) !== 1) {
            return json(['code' => 0, 'msg' => '评论功能已关闭']);
        }
        $id      = (int)input('post.id', 0);
        $name    = trim((string)input('post.name', ''));
        $content = trim((string)input('post.content', ''));
        $contentId = $id ?: (int)input('get.id', 0);

        $contentRow = Content::find($contentId);
        if (!$contentRow || $contentRow['status'] != 1) {
            return json(['code' => 0, 'msg' => '内容不存在或未公开']);
        }
        if ($name === '' || mb_strlen($name, 'utf-8') > 30) {
            return json(['code' => 0, 'msg' => '请输入称呼（30字以内）']);
        }
        if ($content === '' || mb_strlen($content, 'utf-8') < 2 || mb_strlen($content, 'utf-8') > 500) {
            return json(['code' => 0, 'msg' => '评论内容需 2-500 字']);
        }
        // 验证码（开关开启时必填）
        if ((int)get_config('comment.captcha', 0) === 1) {
            $code = input('post.code', '');
            if (!captcha_check($code)) return json(['code' => 0, 'msg' => '验证码错误']);
        }
        // 评论审核开关
        $audit = (int)get_config('comment.audit', 1) === 1;
        \app\common\model\Comment::create([
            'content_id' => $contentId,
            'catid'      => (int)$contentRow['catid'],
            'username'   => $name,
            'content'    => mb_substr($content, 0, 500, 'utf-8'),
            'status'     => $audit ? 0 : 1,
            'ip'         => (string)(request()->ip() ?: ''),
            'create_time'=> time(),
        ]);
        return json(['code' => 1, 'msg' => $audit ? '评论已提交，审核通过后展示' : '评论成功']);
    }

    /**
     * 评论验证码（前台评论表单调用，后台开启验证码时使用）
     */
    public function commentCaptcha()
    {
        return captcha();
    }

    /**
     * 解析扩展字段为 [label, value] 展示数组（按模型字段定义）
     */
    protected function extendFields($content): array
    {
        $extend = json_decode((string)$content['extend'], true);
        if (!is_array($extend) || !$extend) {
            return [];
        }
        $fields = \app\common\model\ModelField::where('model_id', $content['model_id'])->select();
        $out = [];
        foreach ($fields as $f) {
            if (!array_key_exists($f['name'], $extend)) continue;
            $v = $extend[$f['name']];
            if (is_array($v)) $v = implode(', ', $v);
            if ($v === '' || $v === null) continue;
            $out[] = ['label' => $f['label'], 'value' => $v, 'type' => $f['type']];
        }
        // 富文本类型的扩展字段同样重写附件下载链接
        foreach ($out as &$item) {
            if ($item['type'] === 'richtext') {
                $item['value'] = attach_download_rewrite($item['value']);
            }
        }
        unset($item);
        return $out;
    }
}
