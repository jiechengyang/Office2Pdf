<?php
/**
 * 自定义XML协议测试
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-05-31 15:19:25
 */
namespace Office2pdf;

use Workerman\Worker;

require_once 'config.php';

if (DEBUG) {
	error_reporting(E_ALL);
	ini_set("display_errors","On");
}

require_once CORE_PATH . '/Autoloader.php';

Worker::$logFile = ROOT_PATH . '/logs/workerman.log';
$xml_worker = new Worker('BinaryTransfer://0.0.0.0:1235');

$xml_worker->count = 1;

$xml_worker->onConnect = function($connection)
{
    echo "new connection from ip " . $connection->getRemoteIp() . "\n";
};

$xml_worker->onMessage = function($conn, $data) {
	echo $data;

	// $conn->send();
};

Worker::runAll();