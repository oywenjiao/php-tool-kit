<?php

namespace Wj\ToolKit\Kind\AuthManage;


use Firebase\JWT\JWT;

class JwtManage
{
    protected $publicKey;
    protected $privateKey;

    public function __construct($publicKey, $privateKey)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * 通过指定参数生成加密token
     *
     * 使用示例
     * expire: 指定过期时间
     * $payload = array('user_id' => 1234, 'expire' => time() + 100)
     *
     *
     * @param $payload
     * @return string
     */
    public function encode($payload)
    {
        return JWT::encode($payload, $this->privateKey, 'RS256');
    }

    /**
     * 解密token数据
     * @param $token
     * @return array
     */
    public function decode($token)
    {
        try {
            $decode = JWT::decode($token, $this->publicKey, ['RS256']);
            return array('code' => 0, 'data' => (array)$decode, 'msg' => 'success');
        } catch (\Exception $e) {
            return array('code' => 401, 'data' => array(), 'msg' => $e->getMessage());
        }
    }
}