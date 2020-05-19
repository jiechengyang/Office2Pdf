# Office2Pdf
基于workerman实现的tcp大文件传输
## Installation
```shell script
 cd core
 composer install -vvv
```
## Basic Usage

### 创建config-local.php
**配置参考**
```php
define('ENV', 'prod');
define('DEBUG', true);
define('MAX_UPLOAD_SEIZE', 20 * 1024 * 1024);
define('TCP_PORT', 1235);
define('TCP_HOST', '0.0.0.0');
define('HEARTBEAT_TIME', 55);// 心跳间隔
// IP黑名单，禁止链接服务端的，如180.183.*.*直接写成180.183即可
define('BLACK_LIST', [
    '0.0.0.0',
    '183.11.38.38',
    '180.183'
]);
define('WEB_URL', 'http://www.boomyang.com/office2pdf/uploads');
define('WEB_UPLOAD_PATH', '/var/www/office2pdf/uploads');
define('APP_KEYS', [
    'j9NxSY1SR61Asu@j2'
]);
define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_PORT', 3306);
define('MYSQL_USERNAME', 'office_pdf');
define('MYSQL_PASSWORD', '111');
define('MYSQL_SCHEMA', 'office_pdf');
```
### 启动服务
```shell script
 php app/Office2pdf/FileTransfeServer.php start
// or php app/Office2pdf/FileTransfeServer.php start -d
// 参考workerman官方
```
## 客户端上传交互代码参考
[app/Office2pdf/ClientFile.php](app/Office2pdf/ClientFile.php)


纪要
-------------

<font color=red>服务端代码应当使用PSR规范</font>
<br>
<font color=red>客户端应当用workerman client 完善</font>

![效果图](/example/demo.gif)
