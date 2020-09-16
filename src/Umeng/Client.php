<?php
/**
 * Created by : PhpStorm
 * User: zachary
 * Email: z18811737572@163.com
 * Date: 2020/9/16
 * Time: 15:57
 */

namespace ZacharyUtils\Umeng;


use ZacharyUtils\Umeng\Http\HttpRequest;
use ZacharyUtils\Umeng\Http\HttpUtil;

/**
 * Class Client
 *
 * @package ZacharyUtils\Umeng
 */
class Client
{
    private $appKey;
    private $appSecret;
    private $host;
    private $privateKey;

    public function __construct($appKey, $appSecret, $host, $androidPrivateKey = '', $iosPrivateKey = '')
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->host = $host;
        $this->privateKey = [
            'android' => $androidPrivateKey, // 安卓秘钥
            'ios'     => $iosPrivateKey,     // ios秘钥
        ];
    }

    /**
     * 获取手机号
     *
     * @param        $appKey
     * @param        $token
     * @param string $type android, ios
     * @param string $verifyId
     *
     * @return mixed
     */
    public function getMobile($appKey, $token, $type = '', $verifyId = '')
    {
        $uri = sprintf('/api/v1/mobile/info?appkey=%s', $appKey);
        if ($verifyId) {
            $uri .= '&verifyId=' . $verifyId;
        }

        $result = json_decode($this->doRequest($uri, ['token' => $token]), true);
        if ($result['success'] != true) {
            return $result['success'];
        }

        $data = $result['data'];
        $mobile = $data['mobile'];

        // @todo 未测试
        if ($data['aesEncryptKey']) {
            $mobile = $this->decryptMobile($mobile, $data['aesEncryptKey'], $type);
        }

        return $mobile;
    }

    private function decryptMobile($mobile, $key, $type)
    {
        $privateKey = $this->privateKey[$type];
        $private_key = openssl_pkey_get_private("-----BEGIN RSA PRIVATE KEY-----\n" . $privateKey . "\n-----END RSA PRIVATE KEY-----");
        openssl_private_decrypt(base64_decode($key), $decrypted, $private_key);
        $keys = base64_encode($decrypted);
        $rs = $this->decrypts($mobile, $keys);

        return base64_decode($rs);
    }

    /**
     * 手机号码解密
     *
     * @param $encrypted
     * @param $key
     *
     * @return string
     */
    private function decrypts($encrypted, $key)
    {
        return base64_encode(openssl_decrypt($encrypted, 'AES-128-ECB', base64_decode($key), 0, ""));
    }

    /**
     * @param $uri    string 请求接口
     * @param $params array 请求参数
     */
    private function doRequest($uri, $params)
    {
        //域名后、query前的部分
        $path = $uri;
        $request = new HttpRequest($this->host, $path, "POST", $this->appKey, $this->appSecret);
        $request->setHeader("Content-Type", "application/text; charset=UTF-8");
        $request->setHeader("Accept", "application/text; charset=UTF-8");
        $request->setSignHeader("X-Ca-Timestamp");
        $request->setBodyString(json_encode($params));
        $response = HttpUtil::DoHttp($request->getHost(),
            $request->getPath(),
            $request->getMethod(),
            $request->getAppKey(),
            $request->getAppSecret(),
            30000,
            80000,
            $request->getHeaders(),
            $request->getQuerys(),
            $request->getBodys(),
            $request->getSignHeaders()
        );
        return $response->getBody();
    }
}