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
            $response = $obj->query('YT123456', '圆通速度');
        ```