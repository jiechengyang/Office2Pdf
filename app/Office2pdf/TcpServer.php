<?php
/**
 * workerman tcp server
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-05-30 16:06:41
 * tcp server 说明
 * 1、win版本count属性无效，全部为单进程
 * 2、不支持start stop reload restart status命令
 * 3、cmd命令行启动，后面可接多个文件，例如 php start_web.php start_gateway.php start_worker.php
 * 4、无法守护进程，cmd窗口关掉后服务即停止
 * 5、每个启动文件只能实例化一个容器(Worker/WebServer/Gateway/BusinessWorker)，需要实例化多个容器时
需要拆成多个文件，例如 start_web.php start_gateway.php start_worker.php 分别初始化web gateway worker
 */
namespace Office2pdf;

use Workerman\Worker;
use Workerman\Lib\Timer;

require_once 'config.php';
if (DEBUG) {
	error_reporting(E_ALL);
	ini_set("display_errors","On");
}

require_once CORE_PATH . '/Autoloader.php';
if (is_file($class_file)) {
    require_once($class_file);
} else {
	exit (' No such as file' . "\r\n");
}

$worker = new Worker('tcp://' . TCP_HOST . ':' . TCP_PORT);
// $worker->count 	= 1;
$worker->name = 'Office2pdfWorker';
// $OfficeWebsocket	= new OfficeWebsocket();

$worker->onWorkerStart = function($task)
{
	echo "----- start success from 'tcp://" . TCP_HOST . ":" . TCP_PORT . "-----\n";
    Timer::add(1, function() use($worker) {
        $time_now = time();
        foreach($worker->connections as $connection) {
            // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
            if (empty($connection->lastMessageTime)) {
                $connection->lastMessageTime = $time_now;
                continue;
            }
            
            // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
            if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                $connection->close();
            }
        }
    });
};

$worker->onConnect = function($connection)
{
	// $GLOBALS['OfficeWebsocket']->clientConnect($connection);
};

$worker->onClose = function($connection)
{
	// $GLOBALS['OfficeWebsocket']->clientClose($connection);
};

$worker->onMessage = function($connection, $data)
{
	$connection->lastMessageTime = time();
	// $GLOBALS['OfficeWebsocket']->clientMessage($connection, $data);
	// var_dump($connection);
	echo "----- haha from client Ip : {$connection->getRemoteIp()} message : {$data}"; 
};


$worker->onWorkerStop = function($worker)
{
    echo "Worker stopping...\n";
};

Worker::runAll();
// object(Workerman\Connection\TcpConnection)#7 (22) {
//   ["onMessage"]=>
//   object(Closure)#5 (1) {
//     ["parameter"]=>
//     array(2) {
//       ["$connection"]=>
//       string(10) "<required>"
//       ["$data"]=>
//       string(10) "<required>"
//     }
//   }
//   ["onClose"]=>
//   object(Closure)#4 (1) {
//     ["parameter"]=>
//     array(1) {
//       ["$connection"]=>
//       string(10) "<required>"
//     }
//   }
//   ["onError"]=>
//   NULL
//   ["onBufferFull"]=>
//   NULL
//   ["onBufferDrain"]=>
//   NULL
//   ["protocol"]=>
//   NULL
//   ["transport"]=>
//   string(3) "tcp"
//   ["worker"]=>
//   object(Workerman\Worker)#1 (26) {
//     ["id"]=>
//     int(0)
//     ["name"]=>
//     string(4) "none"
//     ["count"]=>
//     int(1)
//     ["user"]=>
//     string(0) ""
//     ["group"]=>
//     string(0) ""
//     ["reloadable"]=>
//     bool(true)
//     ["reusePort"]=>
//     bool(false)
//     ["onWorkerStart"]=>
//     object(Closure)#2 (1) {
//       ["parameter"]=>
//       array(1) {
//         ["$task"]=>
//         string(10) "<required>"
//       }
//     }
//     ["onConnect"]=>
//     object(Closure)#3 (1) {
//       ["parameter"]=>
//       array(1) {
//         ["$connection"]=>
//         string(10) "<required>"
//       }
//     }
//     ["onMessage"]=>
//     object(Closure)#5 (1) {
//       ["parameter"]=>
//       array(2) {
//         ["$connection"]=>
//         string(10) "<required>"
//         ["$data"]=>
//         string(10) "<required>"
//       }
//     }
//     ["onClose"]=>
//     object(Closure)#4 (1) {
//       ["parameter"]=>
//       array(1) {
//         ["$connection"]=>
//         string(10) "<required>"
//       }
//     }
//     ["onError"]=>
//     NULL
//     ["onBufferFull"]=>
//     NULL
//     ["onBufferDrain"]=>
//     NULL
//     ["onWorkerStop"]=>
//     object(Closure)#6 (1) {
//       ["parameter"]=>
//       array(1) {
//         ["$worker"]=>
//         string(10) "<required>"
//       }
//     }
//     ["onWorkerReload"]=>
//     NULL
//     ["transport"]=>
//     string(3) "tcp"
//     ["connections"]=>
//     array(1) {
//       [1]=>
//       *RECURSION*
//     }
//     ["protocol"]=>
//     NULL
//     ["_autoloadRootPath":protected]=>
//     string(27) "E:\workerman\app\office2pdf"
//     ["_pauseAccept":protected]=>
//     bool(false)
//     ["stopping"]=>
//     bool(false)
//     ["_mainSocket":protected]=>
//     resource(13) of type (stream)
//     ["_socketName":protected]=>
//     string(18) "tcp://0.0.0.0:9201"
//     ["_context":protected]=>
//     resource(8) of type (stream-context)
//     ["workerId"]=>
//     string(32) "000000003b96b3d6000000004d6dcd7e"
//   }
//   ["bytesRead"]=>
//   int(6)
//   ["bytesWritten"]=>
//   int(0)
//   ["id"]=>
//   int(1)
//   ["_id":protected]=>
//   int(1)
//   ["maxSendBufferSize"]=>
//   int(1048576)
//   ["maxPackageSize"]=>
//   int(10485760)
//   ["_socket":protected]=>
//   resource(19) of type (stream)
//   ["_sendBuffer":protected]=>
//   string(0) ""
//   ["_recvBuffer":protected]=>
//   string(6) "你好啊"
//   ["_currentPackageLength":protected]=>
//   int(0)
//   ["_status":protected]=>
//   int(2)
//   ["_remoteAddress":protected]=>
//   string(15) "127.0.0.1:63385"
//   ["_isPaused":protected]=>
//   bool(false)
//   ["_sslHandshakeCompleted":protected]=>
//   bool(false)