<?php
// declare(strict_types=1);
/**
 * 上传文件客户端
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-06-03 13:34:10
 */
namespace officeUpload;

$config = [
    'server_protocol'    => 'tcp',
    'server_host'        => '127.0.0.1',//本地没有则填其它
    'server_port'        => '12508',
    'blocking_mode'      => 1,//是否连接阻塞
    'default_chunk_size' => 1024 * 1024,
    'stream_timeout'     => 30,
    'app_key'            => 'j9NxSY1SR61Asu@j1',//可以更换
    'transfer_state'     => [
        'ready',       // 开始
        'processing', // 传输中
        'succeess',  // 传输完成
        'timeout',  // 传输超时
        'failed',  // 传输失败
        'pausing',  // 传输暂停
        'goahead', //续传
    ],
];
if ($config['debug']) {
	error_reporting(E_ALL);
	ini_set("display_errors", "On");
}

require_once $config['ROOT_PATH'] . '/CommFunc.php';

$address = $config['server_protocol'] . '://' . $config['server_host'] . ':' . $config['server_port'];

// 检查上传文件路径参数
if (!isset($argv[1])) {
   exit("use php client.php \$file_path\n");
}
// 上传文件路径
$file_to_transfer = trim($argv[1]);
// 上传的文件本地不存在
if (!is_file($file_to_transfer)) {
    exit("$file_to_transfer not exist\n");
}
// 建立socket连接
$client = stream_socket_client($address, $errno, $errmsg, $config['stream_timeout']);
if (!$client) {
    exit("$errmsg\n");
}

// 设置成阻塞
stream_set_blocking($client, $config['blocking_mode']);
// 文件名
$file_name = basename($file_to_transfer);
// 文件名长度
$name_len = strlen($file_name);
$fp = fopen($file_to_transfer, 'r') or die("The File Open failed");
$upload_state = $config['transfer_state'][0];
$state_len = strlen($upload_state);
$pack_head_len = 7;
$alen = $pack_head_len + $name_len;
$file_size = filesize($file_to_transfer);
$chunkSize = $config['default_chunk_size'];
if ($file_size < $chunkSize) {
	$chunkSize = $file_size;
}
$total_chunk = ceil($file_size / $chunkSize) . '_' . $file_size;
$upload_num_len = strlen($total_chunk);
$first_package = pack('NCCC', $alen + $state_len + $upload_num_len + 1, $name_len, $state_len, $upload_num_len) . $file_name . $upload_state . $total_chunk . 0;
fwrite($client, $first_package);
// $response = fread($client, 1024);
// 断点续传
if ( is_file( md5($file_name) . '.tmp' ) ) {
	// 发送续传通知
	$res1 = stream_socket_recvfrom($client, 40);
	$upload_state = $config['transfer_state'][6];
	$state_len = strlen($upload_state);
	$contents = json_decode( file_get_contents( md5($file_name) . '.tmp' ), true);
	$upload_num = $contents['upload_num'];
	$upload_num_len = strlen($upload_num);
	$file_name = $contents['file_name'];
	$name_len = strlen($file_name);
	$alen = $pack_head_len + $name_len;
	$package = pack('NCCC', 
		$alen + $state_len + $upload_num_len + 1, 
		$name_len, 
		$state_len, 
		$upload_num_len) 
		. $file_name 
		. $upload_state 
		. $upload_num 
		. 0;
	fwrite($client, $package);
	$fp = fopen($contents['file'], 'r');
	fseek($fp, $contents['seek']);//, SEEK_CUR
	$upload_state = $config['transfer_state'][1];
	$state_len = strlen($upload_state);
	$i = $upload_num ;
	$res1 = json_decode( stream_socket_recvfrom($client, 40), true );
	if ($res1['code'] === $config['transfer_state'][6]) {
		$i = $res1['upload_num'] + 1;//比如已经传输19个片段，那么应当从20个开始
	}

	$write_error = false;
	echo 'start xu chuan...', PHP_EOL;
	do {
		$s = readFileChunk($fp, $chunkSize);
		$upload_num = $i;
		$upload_num_len = strlen($upload_num);
		$pack = pack('NCCC', 
			$alen + $state_len + $upload_num_len + strlen($s), 
			$name_len, 
			$state_len, 
			$upload_num_len
		) . $file_name . $upload_state . $upload_num . $s;
		if (!fwrite($client, $pack)) {
			$write_error = true;
			break;
		}
		$i++;
		usleep(1000);
		// sleep(1);
	}while (! feof($fp) );
	$upload_state = $config['transfer_state'][2];
	$state_len = strlen($upload_state);
	$last_package = pack('NCCC', $alen + $state_len + 1 + 1, $name_len, $state_len, 1) . $file_name . $upload_state . 0 . 0;
	fwrite($client, $last_package);
	while (true) {
		if ($write_error) break;
		// $res1 = explode("\n", fread($client, 1024));
		$res1 = explode("\n", fread($client, 64));
		if (!$res1) break;
		$res = json_decode($res1[0], true);
		if ($res) {
			if ( $config['transfer_state'][4] === $res['code']) {
				echo 'The file upload failed ';
				@unlink(md5($file_name) . '.tmp');
				if(isset($res['msg'])) {
					echo $res['msg'];
				}
				echo "\n";
				break;
			}

			if ( $config['transfer_state'][2] === $res['code']) {
				echo 'The file upload successed', "\n";
				@unlink(md5($file_name) . '.tmp');
				break;		
			}

			if ( $config['transfer_state'][3] === $res['code']) {
				echo 'timeout', "\n";
				break;
			}
		}

		fclose($fp);
		fclose($client);
		@unlink(md5($file_name) . '.tmp');
		echo 'end xu chuan...', PHP_EOL;
		break;
	}
} else {
	while (true) {
		$res1 = explode("\n", fread($client, 64));
		$res = json_decode($res1[0], true);
		$current_ftell = null;
		if ($res) {
			if ( $config['transfer_state'][4] === $res['code']) {
				echo 'The file upload failed ';
				if (isset($res['msg'])) echo $res['msg'];
				echo "\n";
				break;
			}

			if ( $config['transfer_state'][2] === $res['code']) {
				echo 'The file upload successed', "\n";
				break;		
			}

			if ( $config['transfer_state'][3] === $res['code']) {
				echo 'timeout', "\n";
				break;
			}

			if ( $config['transfer_state'][0] === $res['code']) {
				echo 'Start transferring data to the server...', "\n";
				$upload_state = $config['transfer_state'][1];
				$state_len = strlen($upload_state);
				$i = 1;
				$write_error = false;
				do {
					$upload_num = $i;
					// if ($upload_num === 20 ) {
					// 	$current_ftell = ftell($fp);// - $chunkSize
					// 	file_put_contents(md5($file_name) . '.tmp', json_encode([
					// 		'file' => $file_to_transfer,
					// 		'file_name' => $file_name,
					// 		'seek' => $current_ftell,
					// 		'upload_num' => $upload_num
					// 	]));
					// 	echo 'Test Go_ahead !!', PHP_EOL;
					// 	fclose($client);
					// 	break;
					// }

					$s = readFileChunk($fp, $chunkSize);
					$upload_num_len = strlen($upload_num);
					$pack = pack('NCCC', 
						$alen + $state_len + $upload_num_len + strlen($s), 
						$name_len, 
						$state_len, 
						$upload_num_len
					) . $file_name . $upload_state . $upload_num . $s;
					if (!fwrite($client, $pack)) {
						$write_error = true;
						break;
					}
					$i++;
					usleep(1000);
					// sleep(1);
				} while ( !feof($fp) );

				if (!feof($fp)) {
					break;
				}

				if ($write_error) break;
				$upload_state = $config['transfer_state'][2];
				$state_len = strlen($upload_state);
				$last_package = pack('NCCC', $alen + $state_len + 1 + 1, $name_len, $state_len, 1) . $file_name . $upload_state . 0 . 0;
				fwrite($client, $last_package);
				fclose($fp);
			}
		} 
	}	
}
