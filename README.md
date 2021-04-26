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