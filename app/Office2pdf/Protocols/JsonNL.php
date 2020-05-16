<?php
/**
 * JsonNL 自定义workerman协议
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-05-31 12:00:50
 */
namespace Protocols;

/**
 * 
 * 协议定义
 *    这里假设区分数据边界的标识为换行符"\n"（注意请求数据本身内部不能包含换行符），数据格式为Json，例如下面是一个符合这个规则的请求包。
 *
 *        {"type":"message","content":"hello"}
 *
 *    注意上面的请求数据末尾有一个换行字符(在PHP中用双引号字符串"\n"表示)，代表一个请求的结束。
 * 
 * 实现步骤
 *   在WorkerMan中如果要实现上面的协议，假设协议的名字叫JsonNL，所在项目为MyApp，则需要以下步骤
 *     1、协议文件放到项目的Protocols文件夹，例如文件MyApp/Protocols/JsonNL.php
 *     2、实现JsonNL类，以namespace Protocols;为命名空间，必须实现三个静态方法分别为 input、encode、decode
 *   注意：workerman会自动调用这三个静态方法，用来实现分包、解包、打包。具体流程参考下面执行流程说明。
 * 
 * workerman与协议类交互流程
 *   1、假设客户端发送一个数据包给服务端，服务端收到数据(可能是部分数据)后会立刻调用协议的input方法，用来检测这包的长度，input方法返回长度值$length给workerman框架。 
 *   2、workerman框架得到这个$length值后判断当前数据缓冲区中是否已经接收到$length长度的数据，如果没有就会继续等待数据，直到缓冲区中的数据长度不小于$length。 
 *   4、缓冲区的数据长度足够后，workerman就会从缓冲区截取出$length长度的数据(即分包)，并调用协议的decode方法解包，解包后的数据为$data。 
 *   4、解包后workerman将数据$data以回调onMessage($connection, $data)的形式传递给业务，业务在onMessage里就可以使用$data变量得到客户端发来的完整并且已经解包的数据了。 
 *   4、当onMessage里业务需要通过调用$connection->send($buffer)方法给客户端发送数据时，workerman会自动利用协议的encode方法将$buffer打包后再发给客户端。
 * 
 */
class JsonNL
{	
	
	const PACKET_TAIL_FLAG = "\n";//0xFC 0xFC

	/**
	 * [input 分包]
     * 检查包的完整性
     * 如果能够得到包长，则返回包的在buffer中的长度，否则返回0继续等待数据
     * 如果协议有问题，则可以返回false，当前客户端连接会因此断开
	 * @param  [string 0x...] $buffer [数据包-来源于缓冲区]
	 * @return [int]         [数据包长度]
	 */
	public static function input($buffer)
	{
		$pos = strpos($buffer, self::PACKET_TAIL_FLAG);
		// 没有包尾，无法得知包长，继续等待数据，返回0
		if ( false === $pos ) {
			return 0;
		}

		// 有包尾，返回数据包长度
		
		return $pos + 1;
	}

	/**
	 * [encode 打包,当向客户端发送数据的时候会自动调用]
	 * @param  [type] $buffer [数据包-来源于缓冲区]
	 * @return [type]         [打包后的数据为$data]
	 */
	public static function encode($buffer)
	{
		return json_encode($buffer) . self::PACKET_TAIL_FLAG;
	}

	/**
	 * [decode 解包,当接收到的数据字节数等于input返回的值（大于0的值）自动调用, 并传递给onMessage回调函数的$data参数]
	 * @param  [type] $buffer [数据包-来源于缓冲区]
	 * @return [type]         [解包后的数据为$data]
	 */
	public static function decode($buffer)
	{
		// 去掉换行，还原成数组
		return json_decode(trim($buffer), true);
	}
}