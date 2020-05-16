<?php
/**
 * 首部4字节网络字节序unsigned int，标记整个包的长度 数据部分为Json字符串
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-05-31 17:58:18
 */
namespace Protocols;

class JsonInt
{
	public static function input($recv_buffer)
	{
		if ( 4 > strlen($recv_buffer) ) {
			return 0;//继续等待数据包
		}

		// 利用unpack函数将首部4字节转换成数字，首部4字节即为整个数据包长度
		$unpack_data = unpack('Ntotal_length', $recv_buffer);

		return $unpack_data['total_length'];
	}

	public static function encode($recv_buffer)
	{
		// Json编码得到包体
		$body_json_str = json_encode($recv_buffer);
		$total_length = 4 + strlen($body_json_str);

		return pack('N', $total_length) . $body_json_str;
	}

	public static function decode($recv_buffer)
	{
		// 去掉首部4字节，得到包体Json数据
		$body_json_str = substr($recv_buffer, 4);
		//json解码
		return json_decode($body_json_str, true); 
	}
}