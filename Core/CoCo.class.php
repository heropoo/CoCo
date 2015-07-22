<?php
/**
 * CoCo核心类
 * @author TTT
 * @date 2015-03-11
 * @lastUpdateTime 2015-07-11
 */
class CoCo
{
    protected static $_instance;    //类实例
    public $config;                 //配置
    public $module;                 //模块

    public function __construct($configFile)
    {
        if (!is_null($configFile)) {
            //加载配置文件
            $this->config = require($configFile);
            $this->config['app_path'] = APP_PATH;
            $GLOBALS['CoCo_required_config'][] = $configFile;
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
        $default_module     = !empty(self::$_instance->config['default_module'])        ? self::$_instance->config['default_module']        : 'Index';
        $default_controller = !empty(self::$_instance->config['default_controller'])    ? self::$_instance->config['default_controller']    : 'Index';
        $default_action     = !empty(self::$_instance->config['default_action'])        ? self::$_instance->config['default_action']        : 'Index';

        self::$_instance->config['default_module']      = $default_module;
        self::$_instance->config['default_controller']  = $default_controller;
        self::$_instance->config['default_action']      = $default_action;


        $router = !empty(CoCo::app()->config['router']) ? CoCo::app()->config['router'] : 'normal';
        //TODO router
        //pathinfo
        if($router == 'pathinfo'){
            $request_uri = $_SERVER['REQUEST_URI'];
            if(!empty(CoCo::app()->config['url_suffix'])){
                //TODO 去掉尾缀
                $request_uri = str_replace(CoCo::app()->config['url_suffix'], '', $request_uri);
            }
            $request_uri = ltrim($request_uri,'/');
            $request_uri_arr = explode('/', $request_uri);

            if(!empty($request_uri) && strpos('index.php', $request_uri) === 0){
                $request = self::normalUrl($default_module, $default_controller, $default_action);
            }else{
                $request['module']      = !empty($request_uri_arr[0])                   ? ucfirst($request_uri_arr[0]) : ucfirst($default_module);
                $request['controller']  = !empty($request_uri_arr[1])                   ? ucfirst($request_uri_arr[1]) : ucfirst($default_controller);
                $request['action']      = 'action' . (!empty($request_uri_arr[2])       ? ucfirst($request_uri_arr[2]) : ucfirst($default_action));
            }
            
            $arr_count = count($request_uri_arr);
            //把参数放入get
            if($arr_count  > 3){
                for($i = 3; $i < $arr_count; $i += 2){
                    if(isset($request_uri_arr[($i+1)])){
                        $_GET[$request_uri_arr[$i]] = $request_uri_arr[($i+1)];
                    }else{
                        $_GET[$request_uri_arr[$i]] = '';
                    }
                }
            }
        }else{ //normal
           $request = self::normalUrl($default_module, $default_controller, $default_action);
        }

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
            Debug::dieMsg($request['module'] . '模块下' . get_class($controller_obj) . '类不存在' . $action . '方法!');
        }

        //调用action
        $controller_obj->$action();

        //运行结束时间
        $GLOBALS['CoCo_endTime'] = microtime(TRUE);
        //var_dump($GLOBALS['CoCo_endTime'] - $GLOBALS['CoCo_beginTime']);
    }

     //普通url模式访问 ?m=home&c=index&a=index&id=12312
    public static function normalUrl($default_module, $default_controller, $default_action){
        $request['module']      = !empty($_GET['m'])                ? ucfirst($_GET['m']) : $default_module;
        $request['controller']  = !empty($_GET['c'])                ? ucfirst($_GET['c']) : $default_controller;
        $request['action']      = 'action' . (!empty($_GET['a'])    ? ucfirst($_GET['a']) : $default_action);
        return $request;
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
            //在Common中查找类
            $bool = false;
            if(!empty(self::$_instance->config['import'])){
                foreach (self::$_instance->config['import'] as $each_path) {
                    $class_file = APP_PATH . DIRECTORY_SEPARATOR . 'Common' .DIRECTORY_SEPARATOR . $each_path . DIRECTORY_SEPARATOR . $className . EXT;
                    if (file_exists($class_file)) {
                        $bool = require_once $class_file;
                        break;
                    }
                }
            }

            if($bool){
                return;
            }

            //模块配置
            $module_config = array();
            $current_module = self::$_instance->module;
            $default_config_file = !empty(self::$_instance->config['default_config_file']) ? self::$_instance->config['default_config_file'] : 'main.php';

            //如果存在模块配置
            if (in_array($current_module, self::$_instance->config['module'])) {
                //加载模块配置文件
                $module_config_file = (self::$_instance->config['app_path']) . DIRECTORY_SEPARATOR . (self::$_instance->module) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $default_config_file;
                if (file_exists($module_config_file)) {
                    //把每个模块的配置放入对应数组中
                    $module_config = require($module_config_file);
                    $GLOBALS['CoCo_required_config'][] = $module_config_file;
                }
            }

            //加入module_path
            $module_config['module_path'] = (self::$_instance->config['app_path']) . DIRECTORY_SEPARATOR . (self::$_instance->module);

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
                Debug::dieMsg($module_config['module_path'] . '模块下' . $className . "类不存在!");
            }
        }
    }
}
