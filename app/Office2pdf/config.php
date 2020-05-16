<?php
/**
 * office congfig
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-05-30 19:26:41
 */

define('ROOT_PATH', str_replace('\\','/',dirname(__FILE__)));
define('CORE_PATH', str_replace('\\','/', dirname(dirname(__DIR__))) . '/core');
define('WORKERMAN_LOG_PATH', ROOT_PATH . '/logs');
//这句是php环境目录地址和使用配置文件
// define('PHPPATH', ROOT_PATH.'/php-5.4.14/php.exe -c '.ROOT_PATH.'/php-5.4.14/php.ini');
//端口：1000-9999数字都可以，禁止用特殊端口，建议还是不要修改
