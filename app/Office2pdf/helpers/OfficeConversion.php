<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/23
 * Time: 14:13
 */

/**
 * office文档转换为PDF类
 * @author jinzhonghao <954299193@qq.com> created 2015-04-23
 */
namespace Office2pdf\helpers;

use \COM;

class OfficeConversion
{
    private $osm;

    public function __construct()
    {
        $this->osm = new COM("com.sun.star.ServiceManager") or die ("Please be sure that OpenOffice.org is installed.n");
    }

    public function MakePropertyValue($name,$value)
    {
        $oStruct = $this->osm->Bridge_GetStruct("com.sun.star.beans.PropertyValue");
        $oStruct->Name = $name;
        $oStruct->Value = $value;

        return $oStruct;
    }

    public function transform($input_url, $output_url)
    {
        $args = array($this->MakePropertyValue("Hidden",true));
        $oDesktop = $this->osm->createInstance("com.sun.star.frame.Desktop");
        $oWriterDoc = $oDesktop->loadComponentFromURL($input_url,"_blank", 0, $args);
        $export_args = array($this->MakePropertyValue("FilterName","writer_pdf_Export"));
        $oWriterDoc->storeToURL($output_url,$export_args);//var_dump($output_url,$export_args);die();
        $oWriterDoc->close(true);
        return $this->getPdfPages($output_url);
    }

    public static function isWinOs()
    {
        return 'WIN' === strtoupper(substr(PHP_OS,0,3)) ? true : false;
    }

    public function run($input,$output)
    {
        // $input = $this->autoCharset($input);
        // $output = $this->autoCharset($output);
        $input = "file:///" . str_replace("\\", "/", $input);
        $output = "file:///" . str_replace("\\", "/", $output);
        return $this->transform($input, $output);
    }

    public function autoCharset($fContents, $from = 'gbk', $to = 'utf-8') {
        $from   = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to     = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    }

    public function encoding_mb($str)
    {
        $encode = mb_detect_encoding($str, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5', 'EUC-CN']);
        return mb_convert_encoding($str, 'GB2312', $encode);
    }

    /**
     * 获取PDF文件页数的函数获取
     * 文件应当对当前用户可读（linux下）
     * @param  [string] $path [文件路径]
     * @return int
     */
    public function getPdfPages($path)
    {
        if(!file_exists($path)) return 0;
        if(!is_readable($path)) return 0;
        // 打开文件
        $fp = @fopen($path,"r");
        if (!$fp) {
            return 0;
        } else {
            $max = 0;
            while(! feof($fp) ) {
                $line = fgets($fp, 255);
                if ( preg_match('/\/Count [0-9]+/', $line, $matches) ) {
                    preg_match('/[0-9]+/',$matches[0], $matches2);
                    if ($max<$matches2[0]) $max=$matches2[0];
                }
            }
            fclose($fp);
            return $max;// 返回页数
        }
    }

}