<?php
/**
 * 文件上传交互
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-06-03 11:50:35
 */
namespace Office2pdf\event;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Workerman\Lib\Timer;
use Workerman\MySQL\Connection as MysqlConnection;
use Office2pdf\helpers\Color;
use Office2pdf\helpers\OfficeConversion;
use Office2pdf\timer\HeartbeatPacket;

class FileUploadEvent
{
	protected static $worker;
	protected static $db;
	// protected static $fileDatas;
	protected $upload_num = 0;

	protected $total_chunk = 0;

	protected $total_size = 0;

	protected static $clients = [];

	private static $incomplete = [];

	private static $new_file_name = null;

	public static function strToBin($str)
	{
	    //1.列出每个字符
	    $arr = preg_split('/(?<!^)(?!$)/u', $str);
	    //2.unpack字符
	    foreach($arr as &$v){
	        $temp = unpack('H*', $v);
	        $v = base_convert($temp[1], 16, 2);
	        unset($temp);
	    }
	  
	    return join(' ', $arr);
	}

	public static function binToStr($str)
	{
	    $arr = explode(' ', $str);
	    foreach($arr as &$v) {
	        $v = pack("H".strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
	    }
	  
	    return join('', $arr);
	}

	public function __construct($worker)
	{
		self::$worker = $worker;
	}

	public function onWorkerStart($task)
	{
		echo PHP_EOL;
		self::showMsg(' ----- Worker gets to work, Current workerID: ' . $task->id . '-----', 'Info');
		self::$db = new MysqlConnection(
			MYSQL_HOST,
			MYSQL_PORT,
			MYSQL_USERNAME,
			MYSQL_PASSWORD,
			MYSQL_SCHEMA
		);
		$heart_check = new HeartbeatPacket();
		$heart_check->detectionLater(self::$worker, HEARTBEAT_TIME);
	}

	public function onConnect($conn)
	{	
		echo PHP_EOL;
		$is_hair = false;
		foreach (BLACK_LIST as $ips) {
			if ( 0 === strpos($conn->getRemoteIp(), $ips) ) {
				self::showMsg("ip[{$conn->getRemoteIp()}] in blacklist", 'Error');
				$conn->send([
					'code' => 'failed',
					'msg' => 'failed_403 Forbidden'
				]);
				$conn->close();
				$is_hair = true;
				break;	
			}
		}

		if ($is_hair) return;
		// echo $this->getclientInfo($conn), "\n";
		self::showMsg("Current workerID: {$conn->id} & new connection from ip  {$conn->getRemoteIp()}", 'Info');
	}

	public function onMessage($conn, $data)
	{
		$conn->lastMessageTime = time();
		$key = $this->getclientInfo($conn);
		self::$clients[$key]['upload_state'] = $data['upload_state'];
		if ('ready' === $data['upload_state']) {
			self::showMsg("The client has sent instructions to start transmission, ready to start", 'Custom');
			$this->upload_num = 0;
			$total_chunks = explode('_', $data['upload_num']);
			$this->total_chunk = $total_chunks[0];//$GLOBALS['total_chunk'] = 
			$this->total_size = $total_chunks[1];
			if (!empty(self::$clients[$key]['fid']) && self::$clients[$key]['fid'] === $data['fid']) {
				$msg = 'The file is in the transfer list, check that the file name is duplicated';
				self::showMsg($msg, 'Error');
				$conn->send([
					'code' => 'failed',
					'msg' => $msg
				]);
				// $conn->send('failed_' . $msg);
				$conn->close();		
			}

			self::$clients[$key]['app_key'] = $data['app_key'];
			self::$clients[$key]['fid'] = $data['fid'];
			self::$clients[$key]['file_name'] = $data['file_name'];
			self::$clients[$key]['upload_num']  = 0;
			self::$clients[$key]['total_chunk']  = $this->total_chunk;
			self::$clients[$key]['total_size']  = $this->total_size;

			if ( ! in_array( self::$clients[$key]['app_key'], APP_KEYS ) ) {
				$msg = 'Illegal connection';
				self::showMsg($msg, 'Error');
				$conn->send([
					'code' => 'failed',
					'msg' => $msg
				]);
				$conn->close();
			}

			if ( MAX_UPLOAD_SEIZE < $this->total_size ) {
				$msg = 'File exceeds upload limit('. $this->formatBytes($this->total_size) .')';
				self::showMsg($msg, 'Error');
				$conn->send([
					'code' => 'failed',
					'msg' => $msg
				]);
				$conn->close();
			}

			if ( empty(self::$clients[$key]['new_file_name']) ) {
				self::$clients[$key]['new_file_name'] = time() . '_' . uniqid();
			}

			$conn->send([
				'code' => $data['upload_state'],
				'upload_num' => $this->upload_num
			]);
			// $conn->send($data['upload_state'] . '_' . $this->upload_num);

		} elseif ('processing' === $data['upload_state']) {
			if (!empty($data['file_data'])) {
				$total_chunk = self::$clients[$key]['total_chunk'];
				self::$clients[$key]['upload_num'] = $this->upload_num = $data['upload_num'];
				if (!empty($conn->last_upload_state ) && $conn->last_upload_state === 'goahead') {
					$arr = self::$incomplete[$conn->getRemoteIp()][$data['file_name']];
					if (!empty($arr)) {//  && $arr['upload_num'] == $data['upload_num']
						self::move_upload_file($data['file_name'], $data['file_data'], $key, $arr['new_file_absolte'], true);
					}
				} else {
					self::move_upload_file($data['file_name'], $data['file_data'], $key, self::$clients[$key]['new_file_name']);
				}

				$transfer_rate = sprintf("%01.2f", $this->upload_num / $total_chunk * 100) . '%';//round( $this->upload_num / $this->total_chunk ) * 100 . '%';
				if (self::checkEncoding($data['file_name'], 'GBK')) { 
					$data['file_name'] = mb_convert_encoding($data['file_name'], 'UTF-8', 'GBK');
				}

				self::showMsg('【' . $data['file_name'] . '】already transferred: ' . $this->upload_num . '/'. $total_chunk . '(' . $transfer_rate .')', 'Custom');
				$conn->send([
					'code' => $data['upload_state'],
					'upload_num' => $this->upload_num
				]);
			}
		} elseif ('goahead' === $data['upload_state']) {// 当收到客户端发来的续传通知，则存储当前的状态
			self::showMsg('cureent state is : goahead', 'Custom');
			$conn->last_upload_state = 'goahead';
			$conn->send([
				'code' => 'goahead',
				'upload_num' => @self::$incomplete[ $conn->getRemoteIp() ][ $data['file_name'] ]['upload_num']
			]);
		} elseif ('succeess' === $data['upload_state']) {
			self::showMsg('The file transfer successful', 'Custom');
			unset( self::$incomplete[ $conn->getRemoteIp() ][ self::$clients[$key]['file_name'] ] );
			$output = $this->office2pdf($key, self::$clients[$key]['new_file_absolte']);//应当返回http路径
			$conn->send([
				'code' => $data['upload_state'],
				'upload_num' => $data['upload_num'],
				'output_file' => $output[0]
			]);			
		} else {
			self::showMsg('An unknown error occurred....... ', 'Error');
			@unlink(self::$clients[$key]['new_file_absolte']);
			unset( self::$incomplete[ $conn->getRemoteIp() ][ self::$clients[$key]['file_name'] ] );
			self::$clients[$key]['upload_num'] = $this->upload_num = 0;
			// $conn->send($data['upload_state'] . '_' . $data['upload_num']);
			$conn->send([
				'code' => $data['upload_state'],
				'upload_num' => $data['upload_num']
			]);	
			$conn->close();
		}
	}

	private static function showMsg($msg, $type)
	{
		switch ($type) {
			case 'Custom':
				Color::show($msg . ' [' . $type . ']');
				break;
			case 'showInfo':
				Color::showInfo($msg . ' [' . $type . ']');
				break;
			case 'Warning':
				Color::showWarning($msg . ' [' . $type . ']');
				break;
			case 'Error':
				Color::showError($msg . ' [' . $type . ']');
				break;			
			default:
				Color::show($msg . ' [Custom]');
				break;
		}

		echo PHP_EOL, PHP_EOL;
	}

	private static function isWinOs()
	{
		return 'WIN' === strtoupper(substr(PHP_OS,0,3)) ? true : false;
	}

	private static function checkEncoding ( string $string, string $string_encoding ): bool
	{
	    $fs = $string_encoding == 'UTF-8' ? 'UTF-32' : $string_encoding;

	    $ts = $string_encoding == 'UTF-32' ? 'UTF-8' : $string_encoding;

	    return $string === mb_convert_encoding ( mb_convert_encoding ( $string, $fs, $ts ), $ts, $fs );
	}

	private function office2pdf($key, $file)
	{
		$farr = explode('.', $file);
		$ext = strtolower(end($farr));
		array_pop($farr);
		$out_file = implode('', $farr);
		$farr = explode('/', $out_file);
		$web_file_path = WEB_URL . '/' . end($farr) . '.pdf';
		if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
			if (WEB_UPLOAD_PATH && !is_dir(WEB_UPLOAD_PATH)) {
				mkdir(WEB_UPLOAD_PATH, 0777, true);
			}

			if ($ext === 'xlsx') {
			    // 如果是xlsx则转成xls
			    $file = $this->xlsxToXls($file);
            }
			if (self::isWinOs()) {
				exec("chcp 65001");
				$office2pdf = new OfficeConversion();
				$out_file = WEB_UPLOAD_PATH . '/' . end($farr) . '.pdf';
				$insert_id = self::insertData(
					self::$clients[$key]['fid'], 
					self::$clients[$key]['app_key'], 
					self::$clients[$key]['file_name'], 
					$web_file_path
				);
				$office2pdf->run($file, $out_file);

			} else {
				if (self::checkEncoding($file, 'GBK')) $file = mb_convert_encoding($file, 'UTF-8', 'GBK');
				// $outdir = dirname($file);
				$outdir = WEB_UPLOAD_PATH;
				shell_exec("libreoffice --invisible --convert-to pdf --outdir {$outdir} {$file}");//html
				$insert_id = self::insertData(
					self::$clients[$key]['fid'], 
					self::$clients[$key]['app_key'], 
					self::$clients[$key]['file_name'], 
					$web_file_path
				);
			}

			return [$web_file_path, $insert_id];

		}

		// @unlink($file);
		return false;
	}

	private function xlsxToXls($inputFile, $outputFile = null)
    {
        $objReader = IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load($inputFile);
        $objWriter = IOFactory::createWriter($objPHPExcel, 'Xls');
        if ($outputFile !== null) {
            $objWriter->save($outputFile);
            return $outputFile;
        }

        unlink($inputFile);
        $filename = str_replace('.xlsx', '.xls',$inputFile);
        $objWriter->save($filename);
    }

	private function insertData($file_id, $app_key, $file_name, $pdf_path)
	{
		$insert_id = self::$db->insert('data_info')
			->cols([
				'file_id' => $file_id,
				'app_key' => $app_key,
				'file_name' => $file_name,
				'pdf_path' => $pdf_path,
				'created_at' => time()
			])
			->query();
	    return $insert_id;		
	}

	private function formatBytes($bytes, $default = 1024)
	{
        $arr = ['Byte', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $e = floor(log($bytes) / log($default));

        return number_format(($bytes / pow($default, floor($e))), 2, '.', '') . ' '. $arr[$e];
	}

	private function getclientInfo($conn)
	{
		return '' . $conn->getRemoteIp() . ":" . $conn->getRemotePort() . '';
	}


	private static function move_upload_file($old_file, $file_data, $key, $new_file_name, $fileIsAbsolute = false)
	{
		$old_file = explode('.', $old_file);
		$path = ROOT_PATH . '/uploads/' . date('Y-m');
		if (! is_dir($path) ) {
			mkdir($path, 0777, true);
		}

		if ($fileIsAbsolute) {
			$new_file = $new_file_name;
		} else {
			$new_file = $path . '/' . $old_file[0] . $new_file_name . '.' . $old_file[1];
		}

		if (!self::isWinOs() && self::checkEncoding($new_file, 'GBK')) {
			$new_file = mb_convert_encoding($new_file, 'UTF-8', 'GBK');
		}

		$fp = fopen($new_file, 'a');// a or a+
		if ( flock( $fp, LOCK_EX | LOCK_NB ) ) {// 进行排它型锁定LOCK_NB
			// ftruncate($fp, 0);// truncate file
			fwrite($fp, $file_data);
			// fflush($fp);// flush output before releasing the lock
			flock($fp, LOCK_UN);
		} else {
			echo 'Unable to obtain lock', "\n";
		}

		self::$clients[$key]['new_file_absolte'] = $new_file;//mb_convert_encoding($new_file, 'GBK', 'UTF-8');//$new_file;
		fclose($fp);
		unset($file_data);
	}

	public function onClose($conn)
	{
		$arr = self::$clients[$this->getclientInfo($conn)];
		// if (
		// 	'succeess' !== $arr['upload_state'] 
		// 	&& !empty($arr['upload_num']) 
		// 	&& !empty($arr['total_chunk']) 
		// 	&& $arr['upload_num'] < $arr['total_chunk'] 
		// 	&& !empty($arr['new_file_absolte'])) {
		// 	self::$incomplete[$conn->getRemoteIp()][$arr['file_name']] = [
		// 		'new_file_absolte' => $arr['new_file_absolte'],
		// 		'total_chunk' => $arr['total_chunk'],
		// 		'upload_num' => $arr['upload_num'],
		// 	];
		// }

		if ('succeess' !== $arr['upload_state'] && $arr['upload_num'] !== $arr['total_chunk']) {
			@unlink($arr['new_file_absolte']);
		} elseif ( 'succeess' ===  $arr['upload_state']) {
			@unlink($arr['new_file_absolte']);
		}

		self::$clients[$this->getclientInfo($conn)]['upload_num'] = $this->upload_num = 0;
		unset(self::$clients[$this->getclientInfo($conn)], $arr);
		self::showMsg('The ip ' . $conn->getRemoteIp() . ' is Disconnect', 'Warning');
	}

	public function onWorkerStop($businessWorker)
	{
		// $arr = self::$clients[$this->getclientInfo($conn)];
		// if ($arr['upload_num'] < $arr['total_chunk']) {
		// 	self::$incomplete[$conn->getRemoteIp()][$arr['file_name']] = [
		// 		'new_file_absolte' => $arr['new_file_absolte'],
		// 		'upload_num' => $arr['upload_num'],
		// 	];
		// }
		$this->upload_num = 0;
		self::$clients = [];
		self::showMsg('Worker stopping...', 'Error');
	}
}