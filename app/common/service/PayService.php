<?php
// +----------------------------------------------------------------------
// | 真实支付服务层（v3.17）
// | 微信支付 Native 扫码（APIv2 统一下单/回调验签/退款）
// | 支付宝当面付（预下单 qr_code / 回调验签 / 退款）
// | 配置：shop.pay_mode=auto|test；shop.wx_mchid/wx_appid/wx_key；shop.ali_appid/ali_private_key/ali_public_key
// +----------------------------------------------------------------------
namespace app\common\service;

use think\facade\Db;

class PayService
{
    /** 微信统一下单（Native） */
    const WX_UNIFIED = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    /** 微信退款（需 APIv2 双向证书，配置证书路径才可调用） */
    const WX_REFUND  = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    /** 支付宝网关 */
    const ALI_GATE   = 'https://openapi.alipay.com/gateway.do';

    /**
     * 微信支付是否已配置真实商户号
     */
    public static function wxReady()
    {
        return (int)get_config('shop.pay_mode', 1) === 1
            && (string)get_config('shop.wx_mchid', '') !== ''
            && (string)get_config('shop.wx_appid', '') !== ''
            && (string)get_config('shop.wx_key', '') !== '';
    }

    /**
     * 支付宝是否已配置真实商户号
     */
    public static function aliReady()
    {
        return (int)get_config('shop.pay_mode', 1) === 1
            && (string)get_config('shop.ali_appid', '') !== ''
            && (string)get_config('shop.ali_private_key', '') !== ''
            && (string)get_config('shop.ali_public_key', '') !== '';
    }

    /* ==================== 微信支付（APIv2） ==================== */

    /**
     * 微信 Native 统一下单
     * @param string $orderNo 商户订单号
     * @param string $body    商品描述（≤128字符）
     * @param float  $amount  金额（元）
     * @param string $notifyUrl 回调地址（必须外网可达）
     * @return array ['code'=>1,'code_url'=>...,] 或 ['code'=>0,'msg'=>...]
     */
    public static function wxNative($orderNo, $body, $amount, $notifyUrl)
    {
        if (!self::wxReady()) return ['code' => 0, 'msg' => '微信支付未配置真实商户号'];
        $params = [
            'appid'            => (string)get_config('shop.wx_appid', ''),
            'mch_id'           => (string)get_config('shop.wx_mchid', ''),
            'nonce_str'        => self::nonceStr(24),
            'body'             => mb_substr($body, 0, 120, 'UTF-8'),
            'out_trade_no'     => $orderNo,
            'total_fee'        => (int)round($amount * 100),
            'spbill_create_ip' => self::clientIp(),
            'notify_url'       => $notifyUrl,
            'trade_type'       => 'NATIVE',
        ];
        $params['sign'] = self::wxSign($params);
        $xml = self::arrayToXml($params);
        $resp = self::postXml(self::WX_UNIFIED, $xml);
        $data = self::xmlToArray($resp);
        if (!is_array($data)) return ['code' => 0, 'msg' => '微信支付网关无响应'];
        if ((string)$data['return_code'] !== 'SUCCESS') {
            return ['code' => 0, 'msg' => '微信下单失败：' . ($data['return_msg'] ?? '未知错误')];
        }
        if ((string)$data['result_code'] !== 'SUCCESS') {
            return ['code' => 0, 'msg' => '微信下单失败：' . ($data['err_code_des'] ?? ($data['err_code'] ?? '未知错误'))];
        }
        return ['code' => 1, 'code_url' => (string)$data['code_url']];
    }

    /**
     * 微信支付回调验签 + 解析
     * @param string $rawXml 回调原始 XML
     * @return array ['code'=>1,'order_no'=>...,'total_fee'=>分] 或 ['code'=>0,'msg'=>...]
     */
    public static function wxNotifyVerify($rawXml)
    {
        $data = self::xmlToArray($rawXml);
        if (!is_array($data)) return ['code' => 0, 'msg' => 'XML解析失败'];
        if ((string)($data['return_code'] ?? '') !== 'SUCCESS') {
            return ['code' => 0, 'msg' => '支付失败通知'];
        }
        if (!self::wxVerifySign($data)) return ['code' => 0, 'msg' => '签名验证失败'];
        return [
            'code'      => 1,
            'order_no'  => (string)$data['out_trade_no'],
            'total_fee' => (int)$data['total_fee'],
            'trade_no'  => (string)($data['transaction_id'] ?? ''),
            'raw'       => $data,
        ];
    }

    /**
     * 微信支付回调成功应答
     */
    public static function wxNotifySuccess()
    {
        return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * 微信退款（APIv2，需配置 apiclient_cert.pem / apiclient_key.pem 绝对路径）
     * @param string $orderNo 商户订单号
     * @param string $refundNo 退款单号
     * @param float  $total 订单总金额（元）
     * @param float  $refund 退款金额（元）
     */
    public static function wxRefund($orderNo, $refundNo, $total, $refund)
    {
        $cert = (string)get_config('shop.wx_cert_path', '');
        $key  = (string)get_config('shop.wx_key_path', '');
        if (!self::wxReady()) return ['code' => 0, 'msg' => '微信支付未配置'];
        if ($cert === '' || !is_file($cert) || $key === '' || !is_file($key)) {
            return ['code' => 0, 'msg' => '未配置微信退款证书（apiclient_cert.pem/apiclient_key.pem），请后台填写后重试'];
        }
        $params = [
            'appid'        => (string)get_config('shop.wx_appid', ''),
            'mch_id'       => (string)get_config('shop.wx_mchid', ''),
            'nonce_str'    => self::nonceStr(24),
            'out_trade_no' => $orderNo,
            'out_refund_no'=> $refundNo,
            'total_fee'    => (int)round($total * 100),
            'refund_fee'   => (int)round($refund * 100),
        ];
        $params['sign'] = self::wxSign($params);
        $resp = self::postXml(self::WX_REFUND, self::arrayToXml($params), $cert, $key);
        $data = self::xmlToArray($resp);
        if (!is_array($data)) return ['code' => 0, 'msg' => '微信退款网关无响应'];
        if ((string)$data['return_code'] !== 'SUCCESS' || (string)$data['result_code'] !== 'SUCCESS') {
            return ['code' => 0, 'msg' => '退款失败：' . ($data['err_code_des'] ?? ($data['return_msg'] ?? '未知错误'))];
        }
        return ['code' => 1, 'msg' => '退款已提交'];
    }

    /**
     * 微信 APIv2 MD5 签名
     */
    public static function wxSign(array $params)
    {
        $key = (string)get_config('shop.wx_key', '');
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null || $k === 'sign') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str .= 'key=' . $key;
        return strtoupper(md5($str));
    }

    /**
     * 微信签名验证
     */
    public static function wxVerifySign(array $data)
    {
        $sign = (string)($data['sign'] ?? '');
        if ($sign === '') return false;
        $check = $data;
        unset($check['sign']);
        return self::wxSign($check) === strtoupper($sign);
    }

    /* ==================== 支付宝（当面付 / 电脑网站支付） ==================== */

    /**
     * 支付宝当面付预下单（返回 qr_code）
     * @param string $orderNo 商户订单号
     * @param string $subject 商品标题
     * @param float  $amount  金额（元）
     * @return array ['code'=>1,'qr_code'=>...,] 或 ['code'=>0,'msg'=>...]
     */
    public static function aliPrecreate($orderNo, $subject, $amount)
    {
        if (!self::aliReady()) return ['code' => 0, 'msg' => '支付宝未配置真实商户号'];
        $biz = [
            'out_trade_no' => $orderNo,
            'total_amount' => sprintf('%.2f', $amount),
            'subject'      => mb_substr($subject, 0, 90, 'UTF-8'),
        ];
        $params = [
            'app_id'      => (string)get_config('shop.ali_appid', ''),
            'method'      => 'alipay.trade.precreate',
            'charset'     => 'utf-8',
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => '1.0',
            'notify_url'  => (string)get_config('shop.ali_notify_url', ''),
            'biz_content' => json_encode($biz, JSON_UNESCAPED_UNICODE),
        ];
        $params['sign'] = self::aliSign($params);
        $resp = self::postForm(self::ALI_GATE, $params);
        $data = json_decode($resp, true);
        if (!is_array($data)) return ['code' => 0, 'msg' => '支付宝网关无响应'];
        $respBody = $data['alipay_trade_precreate_response'] ?? [];
        if ((string)($respBody['code'] ?? '') !== '10000') {
            return ['code' => 0, 'msg' => '支付宝下单失败：' . ($respBody['sub_msg'] ?? ($respBody['msg'] ?? '未知错误'))];
        }
        return ['code' => 1, 'qr_code' => (string)$respBody['qr_code']];
    }

    /**
     * 支付宝电脑网站支付（收银台跳转链接，返回 form 自动提交）
     */
    public static function aliPagePay($orderNo, $subject, $amount, $returnUrl = '')
    {
        if (!self::aliReady()) return '';
        $biz = [
            'out_trade_no' => $orderNo,
            'total_amount' => sprintf('%.2f', $amount),
            'subject'      => mb_substr($subject, 0, 90, 'UTF-8'),
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
        ];
        $params = [
            'app_id'      => (string)get_config('shop.ali_appid', ''),
            'method'      => 'alipay.trade.page.pay',
            'charset'     => 'utf-8',
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => '1.0',
            'notify_url'  => (string)get_config('shop.ali_notify_url', ''),
            'return_url'  => $returnUrl,
            'biz_content' => json_encode($biz, JSON_UNESCAPED_UNICODE),
        ];
        $params['sign'] = self::aliSign($params);
        $html = '<form id="ali_pay_form" method="post" action="' . self::ALI_GATE . '">';
        foreach ($params as $k => $v) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES) . '" value="' . htmlspecialchars((string)$v, ENT_QUOTES) . '">';
        }
        $html .= '</form><script>document.getElementById("ali_pay_form").submit();</script>';
        return $html;
    }

    /**
     * 支付宝异步回调验签（POST 参数数组）
     */
    public static function aliNotifyVerify(array $params)
    {
        if (empty($params['sign']) || ($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
            return ['code' => 0, 'msg' => '非支付成功通知'];
        }
        if (!self::aliVerifySign($params)) return ['code' => 0, 'msg' => '签名验证失败'];
        return [
            'code'     => 1,
            'order_no' => (string)$params['out_trade_no'],
            'amount'   => (float)$params['total_amount'],
            'trade_no' => (string)$params['trade_no'],
            'raw'      => $params,
        ];
    }

    /**
     * 支付宝退款
     */
    public static function aliRefund($orderNo, $refundNo, $refund)
    {
        if (!self::aliReady()) return ['code' => 0, 'msg' => '支付宝未配置'];
        $biz = [
            'out_trade_no'   => $orderNo,
            'refund_amount'  => sprintf('%.2f', $refund),
            'out_request_no' => $refundNo,
        ];
        $params = [
            'app_id'      => (string)get_config('shop.ali_appid', ''),
            'method'      => 'alipay.trade.refund',
            'charset'     => 'utf-8',
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => '1.0',
            'biz_content' => json_encode($biz, JSON_UNESCAPED_UNICODE),
        ];
        $params['sign'] = self::aliSign($params);
        $resp = self::postForm(self::ALI_GATE, $params);
        $data = json_decode($resp, true);
        if (!is_array($data)) return ['code' => 0, 'msg' => '支付宝网关无响应'];
        $respBody = $data['alipay_trade_refund_response'] ?? [];
        if ((string)($respBody['code'] ?? '') !== '10000') {
            return ['code' => 0, 'msg' => '退款失败：' . ($respBody['sub_msg'] ?? ($respBody['msg'] ?? '未知错误'))];
        }
        return ['code' => 1, 'msg' => '退款已提交'];
    }

    /**
     * 支付宝 RSA2 签名（对参数按 key 排序，排除 sign，key=value&...）
     */
    public static function aliSign(array $params)
    {
        $privateKey = (string)get_config('shop.ali_private_key', '');
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null || $k === 'sign') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = rtrim($str, '&');
        $key = self::formatPrivateKey($privateKey);
        $res = openssl_sign($str, $sign, $key, OPENSSL_ALGO_SHA256);
        if (!$res) return '';
        return base64_encode($sign);
    }

    /**
     * 支付宝 RSA2 验签（回调参数含 sign，其余为数据）
     */
    public static function aliVerifySign(array $params)
    {
        $sign = (string)($params['sign'] ?? '');
        if ($sign === '') return false;
        $check = $params;
        unset($check['sign'], $check['sign_type']);
        ksort($check);
        $str = '';
        foreach ($check as $k => $v) {
            if ($v === '' || $v === null) continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = rtrim($str, '&');
        $pubKey = self::formatPublicKey((string)get_config('shop.ali_public_key', ''));
        return (bool)openssl_verify($str, base64_decode($sign), $pubKey, OPENSSL_ALGO_SHA256);
    }

    /* ==================== 通用工具 ==================== */

    public static function nonceStr($len = 24)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $len; $i++) $str .= $chars[random_int(0, strlen($chars) - 1)];
        return $str;
    }

    public static function clientIp()
    {
        $ip = request()->ip();
        return $ip ?: '127.0.0.1';
    }

    public static function arrayToXml(array $data)
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    public static function xmlToArray($xml)
    {
        if (!$xml) return [];
        // PHP8 默认已禁用外部实体加载（XXE 防护），无需 libxml_disable_entity_loader（8.2 已废弃）
        $prev = libxml_use_internal_errors(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if ($obj === false) return [];
        return json_decode(json_encode($obj), true) ?: [];
    }

    /**
     * POST XML（微信，可选证书）
     */
    public static function postXml($url, $xml, $certFile = '', $keyFile = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        if ($certFile !== '' && is_file($certFile)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
            curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        }
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        return $err ? '' : (string)$resp;
    }

    /**
     * POST 表单（支付宝）
     */
    public static function postForm($url, array $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded;charset=utf-8']);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        return $err ? '' : (string)$resp;
    }

    /**
     * 组装 PEM 私钥（兼容一行/多行/带标记）
     */
    public static function formatPrivateKey($key)
    {
        $key = trim($key);
        if (strpos($key, '-----BEGIN') !== false) return $key;
        return "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
    }

    /**
     * 组装 PEM 公钥（支付宝公钥格式）
     */
    public static function formatPublicKey($key)
    {
        $key = trim($key);
        if (strpos($key, '-----BEGIN') !== false) return $key;
        return "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
    }

    /**
     * 查询微信支付订单状态（可选：供轮询）
     * @return array ['code'=>1,'trade_state'=>...] trade_state: SUCCESS 已支付
     */
    public static function wxQuery($orderNo)
    {
        if (!self::wxReady()) return ['code' => 0, 'msg' => '未配置'];
        $params = [
            'appid'        => (string)get_config('shop.wx_appid', ''),
            'mch_id'       => (string)get_config('shop.wx_mchid', ''),
            'out_trade_no' => $orderNo,
            'nonce_str'    => self::nonceStr(24),
        ];
        $params['sign'] = self::wxSign($params);
        $resp = self::postXml('https://api.mch.weixin.qq.com/pay/orderquery', self::arrayToXml($params));
        $data = self::xmlToArray($resp);
        if (!is_array($data) || (string)($data['return_code'] ?? '') !== 'SUCCESS') {
            return ['code' => 0, 'msg' => '查询失败'];
        }
        return ['code' => 1, 'trade_state' => (string)($data['trade_state'] ?? '')];
    }
}
