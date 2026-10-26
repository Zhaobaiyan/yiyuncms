<?php
// +----------------------------------------------------------------------
// | 邮件服务（v3.23）
// | 纯 PHP SMTP 客户端：支持 SSL(465) / STARTTLS(587) / 无加密(25)
// | 用途：前台新留言邮件通知（后台开关 smtp 配置见 网站设置→邮件通知）
// +----------------------------------------------------------------------
namespace app\common\service;

class MailService
{
    /**
     * 发送邮件
     * @param string|array $to     收件人邮箱（多个用数组或英文逗号分隔）
     * @param string       $subject 主题（自动 UTF-8 编码）
     * @param string       $body    正文（支持 HTML）
     * @param bool         $isHtml  正文是否 HTML
     * @return array ['code'=>0|1, 'msg'=>...]
     */
    public static function send($to, $subject, $body, $isHtml = true)
    {
        $host = (string)get_config('smtp.host', '');
        $port = (int)get_config('smtp.port', 465);
        $secure = (string)get_config('smtp.secure', 'ssl');
        $user = (string)get_config('smtp.user', '');
        $pass = (string)get_config('smtp.pass', '');
        $from = (string)get_config('smtp.from', '');
        $fromName = (string)get_config('smtp.from_name', '');

        if ($host === '' || $user === '' || $pass === '' || $from === '') {
            return ['code' => 0, 'msg' => '邮件服务未配置（SMTP服务器/账号/授权码/发件人必填）'];
        }
        if ($to === '') {
            return ['code' => 0, 'msg' => '收件人为空'];
        }

        $toList = is_array($to) ? $to : array_map('trim', explode(',', (string)$to));
        $toList = array_values(array_filter($toList));
        if (!$toList) {
            return ['code' => 0, 'msg' => '收件人为空'];
        }

        try {
            // 建立连接（SSL 直连 / 明文连接后 STARTTLS）
            $remote = ($secure === 'ssl') ? 'ssl://' . $host . ':' . $port : $host . ':' . $port;
            $ctx = stream_context_create(['ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]]);
            $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
            if (!$fp) {
                return ['code' => 0, 'msg' => '无法连接SMTP服务器：' . $errstr];
            }
            stream_set_timeout($fp, 15);

            $r = self::read($fp);
            if (!preg_match('/^2\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => 'SMTP服务器响应异常：' . trim($r)];
            }

            // EHLO
            self::write($fp, 'EHLO ' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'));
            $r = self::readAll($fp);
            if (!preg_match('/^2\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => 'EHLO 失败：' . trim($r)];
            }

            // STARTTLS（587 常用）
            if ($secure === 'tls') {
                self::write($fp, 'STARTTLS');
                $r = self::read($fp);
                if (!preg_match('/^2\d\d/', $r)) {
                    fclose($fp);
                    return ['code' => 0, 'msg' => 'STARTTLS 失败：' . trim($r)];
                }
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($fp);
                    return ['code' => 0, 'msg' => 'TLS 加密协商失败'];
                }
                self::write($fp, 'EHLO ' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'));
                $r = self::readAll($fp);
                if (!preg_match('/^2\d\d/', $r)) {
                    fclose($fp);
                    return ['code' => 0, 'msg' => 'EHLO(TLS) 失败：' . trim($r)];
                }
            }

            // AUTH LOGIN
            self::write($fp, 'AUTH LOGIN');
            $r = self::read($fp);
            if (!preg_match('/^3\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => '服务器不支持 AUTH LOGIN：' . trim($r)];
            }
            self::write($fp, base64_encode($user));
            $r = self::read($fp);
            if (!preg_match('/^3\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => '账号校验失败：' . trim($r)];
            }
            self::write($fp, base64_encode($pass));
            $r = self::read($fp);
            if (!preg_match('/^2\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => '认证失败：账号或授权码错误（QQ/163邮箱需使用“授权码”而非登录密码）'];
            }

            // 发件人 / 收件人
            self::write($fp, 'MAIL FROM:<' . $from . '>');
            $r = self::read($fp);
            if (!preg_match('/^2\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => 'MAIL FROM 失败：' . trim($r)];
            }
            foreach ($toList as $addr) {
                self::write($fp, 'RCPT TO:<' . $addr . '>');
                $r = self::read($fp);
                if (!preg_match('/^2\d\d/', $r)) {
                    fclose($fp);
                    return ['code' => 0, 'msg' => '收件人 ' . $addr . ' 被拒绝：' . trim($r)];
                }
            }

            // 正文
            self::write($fp, 'DATA');
            $r = self::read($fp);
            if (!preg_match('/^3\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => 'DATA 失败：' . trim($r)];
            }

            $enc = function ($s) {
                return '=?UTF-8?B?' . base64_encode($s) . '?=';
            };
            $headers = [];
            $headers[] = 'From: ' . ($fromName !== '' ? $enc($fromName) . ' <' . $from . '>' : $from);
            $headers[] = 'To: ' . $toList[0];
            $headers[] = 'Subject: ' . $enc($subject);
            $headers[] = 'Date: ' . date('D, d M Y H:i:s O');
            $headers[] = 'Message-ID: <' . md5(uniqid('', true)) . '@' . ($host ?: 'localhost') . '>';
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = $isHtml
                ? 'Content-Type: text/html; charset=UTF-8'
                : 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';

            $payload = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");

            // 正文行首为 "." 需转义为 ".."（DATA 结束符判定）
            $payload = preg_replace('/^\./m', '..', $payload);

            self::write($fp, $payload);
            self::write($fp, '.');
            $r = self::read($fp);
            if (!preg_match('/^2\d\d/', $r)) {
                fclose($fp);
                return ['code' => 0, 'msg' => '邮件发送被拒绝：' . trim($r)];
            }

            self::write($fp, 'QUIT');
            fclose($fp);

            return ['code' => 1, 'msg' => '发送成功'];
        } catch (\Throwable $e) {
            return ['code' => 0, 'msg' => '发送异常：' . $e->getMessage()];
        }
    }

    /**
     * 新留言邮件通知（前台留言成功后调用）
     * @param \think\Model|array $info  留言记录
     * @param \think\Model|null  $board 留言板
     * @return array
     */
    public static function sendMessageNotify($info, $board = null)
    {
        $siteName = (string)get_setting('site.name', '本站');
        $boardName = ($board && !empty($board['name'])) ? (string)$board['name'] : '默认留言板';
        $name  = (string)($info['name'] ?? '');
        $phone = (string)($info['phone'] ?? '');
        $email = (string)($info['email'] ?? '');
        $title = (string)($info['title'] ?? '');
        $content = (string)($info['content'] ?? '');
        $ip = (string)($info['ip'] ?? '');
        $queryCode = (string)($info['query_code'] ?? '');
        $ct = $info['create_time'] ?? 0;
        $time = $ct ? date('Y-m-d H:i:s', (int)$ct) : date('Y-m-d H:i:s');
        $public = isset($info['is_public']) ? ((int)$info['is_public'] === 1 ? '公开' : '不公开') : '-';

        $subject = '【' . $siteName . '】新留言通知' . ($name !== '' ? '：' . $name : '');

        $esc = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $rows = [
            ['留言板', $esc($boardName)],
            ['称呼', $esc($name)],
            ['电话', $esc($phone)],
            ['邮箱', $esc($email)],
            ['标题', $esc($title)],
            ['留言内容', nl2br($esc($content))],
            ['IP 地址', $esc($ip)],
            ['提交时间', $esc($time)],
            ['是否公开', $esc($public)],
            ['查询码', $esc($queryCode)],
        ];
        $trs = '';
        foreach ($rows as $r) {
            $trs .= '<tr><td style="padding:8px 12px;background:#f7f8fa;border:1px solid #e5e7eb;color:#333;font-weight:600;white-space:nowrap;vertical-align:top">' . $r[0] . '</td>'
                  . '<td style="padding:8px 12px;border:1px solid #e5e7eb;color:#111;vertical-align:top">' . $r[1] . '</td></tr>';
        }
        $body = '<div style="font-family:Arial,\'Microsoft YaHei\',sans-serif;max-width:640px;margin:0 auto">'
              . '<h2 style="color:#2563eb;margin:0 0 16px">' . $esc($siteName) . ' · 新留言通知</h2>'
              . '<p style="color:#666">网站收到一条新的留言，请及时到后台「留言管理」查看处理：</p>'
              . '<table style="border-collapse:collapse;width:100%;font-size:14px">' . $trs . '</table>'
              . '<p style="color:#999;font-size:12px;margin-top:16px">本邮件由易云CMS 自动发送，请勿直接回复。</p>'
              . '</div>';

        return self::send(get_config('smtp.to', ''), $subject, $body, true);
    }

    /**
     * 写一行命令
     */
    protected static function write($fp, $line)
    {
        fwrite($fp, $line . "\r\n");
    }

    /**
     * 读一行响应
     */
    protected static function read($fp)
    {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // 多行响应以 "250-" 形式，遇到 "250 "（空格）即结束
            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    }

    /**
     * 读取多条响应（EHLO 等会返回多行）
     */
    protected static function readAll($fp)
    {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
            if (feof($fp)) {
                break;
            }
        }
        return $data;
    }
}
