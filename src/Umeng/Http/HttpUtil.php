<?php
/**
 * Created by : PhpStorm
 * User: zachary
 * Email: z18811737572@163.com
 * Date: 2020/9/16
 * Time: 15:57
 */

namespace ZacharyUtils\Umeng\Http;


use ZacharyUtils\Umeng\Util\SignUtil;

class HttpUtil
{
    /**
     *请求Request
     */
    public static function DoHttp($host, $path, $method, $appKey, $appSecret, $readtimeout, $connectTimeout, $headers, $querys, $bodys, $signHeaderPrefixList) {
        $response = new HttpResponse();
        $headers = self::InitialBasicHeader($path, $appKey, $appSecret, $method, $headers, $querys, $bodys, $signHeaderPrefixList);
        $curl = self::InitHttpRequest($host, $path, $method, $readtimeout, $connectTimeout, $headers, $querys);

        $streams = array();
        if (is_array($bodys)) {
            if (0 < count($bodys)) {
                $body = "";
                foreach ($bodys as $itemKey => $itemValue) {
                    if (0 < strlen($body)) {
                        $body .= "&";
                    }
                    if (0 < strlen($itemValue) && 0 == strlen($itemKey)) {
                        $body .= $itemValue;
                        array_push($streams, $itemValue);
                    }
                    if (0 < strlen($itemKey)) {
                        $body .= "=";
                        if (0 < strlen($itemValue)) {
                            $body .= URLEncode($itemValue);
                        }
                    }
                }
                if (count($bodys) == count($streams) && 1 == count($streams)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $streams[0]);
                } elseif (0 < count($bodys)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($bodys));
                }
            }
        }
        $response->setContent(curl_exec($curl));
        $response->setHttpStatusCode(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        $response->setContentType(curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        $response->setHeaderSize(curl_getinfo($curl, CURLINFO_HEADER_SIZE));


        curl_close($curl);
        return $response;
    }

    /**
     *准备请求Request
     */
    private static function InitHttpRequest($host, $path, $method, $readtimeout, $connectTimeout, $headers, $querys) {
        $url = $host;
        if (0 < strlen($path)) {
            $url .= $path;
        }
        $headerArray = array();
        if (is_array($headers)) {
            if (0 < count($headers)) {
                foreach ($headers as $itemKey => $itemValue) {
                    if (0 < strlen($itemKey)) {
                        array_push($headerArray, $itemKey . ":" . $itemValue);
                    }
                }
            }
        }
        if (is_array($querys)) {
            if (0 < count($querys)) {
                $sb = "";
                foreach ($querys as $itemKey => $itemValue) {
                    if (0 < strlen($sb)) {
                        $sb .= "&";
                    }
                    if (0 < strlen($itemValue) && 0 == strlen($itemKey)) {
                        $sb .= $itemValue;
                    }
                    if (0 < strlen($itemKey)) {
                        $sb .= $itemKey;
                        if (0 < strlen($itemValue)) {
                            $sb .= "=";
                            $sb .= URLEncode($itemValue);
                        }
                    }
                }
                $url .= "?";
                $url .= $sb;
            }
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $readtimeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($curl, CURLOPT_HEADER, false);

        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_HEADER, true);
        return $curl;
    }

    /**
     *准备请求的基本header
     */
    private static function InitialBasicHeader($path, $appKey, $appSecret, $method, $headers, $querys, $bodys, $signHeaderPrefixList) {
        if (null == $headers) {
            $headers = array();
        }
        $sb = "";
        //时间戳
        date_default_timezone_set('PRC');
        $headers["X-Ca-Timestamp"] = strval(time() * 1000);
        //防重放，协议层不能进行重试，否则会报NONCE被使用；如果需要协议层重试，请注释此行
        $headers["X-Ca-Nonce"] = strval(self::NewGuid());

        $headers["X-Ca-Key"] = $appKey;
        $headers["X-Ca-Signature"] = SignUtil::Sign($path, $method, $appSecret, $headers, $querys, $bodys, $signHeaderPrefixList);

        return $headers;
    }


    private static function NewGuid() {
        mt_srand((double)microtime() * 10000);
        $uuid = strtoupper(md5(uniqid(rand(), true)));
        return $uuid;
    }
}