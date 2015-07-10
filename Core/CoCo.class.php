<?php

/**
 * CoCo核心类
 * @author Tang
 * @date 2015-03-11
 */
class CoCo
{
    protected static $_instance;
    public $config;    //配置
    public $module;    //模块

    public function __construct($configFile)
    {
        if (!is_null($configFile)) {
            //加载配置文件
            $this->config = require($configFile);
        }
    }

    public static function app($configFile = null)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($configFile);
        }
        return self::$_instance;
    }

    /**
     * 应用初始化
     */
    public static function run()
    {
        $default_module     = !empty(self::$_instance->config['default_module'])        ? self::$_instance->config['default_module']        : 'Home';
        $default_controller = !empty(self::$_instance->config['default_controller'])    ? self::$_instance->config['default_controller']    : 'Index';
        $default_action     = !empty(self::$_instance->config['default_action'])        ? self::$_instance->config['default_action']        : 'Index';

        //普通url模式访问 ?m=home&c=index&a=index&id=12312
        $request['module'] = !empty($_GET['m']) ? ucfirst($_GET['m']) : $default_module;
        $request['controller'] = !empty($_GET['c']) ? ucfirst($_GET['c']) : $default_controller;
        $request['action'] = 'action' . (!empty($_GET['a']) ? ucfirst($_GET['a']) : $default_action);

        /*unset($_GET['m']);
        unset($_GET['c']);
        unset($_GET['a']);*/

        //放入module
        self::$_instance->module = $request['module'];

        //拼接类名
        $className = $request['controller'] . 'Controller';

        // 注册AUTOLOAD方法
        spl_autoload_register('CoCo::autoload');

        //实例化控制器
        $controller_obj = new $className();

        $action = $request['action'];

        //检查是否存在此action方法
        if (!method_exists($controller_obj, $action)) {
            die(PHP_EOL . $request['module'] . '模块下' . get_class($controller_obj) . '不存在' . $action . "方法!");
        }

        //调用action
        $controller_obj->$action();

        //运行结束时间
        $GLOBALS['_endTime'] = microtime(TRUE);

        $GLOBALS['debug_info']['run_time'] = '脚本运行时间 ： ' . ($GLOBALS['_endTime'] - $GLOBALS['_beginTime']) . ' 秒';

        //显示DEBUG 信息
        if (APP_DEBUG) {
            echo '<div class="debug" style="border:1px dashed #ccc;padding:10px;background-color:#eee;font-size:13px;"><ul>';
            if (!empty($GLOBALS['debug_info'])) {
                foreach ($GLOBALS['debug_info'] as $value) {
                    echo '<li>' . $value . '</li>';
                }
            }
            echo '</ul></div>';
        }
    }

    /**
     * 自动加载类
     */
    public static function autoload($className)
    {

        if (file_exists(CoCo_PATH . '/Core/' . $className . EXT)) {
            require_once CoCo_PATH . '/Core/' . $className . EXT;
        } else if (file_exists(CoCo_PATH . '/Lib/' . $className . EXT)) {
            require_once CoCo_PATH . '/Lib/' . $className . EXT;
        } else {
            //模块配置
            $module_config = array();
            $current_module = self::$_instance->module;

            //如果存在模块配置
            if (in_array($current_module, self::$_instance->config['module'])) {
                //加载模块配置文件
                $module_config_file = (self::$_instance->config['app_name']) . DIRECTORY_SEPARATOR . (self::$_instance->module) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'main.php';
                if (file_exists($module_config_file)) {
                    //把每个模块的配置放入对应数组中
                    $module_config = require($module_config_file);
                }
            }

            //加入module_path
            $module_config['module_path'] = (self::$_instance->config['app_name']) . DIRECTORY_SEPARATOR . (self::$_instance->module);

            //同步配置到全局配置中
            self::$_instance->config['module_config'][$current_module] = $module_config;

            //是否找到class文件
            $bool = false;

            //根据配置import 查找class文件
            if (!empty($module_config['import'])) {
                //循环查找
                foreach ($module_config['import'] as $v) {
                    $class_file = $module_config['module_path'] . DIRECTORY_SEPARATOR . $v . DIRECTORY_SEPARATOR . $className . EXT;
                    if (file_exists($class_file)) {
                        $bool = require_once $class_file;
                        break;
                    }
                }
            }

            //class文件未找到
            if ($bool === false) {
                die(PHP_EOL . $module_config['module_path'] . '模块下' . $className . "类不存在!");
            }
        }
    }
}
