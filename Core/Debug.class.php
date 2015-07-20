<?php
/**
    * Debug调试信息类
    * @author 冷空气
    * @date 2015-03-29
    * @lastUpdateTime 2015-07-11
    */ 
class Debug{
    
    //致命错误
    public static function dieMsg($info){
        $error_log_file = !empty(CoCo::app()->config['log']['access_log_file']) ? CoCo::app()->config['log']['access_log_file'] : APP_PATH . '/Runtime/app_access.log';
        error_log(PHP_EOL.'CoCo access log:'.PHP_EOL.var_export($info,true).PHP_EOL.'CoCo_required_config_file:'.var_export($GLOBALS['CoCo_required_config'],true).PHP_EOL.' -- '.date('Y-m-d H:i:s'),3,$error_log_file);
        if(APP_DEBUG){
            echo $info;
        }
        exit;
    } 

    //添加错误信息
    public static function errLog($info){
        $error_log_file = !empty(CoCo::app()->config['log']['error_log_file']) ? CoCo::app()->config['log']['error_log_file'] : APP_PATH . '/Runtime/app_error.log';
        error_log(PHP_EOL.'CoCo error log:'.var_export($info,true).' -- '.date('Y-m-d H:i:s'),3,$error_log_file);
    }
}