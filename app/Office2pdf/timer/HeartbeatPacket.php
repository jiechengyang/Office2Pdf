<?php
/**
 * 心跳包检测
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-06-06 11:52:06
 */
namespace Office2pdf\timer;

use \Workerman\Worker;
use \Workerman\Lib\Timer;
use Office2pdf\helpers\Color;

class HeartbeatPacket
{
	// 注意，回调函数属性必须是public
	public function detection($worker, $heart_time)
	{
        $time_now = time();
        foreach($worker->connections as $connection) {
            // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
            if (empty($connection->lastMessageTime)) {
                $connection->lastMessageTime = $time_now;
                continue;
            }

            // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
            if ($time_now - $connection->lastMessageTime > $heart_time) {
            	$connection->send([
                    'code' => 'failed',
                    'msg' => 'Client connection timeout'
                ]);
				Color::showError('Client connection timeout [Error]');
				echo PHP_EOL, PHP_EOL;
                $connection->close();
            }
        }
	}

	public function detectionLater($worker, $heart_time)
	{
        // 回调的方法属于当前的类，则回调数组第一个元素为$this
        Timer::add(1, [$this, 'detection'], [$worker, $heart_time]);//, false
	}
}
