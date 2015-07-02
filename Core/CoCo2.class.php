<?php
namespace Core;
class CoCo{

	// 类映射
    private static $_map      = array();

    // 实例化对象
    private static $_instance = array();


    /**
     * 应用程序初始化
     * @return void
     */
    public static function start(){
    	// 注册AUTOLOAD方法
    	spl_autoload_register('Core\CoCo::autoload');
    }

    /**
     * 类库自动加载
     * @param string $class 对象类名
     * @return void
     */
    public static function autoload($class) {
    	// 检查是否存在映射
    	if(isset(self::$_map[$class])){
    		include self::$_map[$class];
    	}else{
    		echo CoCo_PATH.'/'.$class.EXT.'<br>';
    		$bool = file_exists(CoCo_PATH.'/'.$class.EXT);
    		var_dump($bool);
    		include CoCo_PATH.'/'.$class.EXT;
    	}
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class,$method='') {
        $identify   =   $class.$method;
        if(!isset(self::$_instance[$identify])) {
            if(class_exists($class)){
                $o = new $class();
                if(!empty($method) && method_exists($o,$method))
                    self::$_instance[$identify] = call_user_func(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            }
            else
                self::halt(L('_CLASS_NOT_EXIST_').':'.$class);
        }
        return self::$_instance[$identify];
    }
}