<?php
/**
 * 文件上传协议
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-06-03 11:43:57
 */
namespace Office2pdf;

use Workerman\Worker;
use Office2pdf\event\FileUploadEvent;

require_once 'config.php';
require_once 'config-local.php';

if (DEBUG) {
	error_reporting(E_ALL);
	ini_set("display_errors","On");
}

function mk_dir(string $path)
{
	mkdir($path, 0777, true);
}

require_once ROOT_PATH . '/helpers/Color.php';
require_once ROOT_PATH . '/helpers/OfficeConversion.php';
require_once ROOT_PATH . '/event/FileUploadEvent.php';
// require_once CORE_PATH . '/vendor/channel/src/Server.php';
// require_once CORE_PATH . '/vendor/channel/src/Client.php';
// require_once CORE_PATH . '/vendor/workerman/mysql/src/Connection.php';
require_once CORE_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/timer/HeartbeatPacket.php';
require_once CORE_PATH . '/Autoloader.php';

if (! is_dir(WORKERMAN_LOG_PATH) ) {
	mk_dir(WORKERMAN_LOG_PATH);
}

$log_file = WORKERMAN_LOG_PATH . '/workerman.log';

Worker::$logFile = $log_file;
$upload_worker   = new Worker('BinaryTransfer://' . TCP_HOST . ':' . TCP_PORT);
$upload_worker->count = 3;
$event_object    = new FileUploadEvent($upload_worker);

$upload_worker->onWorkerStart = [$event_object, 'onWorkerStart'];
$upload_worker->onConnect     = [$event_object, 'onConnect'];
$upload_worker->onMessage     = [$event_object, 'onMessage'];
$upload_worker->onClose       = [$event_object, 'onClose'];
$upload_worker->onWorkerStop  = [$event_object, 'onWorkerStop'];

Worker::runAll();