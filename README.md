# php-tool-kit
PHP工具箱


## 安装

> composer require oywenjiao/php-tool-kit


## 文件目录说明
+ Express   快递工具箱
    + Baidu 百度快递查询接口
        + ## 使用示例
        
        ```php
            $obj = new Baidu();
            $response = $obj->query('YT123456', '圆通速递');
        ```
      
    + BirdLogistics 快递鸟查询接口
        + ## 使用示例
        
        ```php
            $appKey = 'your app_key';   // 快递鸟账号密钥
            $businessId = 'your businessId';    // 快递鸟账号id
            $obj = new BirdLogistics($appKey, $businessId);
            $trade_sn = '123456';   // 商户订单号
            $ship_code  = 'YTO';    // 快递公司编码
            $ship_sn    = '123456'; // 快递单号
            $response = $obj->getOrderTracesByJson($trade_sn, $ship_code, $ship_sn);
        ```
      
+ Wechat    微信生态
    + WxBase    微信操作公共类，所有的微信相关api基于该公共类的基础上进行调用
        + ## 示例
    
        ```php
            $appId = 'your appid';  // 公众号appid
            $appSecret = 'your appSecret';  // 公众号app_secret
            $base = new WxBase($appId, $appSecret);
        ```
      
    + Order     微信订单操作类
        + ## 示例
        ```php
            $wx_order = new Order($base);
            $mchId = "your mch_id";     // 商户号id
            $key = "your key";  // 商户密钥
            $wx_order->setMchID($mchId)->setKey($key);  // 设置密钥和商户号
            
            // 调用统一下单接口
            $openid = 'your openid';    // 用户openid
            $trade_sn = 'your trade_sn';    // 商户订单号
            $price = '1.00';    // 订单价格
            $notify = 'your notify';    // 回调地址
            $res = $wx_order->unifiedOrder($openid, $trade_sn, $price, $notify);
      
            // 调用企业付款 
            $wx_order = new Order($base);
            $cert = 'your cert';    // cert 证书路径
            $ssl_key = 'your ssl_key';  // ssl_key 证书路径
            $openid = 'your openid';    // 用户openid
            $trade_sn = 'your trade_sn';    // 商户订单号
            $price = '1.00';    // 提现金额
            $desc = '提现描述';
            $wx_order->setMchID($mchId)->setKey($key)->setCert($cert)->setSslKey($ssl_key);
            $res = $wx_order->payToUser($openid, $trade_sn, $price, $desc);
        ```
    