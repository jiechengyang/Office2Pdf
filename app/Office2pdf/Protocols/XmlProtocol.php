<?php
/**
 * XmlProtocol
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-05-31 15:08:43
 */
namespace Protocols;

class XmlProtocol
{
	const HEAD_DATA_LEN = 10;

	public static function input($buffer)
	{
		if ( self::HEAD_DATA_LEN > strlen( $buffer ) ) {
			return 0;//不够10字节，继续等待数据
		}

		// 返回包长，包长包含 头部数据长度+包体长度
		$head_str = substr($buffer, 0, 10);
		return  base_convert($head_str, 10, 10);
	}

	public static function encode($buffer)
	{
		$body = substr( $buffer, self::HEAD_DATA_LEN );
		return simplexml_load_string($body);
	}

	public static function decode($buffer)
	{
		// 包体+包头的长度
		$total_length = strlen($buffer) + self::HEAD_DATA_LEN;
		// 长度部分凑足10字节，位数不够补0
		$total_length_str = str_pad( $total_length, 10, '0', STR_PAD_LEFT );

		return $total_length_str . $buffer;
	}
}
