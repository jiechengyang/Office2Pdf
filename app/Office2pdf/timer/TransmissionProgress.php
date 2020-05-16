<?php
/**
 * 传输进度生成器
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-06-06 13:48:29
 */
namespace Office2pdf\timer;

use Workerman\Lib\Timer;
use Office2pdf\helpers\Color;

class TransmissionProgress
{
	public static function progress($total_chunk, $current_chunk, $timer_id)
	{
		$count = empty($count) ? 1 :$count;
		if ($count++ >= 10000) {
			Timer::del($timer_id);
		}

		if ($total_chunk > 0) {
			if (1 === $count) {
				Color::show('The file has already transferred :'); 
			}

			Color::show($current_chunk . '/' . $total_chunk . '(' . sprintf("%01.2f", $current_chunk / $total_chunk * 100) . '%)');
			if ($total_chunk == $current_chunk) {
				Timer::del($timer_id);
				echo PHP_EOL, PHP_EOL;
			}
		}
	}	
}