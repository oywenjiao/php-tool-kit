<?php

namespace Wj\ToolKit\Wechat;

class TemplateMessage
{
    protected $base;

    public function __construct(WxBase $base)
    {
        $this->base = $base;
    }

    /**
     * 发送小程序模板消息
     * @param $openid  string 用户唯一码 open_id
     * @param $template_id  string 模板id
     * @param $params  array 消息内容主体
     * @param string $page  string 跳转页面地址
     * @param string $miniprogram_state  需要放大显示的字段
     * @return bool|mixed
     */
    public function sendXcxMessage(
        $openid,
        $template_id,
        $params,
        $page = 'pages/index/index',
        $miniprogram_state = 'formal'
    ) {
        $access_token = $this->base->access();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$access_token}";
        $post_data = [
            'touser'      => $openid,
            'template_id' => $template_id,
            'page'        => $page,
            'data'        => $params,
        ];
        if ($miniprogram_state) {
            $post_data['miniprogram_state'] = $miniprogram_state;
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS     => json_encode($post_data, JSON_UNESCAPED_UNICODE)
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }
}