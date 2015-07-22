<?php 
/**
 * CoCo控制器基类
 * @author by TTT 2014-11-23
 */
class CoController{

    protected $layout = '/main';    //布局文件 默认mian
    protected $title = '';
    protected $controller;          //当前控制器
    protected $module;              //当前模块

    public function __construct(){
        $controller = get_class($this);
        //从 PHP 5.3.0 起！！
        $this->controller = strstr($controller,'Controller',true);
        $this->module = CoCo::app()->module;
        //控制器初始化
        if(method_exists($this,'init')){
            $this->init();
        }
    }

    /**
     * renderPartial
     * 不要布局渲染页面
     */
    public function renderPartial($view = '',$data = null){
        
        $module = $this->module;
        $module_path = CoCo::app()->config['module_config'][$module]['module_path'];
        $view_dir = $module_path.DIRECTORY_SEPARATOR.'view';

        //渲染参数
        /*if(!empty($data)){
            foreach($data as $k=>$v){
                $$k = $v;
            }
        }*/
        extract($data);

        //渲染页面
        if(!empty($view)){
            require $view_dir.$view.VEXT;
        }
    }

    /**
     * render
     * 要布局渲染页面
     */
    public function render($view = '',$data = null){

        $module = $this->module;
        $module_path = CoCo::app()->config['module_config'][$module]['module_path'];
        $view_dir = $module_path.DIRECTORY_SEPARATOR.'view';
        
        $view_data = array();

        //渲染参数
        if(!empty($data)){
            foreach($data as $k=>$v){
                $$k = $v;
                $view_data[$k] = $v;
            }
        }

        //layout文件
        $layout_file = $view_dir.'/layout'.$this->layout.VEXT;
        
        if(empty($this->layout)){               //layout empty
            //渲染页面
            if(!empty($view)){
                require $view_dir.$view.VEXT;
            }
        }else if(!file_exists($layout_file)){   //layout文件不存在
            //渲染页面
            if(!empty($view)){
                require $view_dir.$view.VEXT;
            }
        }else{                                  //layout文件存在
            ob_start(); //打开缓冲区
            if(!empty($view)){
                require $view_dir.$view.VEXT;
                $content = ob_get_contents(); //得到缓冲区的内容并且赋值给$content
            }    
            ob_end_clean();
            //渲染布局页面
            $view_data['content'] = $content;
            $this->renderPartial('/layout'.$this->layout,$view_data);
        }
    }

    /**
     * 重定向
     * @param string $path like '/home/index/index' or 'http://www.baidu.com'
     * @param array() $params
     * @param int $time
     * @param string $msg
     * @return void
     * @date 2015-04-01
     */
    public function redirect($path,$params = array(),$time=0, $msg=''){
        //如果为一个真实的url，直接跳转
        if(strpos($path, 'http://', 0) !== false || strpos($path, 'https://', 0) !== false){
            $url = $path;
        }else{
            $url = $this->createUrl($path,$params);
            if(empty($url)){
                return;
            }
        }

        if (empty($msg))
            $msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
        if (!headers_sent()) {
            // redirect
            if (0 === $time) {
                header('Location: ' . $url);
            } else {
                header("refresh:{$time};url={$url}");
                echo($msg);
            }
            exit();
        } else {
            $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
            if ($time != 0)
                $str .= $msg;
            exit($str);
        }
    }

    /**
     * 生成url
     * @param string $path like '/home/index/index'
     * @param array() $params
     * @return string $url 
     */
    public function createUrl($path,$params = array()){
        $url = '';
        if(empty($path)){
            return $url;
        }

        $router = !empty(CoCo::app()->config['router']) ? CoCo::app()->config['router'] : 'normal';

        if($router == 'pathinfo'){  //pathinfo
            // /home/index/index
            if(strpos($path,'/') === 0){
                $arr = explode('/',ltrim($path,'/'));
                if(count($arr) == 3){       //模块 + 控制器 + 方法
                    $url = '/'.$arr[0].'/'.$arr[1].'/'.$arr['2'];
                }else if(count($arr) == 2){ //模块 + 控制器 + 默认方法
                    $url = '/'.$arr[0].'/'.$arr[1].'/'.CoCo::app()->config['default_action'];
                }else if(count($arr) == 1){ //模块 + 默认控制器 + 默认方法
                    $url = '/'.$arr[0].'/'.CoCo::app()->config['default_controller'].'/'.CoCo::app()->config['default_action'];
                } 
            }else{
                $arr = explode('/',$path);
                if(count($arr) == 1){      //当前模块 + 当前控制器 + 方法
                    $url = '/'.$this->module.'/'.$this->controller.'/'.$arr[0];
                }else if(count($arr) == 2){//当前模块 + 控制器 + 方法
                    $url = '/'.$this->module.'/'.$arr[0].'/'.$arr[1];
                }else if(count($arr) == 3){//模块 + 控制器 + 方法
                    $url = '/'.$arr[0].'/'.$arr[1].'/'.$arr[2];
                }
            }
            if(!empty($params)){
                foreach($params as $k=>$v){
                    $url .= "/$k/$v";
                }
            }   
            if(!empty(CoCo::app()->config['url_suffix'])){
                $url .= CoCo::app()->config['url_suffix'];
            }
        }else{                      //normal
            $script_name = $_SERVER['SCRIPT_NAME'];
            // /home/index/index
            if(strpos($path,'/') === 0){
                $arr = explode('/',ltrim($path,'/'));
                if(count($arr) == 3){       //模块 + 控制器 + 方法
                    $url = $script_name.'?m='.$arr[0].'&c='.$arr[1].'&a='.$arr['2'];
                }else if(count($arr) == 2){ //模块 + 控制器 + 默认方法
                    $url = $script_name.'?m='.$arr[0].'&c='.$arr[1];
                }else if(count($arr) == 1){ //模块 + 默认控制器 + 默认方法
                    $url = $script_name.'?m='.$arr[0];
                }
            }else{
                $arr = explode('/',$path);
                if(count($arr) == 1){      //当前模块 + 当前控制器 + 方法
                    $url = $script_name.'?m='.$this->module.'&c='.$this->controller.'&a='.$arr[0];
                }else if(count($arr) == 2){//当前模块 + 控制器 + 方法
                    $url = $script_name.'?m='.$this->module.'&c='.$arr[0].'&a='.$arr[1];
                }else if(count($arr) == 3){//模块 + 控制器 + 方法
                    $url = $script_name.'?m='.$arr[0].'&c='.$arr[1].'&a='.$arr[2];
                }
            }
            
            if(!empty($params)){
                foreach($params as $k=>$v){
                    $url .= "&$k=$v";
                }
            }    
        }
        return $url;
    }

    /**
     * 获取get方法提交的值
     * @param string $key $_GET参数, mixed $default_value 没有值时给予默认值
     * @return mixed 
     * @author by Xiao 2014-12-10
     */
    public function getQuery($key,$default_value = null){
        if(isset($_GET[$key])){
            return $_GET[$key];
        }else if(isset($default_value)){
            return $default_value;
        }else{
            return null;
        }
    }

    /**
     * 获取post方法提交的值
     * @param string $key $_POST参数, mixed $default_value 没有值时给予默认值
     * @return mixed 
     * @author by Xiao 2014-12-10
     */
    public function getPost($key,$default_value = null){
        if(isset($_POST[$key])){
            return $_POST[$key];
        }else if(isset($default_value)){
            return $default_value;
        }else{
            return null;
        }
    }

    /**
     * $_REQUEST 默认情况下包含了 $_GET，$_POST 和 $_COOKIE 的数组 
     * （这个数组的项目及其顺序依赖于 PHP 的 variables_order 指令的配置。）
     * 不建议使用
     * @param string $key $_REQUEST参数, mixed $default_value 没有值时给予默认值
     * @return mixed 
     * @author by Xiao 2014-12-10
     */
    public function getParam($key,$default_value = null){
        if(isset($_REQUEST[$key])){
            return $_REQUEST[$key];
        }else if(isset($default_value)){
            return $default_value;
        }else{
            return null;
        }
    }

    /**
     * 定义 title
     */
    public function setTitle(){
        
    }

    /**
     * 添加css文件
     */
    public function addCss(){

    }

    /**
     * 添加js文件
     */
    public function addJs(){

    }
}