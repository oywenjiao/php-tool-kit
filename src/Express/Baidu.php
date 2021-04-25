<?php

namespace Wj\ToolKit\Express;

use Curl\Curl;

class Baidu
{

    protected $url;

    /**
     * @var 
     */
    protected $curl;

    public function __construct()
    {
        // 接口请求地址
        $this->url= 'https://express.baidu.com/express/api/express';

        $this->curl = new Curl();
        // 设置header头参数
        $this->curl->setHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36',
        ]);
    }

    /**
     * 获取快递信息
     * @param $number
     * @param $company
     * @return array
     */
    public function query($number, $company)
    {
        if (empty($company)) {
            return [];
        }
        $com = $this->companyList($company, true);
        $this->curl->setDefaultJsonDecoder(1);
        // 获取token
        $tokenV2 = $this->getTokenV2();
        $this->curl->get($this->url, [
            'tokenV2'   => $tokenV2,
            'appid'     => 4001,
            'nu'        => $number,
            'com'       => $com
        ]);
        $response = $this->curl->response;
        $info = $response['data']['info'];
        if ($info) {
            return $info['context'];
        }
        return [];
    }

    /**
     * 实时获取网页版token
     * @return mixed|null
     */
    protected function getTokenV2()
    {
        $curl = $this->curl;
        $tokenUrl = 'https://www.baidu.com/baidu?wd=%E5%BF%AB%E9%80%92';
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $curl->get($tokenUrl);
        $pattern = '/tokenV2=(.*?)"/i';
        preg_match($pattern, $curl->response, $match);
        if (!empty($match[1])) {
            $curl->setCookies($curl->getResponseCookies());
            return $match[1];
        }
        return null;
    }

    /**
     * 获取快递简称
     * @param null $key 需要获取名称的key
     * @param false $flip   是否反转数组的键值
     * @return array|mixed|string|string[]
     */
    protected function companyList($key = null, $flip = false)
    {
        $companyList =  [
            'shunfeng'  => '顺丰',
            'yuantong'  => '圆通速递',
            'shentong'  => '申通',
            'yunda'     => '韵达快运',
            'ems'       => 'ems快递',
            'tiantian'  => '天天快递',
            'zhongtong' => '中通速递',
            'debangwuliu'       => '德邦物流',
            'zhongtiekuaiyun'   => '中铁快运',
        ];
        if ($flip) {
            $companyList = array_flip($companyList);
        }
        if (isset($key)) {
            return $companyList[$key];
        }
        return $companyList;
    }
}