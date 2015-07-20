<?php
/**
 * 公用函数库
 */

/**
 * 获取get方法提交的值
 * @param string $key $_GET参数, mixed $default_value 没有值时给予默认值
 * @return mixed 
 * @author by Xiao 2014-12-10
 */
function getQuery($key,$default_value = null){
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
function getPost($key,$default_value = null){
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
function getParam($key,$default_value = null){
	if(isset($_REQUEST[$key])){
		return $_REQUEST[$key];
	}else if(isset($default_value)){
		return $default_value;
	}else{
		return null;
	}
}

/**
 * 过滤字符串
 * @param String or Array $string GET或者POST参数
 * @param bool $is_html 如果是文章之类的html内容，指定true
 * @return String or Array $string
 * @author TTT
 * @date 2015-06-17 23:40
 */
function filterString($string,$is_html = false) {
    if (is_array($string)) {
        foreach ($string as $k => $v) {
            $string[$k] = filterString($v);
        }
    } else {
    	// 1.如果是文章之类的html内容
    	if($is_html){
        	//把一些预定义的字符转换为 HTML 实体:[ & （和号） 成为 &amp; " （双引号） 成为 &quot; ' （单引号） 成为 &#039; < （小于） 成为 &lt; > （大于） 成为 &gt; ]
	        // ENT_QUOTES - 编码双引号和单引号。
	        $string = htmlspecialchars($string,ENT_QUOTES);
        }else{
        	// 2.如果是常用参数
	    	//从字符串的两端删除空白字符和其他预定义字符
	        $string = trim($string);
	        if(!get_magic_quotes_gpc()){
	        	//指定的预定义字符前添加反斜杠:[ 单引号 (') 双引号 (") 反斜杠 (\) NULL ]
	        	$string = addslashes($string);
	        }
        }
    }
    return $string;
}