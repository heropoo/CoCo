<?php
// 设置默认字符集
header('Content-type:text/html;charset=utf-8');

// 检测PHP环境
if (version_compare(PHP_VERSION, '5.3.0', '<')) die('require PHP > 5.3.0 !');

//通过版本号判断是否为sae环境
if (isset($_SERVER['HTTP_APPVERSION'])) {
    define('SAE_ENV', true);
} else {
    define('SAE_ENV', false);
}

// 开启session
session_start();

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 记录开始运行时间
$GLOBALS['CoCo_beginTime'] = microtime(true);

// 应用加载的配置文件
$GLOBALS['CoCo_required_config'] = array();

// 版本信息
const CoCo_VERSION = '0.2';
// 类文件后缀
const EXT = '.class.php';
// 模板文件后缀
const VEXT = '.php';

// 系统常量定义
defined('CoCo_PATH') or define('CoCo_PATH', __DIR__);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']));
defined('APP_DEBUG') or define('APP_DEBUG', false); // 是否调试模式

// 加载核心CoCo类
require CoCo_PATH . '/Core/CoCo' . EXT;
// 加载公用方法
require CoCo_PATH . '/Lib/Function.php';