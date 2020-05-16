<?php
/**
 * jsonPL start
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-05-31 13:57:51
 */
namespace Office2pdf;

use Workerman\Worker;

require_once 'config.php';

if (DEBUG) {
	error_reporting(E_ALL);
	ini_set("display_errors","On");
	ini_set("memory_limit","512M");
}

require_once CORE_PATH . '/Autoloader.php';

Worker::$logFile = ROOT_PATH . '/logs/workerman.log';
$json_worker = new Worker('JsonNL://0.0.0.0:1234');
$json_worker->count = 1;

$json_worker->onMessage = function($connection, $data) {
	// $data就是客户端传来的数据，数据已经经过JsonNL::decode处理过
	echo $data;

	 // $connection->send的数据会自动调用JsonNL::encode方法打包，然后发往客户端
	$connection->send([
		'code' => 0,
		'msg' => 'ok'
	]);
};

Worker::runAll();