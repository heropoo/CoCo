<?php
/**
  * Debug调试信息类
  * @author 冷空气
  * @date 2015-03-29
  */ 
class Debug{

	public function __construct(){
		ini_set('display_errors', true);
    	error_reporting(E_ALL);
    	set_error_handler('Debug::error_f');
	}
	
	//添加错误信息
	public static function addMsg($info){
		$GLOBALS['debug_info'][] = $info;
		var_dump($GLOBALS['debug_info']);
	}

	//error to file
	public static function error_f($error_level,$error_message,$error_file,$error_line,$error_context){
		switch($error_level){
            case 2:$error_level = 'E_WARNING';break;
            case 8:$error_level = 'E_NOTICE';break;
            case 256:$error_level = 'E_USER_ERROR';break;
            case 512:$error_level = 'E_USER_WARNING';break;
            case 1024:$error_level = 'E_USER_NOTICE';break;
            case 4096:$error_level = 'E_RECOVERABLE_ERROR';break;
            case 8191:$error_level = 'E_ALL';break;
        }
        $error_msg = PHP_EOL."[$error_level]$error_messagein file <span title='$error_file'><b>$error_file_basename</b></span> in line $error_line";
        $GLOBALS['debug_info'][] = $error_msg;	
    }


}