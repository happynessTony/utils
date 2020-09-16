<?php
/**
 * Created by : PhpStorm
 * User: zachary
 * Email: z18811737572@163.com
 * Date: 2020/9/16
 * Time: 15:57
 */

namespace ZacharyUtils\Umeng\Util;


class SignUtil
{
    /**
     * 构建待签名
     */
    public static function Sign($path, $method, $secret, &$headers, $querys, $bodys, $signHeaderPrefixList)
    {
        $signStr = self::BuildStringToSign($path, $method, $headers, $querys, $bodys, $signHeaderPrefixList);
        return base64_encode(hash_hmac('sha256', $signStr, $secret, true));
    }

    /**
     * 构建待签名path+(header+query+body)
     */
    private static function BuildStringToSign($path, $method, &$headers, $querys, $bodys, $signHeaderPrefixList)
    {
        $sb = "";
        $sb .= strtoupper($method);
        $sb .= "\n";
        if (array_key_exists("Accept", $headers) && null != $headers["Accept"]) {
            $sb .= $headers["Accept"];
        }
        $sb .= "\n";
        if (array_key_exists("Content-MD5", $headers) && null != $headers["Accept"]) {
            $sb .= $headers["Content-MD5"];
        }
        $sb .= "\n";
        if (array_key_exists("Content-Type", $headers) && null != $headers["Accept"]) {
            $sb .= $headers["Content-Type"];
        }
        $sb .= "\n";
        if (array_key_exists("Date", $headers) && null != $headers["Accept"]) {
            $sb .= $headers["Date"];
        }
        $sb .= "\n";
        $sb .= self::BuildHeaders($headers, $signHeaderPrefixList);
        $sb .= self::BuildResource($path, $querys, $bodys);

        return $sb;
    }

    /**
     * 构建待签名Path+Query+FormParams
     */
    private static function BuildResource($path, $querys, $bodys)
    {
        $sb = "";
        if (0 < strlen($path)) {
            $sb .= $path;
        }
        $sbParam = "";
        $sortParams = [];

        //query参与签名
        if (is_array($querys)) {
            foreach ($querys as $itemKey => $itemValue) {
                if (0 < strlen($itemKey)) {
                    $sortParams[$itemKey] = $itemValue;
                }
            }
        }
        //body参与签名
        if (is_array($bodys)) {
            foreach ($bodys as $itemKey => $itemValue) {
                if (0 < strlen($itemKey)) {
                    $sortParams[$itemKey] = $itemValue;
                }
            }
        }
        //排序
        ksort($sortParams);
        //参数Key
        foreach ($sortParams as $itemKey => $itemValue) {
            if (0 < strlen($itemKey)) {
                if (0 < strlen($sbParam)) {
                    $sbParam .= "&";
                }
                $sbParam .= $itemKey;
                if (null != $itemValue) {
                    if (0 < strlen($itemValue)) {
                        $sbParam .= "=";
                        $sbParam .= $itemValue;
                    }
                }
            }
        }
        if (0 < strlen($sbParam)) {
            $sb .= "?";
            $sb .= $sbParam;
        }

        return $sb;
    }

    /**
     * 构建待签名Http头
     *
     * @param headers              请求中所有的Http头
     * @param signHeaderPrefixList 自定义参与签名Header前缀
     *
     * @return string 待签名Http头
     */
    private static function BuildHeaders(&$headers, $signHeaderPrefixList)
    {
        $sb = "";

        if (null != $signHeaderPrefixList) {
            //剔除X-Ca-Signature/X-Ca-Signature-Headers/Accept/Content-MD5/Content-Type/Date
            unset($signHeaderPrefixList["X-Ca-Signature"]);
            unset($signHeaderPrefixList["Accept"]);
            unset($signHeaderPrefixList["Content-MD5"]);
            unset($signHeaderPrefixList["Content-Type"]);
            unset($signHeaderPrefixList["Date"]);
            ksort($signHeaderPrefixList);

            if (is_array($headers)) {
                ksort($headers);
                $signHeadersStringBuilder = "";
                foreach ($headers as $itemKey => $itemValue) {
                    if (self::IsHeaderToSign($itemKey, $signHeaderPrefixList)) {
                        $sb .= $itemKey;
                        $sb .= ":";
                        if (0 < strlen($itemValue)) {
                            $sb .= $itemValue;
                        }
                        $sb .= "\n";
                        if (0 < strlen($signHeadersStringBuilder)) {
                            $signHeadersStringBuilder .= ",";
                        }
                        $signHeadersStringBuilder .= $itemKey;
                    }
                }
                $headers["X-Ca-Signature-Headers"] = $signHeadersStringBuilder;
            }

        }

        return $sb;
    }

    /**
     * Http头是否参与签名
     * return
     */
    private static function IsHeaderToSign($headerName, $signHeaderPrefixList)
    {
        if (NULL == $headerName) {
            return false;
        }
        if (0 == strlen($headerName)) {
            return false;
        }
        if (1 == strpos("$" . $headerName, "X-Ca-")) {
            return true;
        }
        if (!is_array($signHeaderPrefixList) || empty($signHeaderPrefixList)) {
            return false;
        }
        if (array_key_exists($headerName, $signHeaderPrefixList)) {
            return true;
        }

        return false;
    }
}