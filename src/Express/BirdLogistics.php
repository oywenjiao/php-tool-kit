<?php


namespace Wj\ToolKit\Express;

/**
 * 快递鸟快递查询
 * 官网:  http://www.kdniao.com/
 * Class BirdLogistics
 * @package Wj\ToolKit\Express
 */
class BirdLogistics
{
    protected $appKey = ''; // app_key 密钥
    protected $eBusinessID = '';    // 账号id

    public function __construct($appKey, $businessId)
    {
        $this->appKey = $appKey;
        $this->eBusinessID = $businessId;
    }

    /**
     * 请求物流信息
     * @param $orderSn string 商户订单号
     * @param $shipCode string  快递公司编码
     * @param $shipSn   string  快递单号
     * @return bool|string
     */
    public function getOrderTracesByJson($orderSn, $shipCode, $shipSn)
    {
        $requestData = json_encode([
            'OrderCode'     => $orderSn,
            'ShipperCode'   => $shipCode,
            'LogisticCode'  => $shipSn
        ]);
        $data = array(
            'EBusinessID' => $this->eBusinessID,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $reqUrl = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        $data['DataSign'] = $this->enCrypt($requestData, $this->appKey);
        return $this->postCurl($data, $reqUrl);
    }

    public function postCurl($postFields, $url = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        // 2. 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        if( strtolower(substr($url,0,8)) == 'https://' ){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        // 3. 执行并获取HTML文档内容
        $output = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        // 4. 释放curl句柄
        curl_close($ch);
        if ($curl_errno > 0) {
            return $curl_error;
        }
        return $output;
    }

    public function sendPost($url, $data) {
        $temps = array();
        foreach ($data as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpHeader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpHeader.= "Host:" . $url_info['host'] . "\r\n";
        $httpHeader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpHeader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpHeader.= "Connection:close\r\n\r\n";
        $httpHeader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpHeader);
        $gets = "";

        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 签名生成
     * @param $data
     * @param $appKey
     * @return string
     */
    protected function enCrypt($data, $appKey) {
        return urlencode(base64_encode(md5($data . $appKey)));
    }
}