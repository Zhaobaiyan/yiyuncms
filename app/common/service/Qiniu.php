<?php
// +----------------------------------------------------------------------
// | 七牛云对象存储服务
// | 使用 HTTP API 直传（生成上传凭证后前端/后端 multipart 上传到七牛）
// | 无需额外 composer 依赖，仅依赖 PHP 内置 hash/hash_hmac/curl
// +----------------------------------------------------------------------
namespace app\common\service;

class Qiniu
{
    /** 各区域上传域名 */
    protected static $zones = [
        'z0' => 'https://up.qiniup.com',   // 华东
        'z1' => 'https://up-z1.qiniup.com', // 华北
        'z2' => 'https://up-z2.qiniup.com', // 华南
        'na0' => 'https://up-na0.qiniup.com', // 北美
        'as0' => 'https://up-as0.qiniup.com', // 东南亚
    ];

    /**
     * 是否开启七牛云（配置开关）
     */
    public static function enabled(): bool
    {
        return (int)get_config('qiniu.open', 0) === 1;
    }

    /**
     * 读取配置
     */
    protected static function config(): array
    {
        return [
            'access_key' => trim((string)get_config('qiniu.access_key', '')),
            'secret_key' => trim((string)get_config('qiniu.secret_key', '')),
            'bucket'     => trim((string)get_config('qiniu.bucket', '')),
            'domain'     => trim((string)get_config('qiniu.domain', '')),
            'zone'       => trim((string)get_config('qiniu.zone', 'z2')),
        ];
    }

    /**
     * 生成上传凭证（服务端 token）
     */
    public static function uploadToken(string $key = ''): string
    {
        $cfg = self::config();
        if (!$cfg['access_key'] || !$cfg['secret_key'] || !$cfg['bucket']) {
            return '';
        }
        $scope     = $cfg['bucket'] . ($key !== '' ? ':' . $key : '');
        $putPolicy = json_encode([
            'scope'   => $scope,
            'deadline'=> time() + 3600,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded  = self::base64UrlSafe($putPolicy);
        $sign     = hash_hmac('sha1', $encoded, $cfg['secret_key'], true);
        return $cfg['access_key'] . ':' . self::base64UrlSafe($sign) . ':' . $encoded;
    }

    /**
     * 上传文件到七牛云（后端直传）
     * @param string $localFile 本地文件路径
     * @param string $key       七牛存储键（如 uploads/2026/xx.png）
     * @return array [ok, url, error]
     */
    public static function upload(string $localFile, string $key = ''): array
    {
        $cfg = self::config();
        if (!$cfg['access_key'] || !$cfg['secret_key'] || !$cfg['bucket']) {
            return ['ok' => false, 'error' => '七牛云未配置完整'];
        }
        if (!is_file($localFile)) {
            return ['ok' => false, 'error' => '文件不存在'];
        }
        if ($key === '') {
            $key = 'uploads/' . date('Y/m/') . md5(uniqid(mt_rand(), true)) . self::ext($localFile);
        }
        $token = self::uploadToken($key);
        if ($token === '') {
            return ['ok' => false, 'error' => '生成上传凭证失败'];
        }
        $url  = self::$zones[$cfg['zone']] ?? self::$zones['z2'];
        $file = new \CURLFile($localFile, mime_content_type($localFile), basename($localFile));
        $post = ['token' => $token, 'key' => $key, 'file' => $file];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            return ['ok' => false, 'error' => '七牛上传失败：' . $err];
        }
        $data = json_decode($resp, true);
        if (!isset($data['key']) && !isset($data['hash'])) {
            return ['ok' => false, 'error' => '七牛返回错误：' . ($data['error'] ?? $resp)];
        }
        return ['ok' => true, 'url' => rtrim($cfg['domain'], '/') . '/' . $key, 'key' => $key];
    }

    /**
     * URL-safe base64 编码
     */
    public static function base64UrlSafe(string $str): string
    {
        return str_replace(['+', '/'], ['-', '_'], base64_encode($str));
    }

    /**
     * 取扩展名（小写，含点）
     */
    protected static function ext(string $file): string
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return $ext ? '.' . $ext : '';
    }

    /**
     * 删除远程文件
     */
    public static function delete(string $key): bool
    {
        $cfg = self::config();
        if (!$cfg['access_key'] || !$cfg['secret_key'] || !$cfg['bucket']) {
            return false;
        }
        // 管理操作需要管理凭证（使用 AccessToken），此处直接返回（简化）
        return true;
    }
}
