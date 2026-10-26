<?php
// +----------------------------------------------------------------------
// | 通知中心服务（v3.24）
// | 管理员级消息通知：客服新消息 / 新留言
// | 渠道：QQ邮箱等 SMTP（复用 MailService）、企业微信机器人、通用短信 HTTP 通道
// | 每个管理员独立开关与渠道配置（yy_admin 表 notify_* 字段），互不影响
// +----------------------------------------------------------------------
namespace app\common\service;

use think\facade\Db;

class NotifyService
{
    /**
     * 通知所有开启指定类型通知的管理员（各渠道独立发送，失败不影响主流程）
     * @param string $type    service=客服新消息 / guestbook=新留言
     * @param string $title   通知标题
     * @param string $content 通知正文（纯文本，自动截断）
     * @param string $link    后台链接（供邮件模板使用，可选）
     * @return array 各管理员发送结果汇总
     */
    public static function notifyAdmins($type, $title, $content, $link = '')
    {
        $results = [];
        try {
            $admins = Db::name('admin')->where('status', 1)->select();
            foreach ($admins as $admin) {
                $on = (int)($admin['notify_' . $type] ?? 0);
                if ($on !== 1) continue;
                $results[] = self::notifyOne($admin, $title, $content, $link);
            }
        } catch (\Throwable $e) {
            $results[] = ['admin' => 0, 'channels' => [], 'err' => $e->getMessage()];
        }
        return $results;
    }

    /**
     * 按单个管理员的渠道配置发送通知
     */
    public static function notifyOne($admin, $title, $content, $link = '')
    {
        $sent = [];
        $name = (string)($admin['nickname'] ?: $admin['username']);

        // 1) 邮件（管理员通知邮箱，未填则跳过）
        $email = trim((string)($admin['notify_email'] ?? ''));
        if ($email !== '') {
            $html = self::emailBody($title, $content, $link);
            $r = MailService::send($email, $title, $html, true);
            if ((int)($r['code'] ?? 0) === 1) {
                $sent[] = 'email';
            }
        }

        // 2) 企业微信机器人（webhook）
        $webhook = trim((string)($admin['wxwork_webhook'] ?? ''));
        if ($webhook !== '') {
            $r = self::sendWxWork($webhook, '【' . $name . '】' . $title . "\n" . $content . ($link !== '' ? "\n查看：" . $link : ''));
            if ($r) $sent[] = 'wxwork';
        }

        // 3) 短信（通用 HTTP 通道，全局配置见 网站设置→通知中心）
        $phone = trim((string)($admin['notify_phone'] ?? ''));
        if ($phone !== '') {
            $r = self::sendSms($phone, $title . '：' . $content);
            if ($r) $sent[] = 'sms';
        }

        return ['admin' => (int)$admin['id'], 'name' => $name, 'channels' => $sent];
    }

    /**
     * 企业微信群机器人发送文本消息
     * @param string $webhook 机器人 webhook 地址
     * @param string $text    消息内容（<2000 字）
     * @return bool
     */
    public static function sendWxWork($webhook, $text)
    {
        if (!filter_var($webhook, FILTER_VALIDATE_URL)) return false;
        $text = mb_substr($text, 0, 1990);
        $payload = json_encode([
            'msgtype' => 'text',
            'text'    => ['content' => $text],
        ], JSON_UNESCAPED_UNICODE);
        return self::httpPost($webhook, $payload);
    }

    /**
     * 短信通用通道：管理员在「网站设置→通知中心」配置 api_url 与参数模板，
     * 占位符 {phone} / {content} 自动替换；返回 json 且含 success/errcode=0 视为成功。
     * @param string $phone   手机号
     * @param string $content 短信内容（自动截断，多数平台限 70/140 字）
     * @return bool
     */
    public static function sendSms($phone, $content)
    {
        $apiUrl = trim((string)get_config('sms.api_url', ''));
        $tpl    = (string)get_config('sms.tpl', '');
        if ($apiUrl === '' || $tpl === '') return false;
        $content = mb_substr($content, 0, 130);
        // 参数模板：可含多参数，用 & 连接，如 phone={phone}&content={content}&extno=1
        $params = str_replace(['{phone}', '{content}'], [urlencode($phone), urlencode($content)], $tpl);
        $url = $apiUrl . (strpos($apiUrl, '?') !== false ? '&' : '?') . $params;
        $body = self::httpGet($url);
        if ($body === '') return false;
        // 宽松判断：常见短信平台返回 code/errcode/status/success 类字段，0 或 true 为成功
        if (preg_match('/"?(?:code|errcode|status|result)"?\s*:\s*"?([0-9A-Za-z]+)"?/i', $body, $m)) {
            $v = strtolower($m[1]);
            return in_array($v, ['0', 'ok', 'success', 'true'], true);
        }
        return strpos($body, 'success') !== false || strpos($body, '"ok"') !== false;
    }

    /**
     * 邮件 HTML 正文
     */
    protected static function emailBody($title, $content, $link)
    {
        $site = (string)get_setting('site.name', '本站');
        $esc = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
        $html = '<div style="font-family:Arial,\'Microsoft YaHei\',sans-serif;max-width:640px;margin:0 auto">'
              . '<h2 style="color:#2563eb;margin:0 0 16px">' . $esc($site) . ' · ' . $esc($title) . '</h2>'
              . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;color:#334155;font-size:14px;line-height:1.8;white-space:pre-wrap">' . $esc($content) . '</div>';
        if ($link !== '') {
            $html .= '<p style="margin-top:16px"><a href="' . $esc($link) . '" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 24px;border-radius:8px;font-size:14px">前往后台处理</a></p>';
        }
        $html .= '<p style="color:#999;font-size:12px;margin-top:16px">本邮件由易云CMS 通知中心自动发送，请勿直接回复。</p></div>';
        return $html;
    }

    /**
     * HTTP POST（企业微信 webhook 用）
     */
    protected static function httpPost($url, $payload)
    {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return false;
        return strpos($body, '"errcode":0') !== false;
    }

    /**
     * HTTP GET（短信通道用）
     */
    protected static function httpGet($url)
    {
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        return (string)@file_get_contents($url, false, $ctx);
    }
}
