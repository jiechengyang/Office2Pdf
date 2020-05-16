<?php
/**
 * 使用二进制协议上传文件
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-06-03 10:16:30
 */
namespace Protocols;

use Workerman\Worker;

class BinaryTransfer
{
	const PACKAGE_HEAD_LEN = 9;
	const PACKAGE_ANALYSIS = 'Ntotal_len/Capp_key_len/Cfid_len/Cname_len/Cstate_len/Cupload_num_len';
    
    // private static $new_file_name = null;

	public static function input($recv_buffer)
	{
		// 4个字节的32 bit值以下面的次序传输：首先是7～0bit，其次15～8bit，然后23～16bit，最后是31~24bit。这种传输次序称作大端字节序（BIG-ENDIAN）。 TCP/IP首部中所有的二进制整数在网络中传输时都要求以这种次序。
		if (self::PACKAGE_HEAD_LEN > strlen($recv_buffer)) {
			return 0;
		}

		$package_data = unpack(self::PACKAGE_ANALYSIS, $recv_buffer);// 解包
		return $package_data['total_len'];
	}

	public static function decode($recv_buffer)
	{
		$package_data = unpack(self::PACKAGE_ANALYSIS, $recv_buffer);// 解包
		$app_key_len = $package_data['app_key_len'];
		$fid_len = $package_data['fid_len'];
		$name_len = $package_data['name_len'];// 文件名长度
		$state_len = $package_data['state_len'];
		$upload_num_len = $package_data['upload_num_len'];
		// 从数据流中截取文件名
		$app_key = substr($recv_buffer, self::PACKAGE_HEAD_LEN, $app_key_len);
		$fid = substr($recv_buffer, self::PACKAGE_HEAD_LEN + $app_key_len, $fid_len);
		$file_name = substr($recv_buffer, self::PACKAGE_HEAD_LEN + $app_key_len + $fid_len, $name_len);
		$upload_state = substr($recv_buffer, self::PACKAGE_HEAD_LEN + $app_key_len + $fid_len + $name_len, $state_len);
		if ( 'processing' === $upload_state ) {
			$upload_num = substr($recv_buffer, self::PACKAGE_HEAD_LEN + $app_key_len + $fid_len  + $name_len + $state_len, $upload_num_len);
			return [
				'app_key' => $app_key,
				'fid' => $fid,
				'file_name' => $file_name,
				'file_data' => substr($recv_buffer, self::PACKAGE_HEAD_LEN + $app_key_len + $fid_len  + $name_len + $state_len + $upload_num_len),
				'upload_state' => $upload_state,
				'upload_num' => $upload_num,
			];
		} else if ( 'ready' === $upload_state ) {
			$upload_num_len = $package_data['upload_num_len'];
			$upload_num = substr($recv_buffer, self::PACKAGE_HEAD_LEN + $app_key_len + $fid_len  + $name_len + $state_len, $upload_num_len);
		} else {
			$upload_num = 0;
		}

		// 从数据流中截取出文件二进制数据
		return [
			'app_key' => $app_key,
			'fid' => $fid,
			'file_name' => $file_name,
			'upload_state' => $upload_state,
			'upload_num' => $upload_num
		];
	}

    private static function lockFile($file_name, $wait = false) {
        $loc_file = fopen($file_name, 'c');
        if ( !$loc_file ) {
            throw new \Exception('Can\'t create lock file!');
        }

        if ( $wait ) {
            $lock = flock($loc_file, LOCK_EX);
        } else {
            $lock = flock($loc_file, LOCK_EX | LOCK_NB);
        }

        if ( $lock ) {
            // fprintf($loc_file, "%s\n", getmypid());
            return $loc_file;
        } else if ( $wait ) {
            throw new \Exception('Can\'t lock file!');
        } else {
            return false;
        }
    }

	private static function move_upload_file($old_file, $file_data)
	{
		$old_file = explode('.', $old_file);
		$path = ROOT_PATH . '/uploads/' . date('Y-m');
		if (! is_dir($path) ) {
			mkdir($path, 0777, true);
		}

		$new_file = $path . '/' . $old_file[0] . self::$new_file_name . '.' . $old_file[1];
		$fp = fopen($new_file, 'a');//'a+ or a '
		if ( flock( $fp, LOCK_EX | LOCK_NB ) ) {// 进行排它型锁定LOCK_NB
			// ftruncate($fp, 0);// truncate file
			fwrite($fp, $file_data);
			// fflush($fp);// flush output before releasing the lock
			flock($fp, LOCK_UN);
		} else {
			echo 'Unable to obtain lock', "\n";
		}

		fclose($fp);
		unset($file_data);
	}

	public static function encode($data)
	{
		// 可以根据自己的需要编码发送给客户端的数据，这里只是当做文本原样返回
		// bin2hex
		// return bin2hex($data) . "\n";
		return json_encode($data) . "\n";
	}
}