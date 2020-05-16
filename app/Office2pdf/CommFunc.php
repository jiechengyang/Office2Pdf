<?php
/**
 * 公共函数库
 * @Authors jiechengyang (2064320087@qq.com)
 * @Link    http://www.boomyang.cn
 * @addTime    2019-06-04 09:53:18
 */
namespace app\client;

/**
 * [read_file 按行读取文件]
 * @param  [type] $file  [文件]
 * @param  [type] $lines [行数]
 * @return [type]        [description]
 */
function read_file($file, $lines)
{
	$handle = fopen($file, "r");
	$linecounter = $lines;
	$pos = -2;
	$beginning = false;
	$text = array();
	while ($linecounter > 0) {
		$t = " ";
		while ($t != "\n") {
			if (fseek($handle, $pos, SEEK_END) == -1) {
				$beginning = true; 
				break;
			}

			$t = fgetc($handle);
			$pos --;
		}

		$linecounter --;
		if($beginning) rewind($handle);
		$text[$lines - $linecounter - 1] = fgets($handle);
		if($beginning) break;
	}

	fclose ($handle);
	// array_reverse is optional: you can also just return the $text array which consists of the file's lines. 
	return array_reverse($text);
}

function readLastLine ($file) {
    $fp = fopen($file, "r");
    $pos = -1;
    $t = " ";
    $data  = '';
    while ($t != "\n") {
    	// *** - fseek returns 0 if successfull, and -1 if it has no succes as in seeking a byte outside the file's range
        if (!fseek($fp, $pos, SEEK_END)) { 
            $t = fgetc($fp);
            $pos -= 1;
            // echo $pos, "\n";
            $data .= $t;
        } else { // ***
            rewind($fp); // ***
            break; // ***
        } // ***
    }

    $t = fgets($fp);
    fclose($fp);
    return ['data' => $data, 't' => $t];
}

function getFileLine($file)
{
	$fp = fopen($file, 'r');
	$line = 0;
	while (! feof($fp)) {
		if (fgets($fp)) $line++;
	}

	return $line;
}

function strpos_count($haystack, $needle, $i = 0) 
{ 
    while (strpos($haystack,$needle) !== false) {$haystack = substr($haystack, (strpos($haystack,$needle) + 1)); $i++;}
    return $i; 
} 

function getLine($file,$line = 1)
{ 
    $occurence = 0; 
    $contents = ''; 
    $startPos = -1; 
    if (!is_file($file)) return null; 
    $fp = fopen($file, "rb") or die('The file open failed'); 
    while (!feof($fp)) { 
        $str = fread($fp, 1024); 
        $number_of_occurences = strpos_count($str,"\n"); 
        if ($number_of_occurences == 0) {if ($start_pos != -1) {$contents .= $str;}} 
        else { 
            $lastPos = 0; 
            for ($i = 0; $i < $number_of_occurences; $i++){ 
                $pos = strpos($str,"\n", $lastPos); 
                $occurence++; 
                if ($occurence == $line) { 
                    $startPos = $pos; 
                    if ($i == $number_of_occurences - 1) {$contents = substr($str, $startPos + 1);} 
                } elseif ($occurence == $line + 1) { 
                    if ($i == 0) {$contents .= substr($str, 0, $pos);} else {$contents = substr($str, $startPos, $pos - $startPos);} 
                    $occurence = 0; 
                    break; 
                } 
                $lastPos = $pos + 1; 
            } 
        } 
    } 
    fclose($fp); 
    return $contents; 
} 

function strToBin($str)
{
    //1.列出每个字符
    $arr = preg_split('/(?<!^)(?!$)/u', $str);
    //2.unpack字符
    foreach($arr as &$v){
        $temp = unpack('H*', $v);
        $v = base_convert($temp[1], 16, 2);
        unset($temp);
    }
  
    return join(' ',$arr);
}
  
function binToStr($str)
{
    $arr = explode(' ', $str);
    foreach($arr as &$v){
        $v = pack("H".strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
    }
  
    return join('', $arr);
}

function readFileChunk ($handle, $chunkSize)
{
    $byteCount = 0;
    $giantChunk = '';
    while (!feof($handle)) {
        // fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file\
        $chunk = fread($handle, 8192);
        $byteCount += strlen($chunk);
        $giantChunk .= $chunk;
        // $giantChunk[] = $chunk;
        if ($byteCount >= $chunkSize) {
            return $giantChunk;
        }
    }

    return $giantChunk;
}

function formatBytes($bytes, $precision = 2) {
    $units = array("b", "kb", "mb", "gb", "tb");

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . " " . $units[$pow];
}

function readTheFile($path) {
    $handle = fopen($path, "r");

    while(!feof($handle)) {
        yield trim(fgets($handle));
    }

    fclose($handle);
}

// readTheFile("shakespeare.txt");

function readfile_chunked_remote($filename, $seek = 0, $retbytes = true, $timeout = 3) 
{ 
    set_time_limit(0); 
    $defaultchunksize = 1024*1024; 
    $chunksize = $defaultchunksize; 
    $buffer = ''; 
    $cnt = 0; 
    $remotereadfile = false; 

    if (preg_match('/[a-zA-Z]+:\/\//', $filename)) 
        $remotereadfile = true; 

    $handle = @fopen($filename, 'rb'); 

    if ($handle === false) { 
        return false; 
    } 

    stream_set_timeout($handle, $timeout); 
    
    if ($seek != 0 && !$remotereadfile) 
        fseek($handle, $seek); 

    while (!feof($handle)) { 

        if ($remotereadfile && $seek != 0 && $cnt+$chunksize > $seek) 
            $chunksize = $seek-$cnt; 
        else 
            $chunksize = $defaultchunksize; 

        $buffer = @fread($handle, $chunksize); 

        if ($retbytes || ($remotereadfile && $seek != 0)) { 
            $cnt += strlen($buffer); 
        } 

        if (!$remotereadfile || ($remotereadfile && $cnt > $seek)) 
            echo $buffer; 

        ob_flush(); 
        flush(); 
    } 

    $info = stream_get_meta_data($handle); 

    $status = fclose($handle); 

    if ($info['timed_out']) 
        return false; 

    if ($retbytes && $status) { 
        return $cnt; 
    } 

    return $status; 
} 

function readfile_chunked( $filename, $retbytes = true ) { 
    $chunksize = 1 * (1024 * 1024); // how many bytes per chunk 
    $buffer = ''; 
    $cnt = 0; 
    $handle = fopen( $filename, 'rb' ); 
    if ( $handle === false ) { 
        return false; 
    } 

    while ( !feof( $handle ) ) { 
        $buffer = fread( $handle, $chunksize ); 
        if ( $retbytes ) { 
            $cnt += strlen( $buffer ); 
        } 
    } 

    $status = fclose( $handle ); 
    if ( $retbytes && $status ) { 
        return $cnt; // return num. bytes delivered like readfile() does. 
    } 

    return $status; 
} 

function large_remote_file_upload($fp, $client, $pack ,$chunkSize = 1024 * 1024)
{
	$i = 0;
	do {
		$s = readFileChunk($fp, $chunkSize);
		// fwrite($client, json_encode([
		// 	'fid' => $i++,
		// 	'fdata' => $s
		// ]). "\n");
		var_dump(json_encode([
			'fid' => $i++,
			'fdata' => $s
		]));
		// usleep(100);
	} while ( !feof($fp) );

	// var_dump(ftell($fp));
	fclose($fp);
	return $i;
}

function tail($file, $n, $base = 5)
{
	$fp = fopen($file, 'r+');
	assert($n > 0);
	$pos = $n + 1;
	$lines = [];
	while (count($lines) <= $n) {
		if (fseek($fp, -$pos, SEEK_END) == -1) {
			fseek(0);
			break;			
		}

	   $pos *= $base;
	   while (!feof($fp)) {
	       array_unshift($lines, fgets($fp));
	   }
	}

	return array_slice($lines, 0, $n);
}

function test_file($file)
{
	// ★SEEK_CUR:设置指针位置为当前位置加上第二个参数所提供的offset字节。
	// ★SEEK_END:设置指针位置为EOF加上offset字节。在这里，offset必须设置为负值。
	// ★SEEK_SET:设置指针位置为offset字节处。这与忽略第三个参数whence效果相同。
	$fp = fopen($file, 'r') or die("The File Open failed");
	// 输出刚打开文件的指针默认位置，指针在文件的开头位置为0
	// 读取文件中的前10个字符输出，指针位置发生了变化
	// 读取文件的前10个字符之后，指针移动的位置在第10个字节处
	echo ftell($fp), "\n", fread($fp, 10), "\n", ftell($fp), "\n";
	// 文件的位置在110个字节处
	fseek($fp, 100, SEEK_CUR);
	// 读取110到120字节数位置的字符串，读取后指针的位置为120
	echo ftell($fp), "\n", fread($fp, 10), "\n";
	// 又将指针移动到倒数10个字节位置处
	fseek($fp, -10, SEEK_END);
	// 输出文件中最后10个字符
	echo fread($fp, 10), "\n";
	// 又移动文件指针到文件的开头
	rewind($fp);
	// 指针在文件的开头位置，输出0
	echo ftell($fp);

}