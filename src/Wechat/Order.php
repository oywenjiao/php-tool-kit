<?php
/**
 * Created by : PhpStorm
 * User: OuYangWenJiao
 * Date: 2019/11/6
 * Time: 13:52
 */

namespace Wj\ToolKit\Wechat;


use GuzzleHttp\Client;

class Order
{
    protected $base;
    protected $mch_id;
    protected $key;
    protected $ssl_key;
    protected $cert;
    protected $client;
    public $handler;
    public $error;

    public function __construct(WxBase $base)
    {
        $this->base = $base;
        $this->handler = new WxHandle();
        if (!isset($this->client)) {
            $this->client = new Client();
        }
    }

    /**
     * 配置商户id
     * @param $mch_id
     * @return $this
     */
    public function setMchId($mch_id)
    {
        $this->mch_id = $mch_id;
        return $this;
    }

    /**
     * 配置API密钥
     * @param $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * 统一下单
     * @param $openid   string 用户唯一编码 open_id
     * @param $order_no string 商户交易订单号
     * @param $price    string 商品价格(分)
     * @param string $notify    string 支付回调地址
     * @param string $body 商品描述
     * @param string $trade_type 下单方式，默认 JSAPI支付 其他支付有(NATIVE, APP, MWEB)
     * @param array $scene_info 场景信息，支付方式为H5(MWEB)时必传。示例 array('h5_info'=>array('type'=>'Wap','wap_url'=>'网站URL','wap_name'=>'网站名称'))
     * @param string $detail 商品详情,一般可不传
     * @return array|bool
     * @throws \Exception
     */
    public function unifiedOrder($openid, $order_no, $price, $notify, $body, $trade_type = 'JSAPI', $scene_info = array(), $detail = '')
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $xml = $this->makeOrderXml($openid, $order_no, $price, $notify, $body, $trade_type, $scene_info, $detail);
        if (!$xml) {
            throw new \Exception('下单参数有误');
        }
        $response = $this->postXml($url, $xml);
        $re_arr = $this->handler->fromXml($response);
        if ($re_arr['return_code'] == 'FAIL') {
            throw new \Exception($re_arr['return_msg']);
        }
        if (isset($re_arr['err_code'])) {
            throw new \Exception($re_arr['err_code_des']);
        }
        if ($re_arr['return_code'] != 'SUCCESS' || $re_arr['result_code'] != 'SUCCESS') {
            throw new \Exception('微信统一下单失败');
        }
        return $re_arr;
    }

    /**
     * 小程序及网页端调起 支付签名计算
     * @param $prepay_id
     * @return array
     */
    public function xcxSign($prepay_id)
    {
        $arr = [
            'appId'     => $this->base->getAppId(),
            'nonceStr'  => md5(time()),
            'package'   => 'prepay_id=' . $prepay_id,
            'signType'  => 'MD5',
            'timeStamp' => time(),
        ];
        $arr['paySign'] = $this->MakeSign($arr);
        return $arr;
    }

    /**
     * app 支付调起签名计算
     * @param $prepay_id
     * @return array
     */
    public function appSign($prepay_id)
    {
        $arr = [
            'appid'     => $this->base->getAppId(),
            'partnerid' => $this->mch_id,
            'prepayid'  => $prepay_id,
            'package'   => 'Sign=WXPay',
            'noncestr'  => md5(time()),
            'timestamp' => time(),
        ];
        $arr['sign'] = $this->MakeSign($arr);
        return $arr;
    }

    /**
     * 查询订单
     * @param $transaction_id
     * @return array|bool
     * @throws \Exception
     */
    public function queryOrder($transaction_id)
    {
        $arr = [
            'appid'          => $this->base->getAppId(),
            'mch_id'         => $this->mch_id,
            'transaction_id' => $transaction_id,
            'nonce_str'      => md5(time()),
            'sign_type'      => 'MD5'
        ];
        $arr['sign'] = $this->MakeSign($arr);
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $xml = $this->handler->ToXml($arr);
        $response = $this->postXml($url, $xml);
        $re_arr = $this->handler->fromXml($response);
        if ($re_arr['return_code'] == 'SUCCESS' && $re_arr['result_code'] == 'SUCCESS') {
            return $re_arr;
        }
        return false;
    }

    /**
     * @param $openid
     * @param $order_no
     * @param $price
     * @param $notify
     * @param $body
     * @param $trade_type
     * @param array $scene_info
     * @param string $detail
     * @return bool|string
     * @throws \Exception
     */
    protected function makeOrderXml($openid, $order_no, $price, $notify, $body, $trade_type, $scene_info = array(), $detail = '')
    {
        if ($price <= 0) {
            throw new \Exception('价格错误，小于等于0');
        }
        $arr = [
            'appid'            => $this->base->getAppId(),
            'mch_id'           => $this->mch_id,
            'nonce_str'        => md5(time()),
            'notify_url'       => $notify,
            'body'             => $body,
            'detail'           => $detail,
            'out_trade_no'     => $order_no,
            'total_fee'        => $price,
            'spbill_create_ip' => '127.0.0.1',
            'trade_type'       => $trade_type
        ];
        if ($trade_type != 'NATIVE') {
            $arr['openid'] = $openid;
        }
        if ($trade_type == 'MWEB') {
            $arr['scene_info'] = json_encode($scene_info);
        }
        $sign = $this->MakeSign($arr);
        $arr['sign'] = $sign;
        $xml = $this->handler->ToXml($arr);
        return $xml;
    }

    /**
     * postXml
     * @param $url
     * @param $xml
     * @param bool $need_cert 是否需要证书
     * @return \Psr\Http\Message\StreamInterface
     * @throws \Exception
     */
    protected function postXml($url, $xml, $need_cert = false)
    {
        $options = ['timeout' => 30, 'body' => $xml, 'verify' => false];
        if ($need_cert) {
            if (!$this->cert || !$this->ssl_key) {
                throw new \Exception('请设置微信证书和ssl_key');
            }
            $options['cert'] = $this->cert;
            $options['ssl_key'] = $this->ssl_key;
        }
        $response = $this->client->post($url, $options);
        return $response->getBody();
    }

    /**
     * 生成签名
     * @param $arr
     * @return string
     */
    public function MakeSign($arr)
    {
        //签名步骤一：按字典序排序参数
        ksort($arr);
        $string = $this->handler->ToUrlParams($arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        return strtoupper($string);
    }

    /**
     * 申请退款
     * @param String $transaction_id 微信生成的订单号
     * @param String $out_refund_no 商户系统内部的退款单号
     * @param int $total_fee 订单总金额
     * @param int $refund_fee 退款总金额
     * @param string $refund_desc 退款理由
     * @param string $refund_fee_type 退款货币种类，默认人名币
     * @return array|bool
     * @throws \Exception
     */
    public function refuseOrder($transaction_id, $out_refund_no, $total_fee, $refund_fee, $refund_desc = '', $refund_fee_type = 'CNY')
    {
        if (!$transaction_id) {
            return false;
        }
        $arr = [
            'appid'           => $this->base->getAppId(),
            'mch_id'          => $this->mch_id,
            'sign_type'       => 'MD5',
            'nonce_str'       => md5(time()),
            'transaction_id'  => $transaction_id,
            'out_refund_no'   => $out_refund_no,
            'total_fee'       => $total_fee,
            'refund_fee'      => $refund_fee,
            'refund_desc'     => $refund_desc,
            'refund_fee_type' => $refund_fee_type,
        ];
        $sign = $this->MakeSign($arr);
        $arr['sign'] = $sign;
        $xml = $this->handler->ToXml($arr);
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $response = $this->postXml($url, $xml, true);
        $re_arr = $this->handler->fromXml($response);
        if ($re_arr['return_code'] == 'FAIL') {
            throw new \Exception($re_arr['return_msg']);
        }
        if (isset($re_arr['err_code'])) {
            throw new \Exception($re_arr['err_code_des']);
        }
        if ($re_arr['return_code'] != 'SUCCESS' || $re_arr['result_code'] != 'SUCCESS') {
            throw new \Exception('微信申请退款失败');
        }
        return $re_arr;
    }

    /**
     * 配置ssl_key 存放路径
     * @param $file_path
     * @return $this
     */
    public function setSslKey($file_path)
    {
        $this->ssl_key = $file_path;
        return $this;
    }

    /**
     * 配置cert证书路径
     * @param $file_path
     * @return $this
     */
    public function setCert($file_path)
    {
        $this->cert = $file_path;
        return $this;
    }

    /**
     * 根据难易程度获取订单号
     * @param int $level
     * @return string
     */
    public function getTradeNo($level = 1)
    {
        if ($level == 2) {
            return $this->createTediousNo();
        }
        return $this->createSimpleNo();
    }

    /**
     * 生成简单的唯一订单号
     * @return string
     */
    protected function createSimpleNo()
    {
        // 获取当前年月日
        $date = date('Ymd');
        // 通过随机函数获取唯一编码，并截取后6位
        $uniqId = substr(uniqid(), 7, 13);
        // 拆分随机数组
        $number = array_map('ord', str_split($uniqId, 1));
        return $date.substr(implode(NULL, $number), 0, 8);
    }

    /**
     * 生成繁琐的唯一订单号
     * @return string
     */
    protected function createTediousNo()
    {
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        // 获取当前年份对应的英文字母
        $year = $yCode[intval(date('Y')) - 2011];
        // 获取当前月份的十六进制值，并转为大写
        $month = strtoupper(dechex(date('m')));
        // 获取当前日期
        $day = date('d');
        // 获取当前时间戳后5位字符
        $time = substr(time(), -5);
        // 获取当前时间戳微秒级，并截取小数点后的前5位字符
        $microTime = substr(microtime(), 2, 5);
        return $year . $month . $day . $time . $microTime . sprintf('%02d', rand(0, 99));
    }

    /**
     * 企业付款给用户
     * @param string $openid 用户openid
     * @param string $trade_no 商户订单号
     * @param int $amount 金额(分)
     * @param string $desc 描述
     * @param string $ip
     * @param bool $check_name  是否验证真实姓名，默认不验证
     * @param string $user_name 用户真实姓名
     * @return array|bool
     * @throws \Exception
     */
    public function payToUser(
        $openid,
        $trade_no,
        $amount,
        $desc,
        $ip = '127.0.0.1',
        $check_name = false,
        $user_name = ''
    ) {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $arr = [
            'mch_appid'        => $this->base->getAppId(),
            'mchid'            => $this->mch_id,
            'nonce_str'        => md5(time()),
            'partner_trade_no' => $trade_no,    //商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'check_name'       => $check_name ? 'FORCE_CHECK' : 'NO_CHECK',
            'amount'           => $amount,
            'desc'             => $desc,
            'spbill_create_ip' => $ip,
            'openid'           => $openid
        ];
        if ($check_name) {
            $arr['re_user_name'] = $user_name;
        }
        $arr['sign'] = $this->MakeSign($arr);
        $xml = $this->handler->ToXml($arr);
        $response = $this->postXml($url, $xml, true);
        return $this->handler->fromXml($response);
    }

    /**
     * 公众号红包
     * @param string $openid 用户openid
     * @param string $trade_no 商户订单号
     * @param int $amount 红包金额
     * @param string $send_name 商品名称
     * @param string $wishing 红包祝福语
     * @param string $act_name 活动名称
     * @param string $remark 备注
     * @param int $total_num 红包总数
     * @param string $ip
     * @return array|bool
     * @throws \Exception
     */
    public function sendRedPack(
        $openid,
        $trade_no,
        $amount,
        $send_name,
        $wishing,
        $act_name,
        $remark,
        $total_num = 1,
        $ip = '127.0.0.1'
    ) {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $arr = [
            'nonce_str'     => md5(time()),
            'mch_billno'    => $trade_no,
            'mch_id'        => $this->mch_id,
            'wxappid'       => $this->base->getAppId(),
            'send_name'     => $send_name,
            're_openid'     => $openid,
            'total_amount'  => $amount,
            'total_num'     => $total_num,
            'wishing'       => $wishing,
            'client_ip'     => $ip,
            'act_name'      => $act_name,
            'remark'        => $remark,
        ];
        $arr['sign'] = $this->MakeSign($arr);
        $xml = $this->handler->ToXml($arr);
        $response = $this->postXml($url, $xml, true);
        return $this->handler->fromXml($response);
    }
}