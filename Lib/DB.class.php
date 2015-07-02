<?php 
class DB extends PDO{

	private static $_db;
	
	public function __construct($db = null,$options = null){
		//$dsn = 'mysql:host=localhost;port=6379;dbname=blog';
		if(is_null($db)){
			$db = CoCo::app()->config['db'];
		}

		//默认端口
		if(empty($db['port'])){
			$dsn = $db['type'].':host='.$db['host'].';dbname='.$db['dbName'];	
		}else{
			$dsn = $db['type'].':host='.$db['host'].';port='.$db['port'].';dbname='.$db['dbName'];
		}
		
		$username = $db['user'];
		$password = $db['password'];

		if(is_null($options)){
			$options = array(
            	PDO::ATTR_AUTOCOMMIT=>0,                    //是否关闭自动提交
            	//PDO::ATTR_ERRMODE=>PDO::ERRMODE_SILENT,   //对错误 沉默
            	//PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING,  //对错误 警告
            	PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,  //对错误 异常
        	);	
		}
		
		parent::__construct($dsn,$username,$password,$options);
	}

	public static function model($db = null,$options = null){
		if(is_null(self::$_db)){
			try{
           		self::$_db = new self($db,$options);
	        }catch(Exception $e){
	            echo '连接错误：'.$e->getMessage();
	            exit;
	        }
           	//self::$_db = new self($db,$options);
		}
		return self::$_db;
	}
}

/**
 * PDO::exec — Execute an SQL statement and return the number of affected rows
 * PDO::query — Executes an SQL statement, returning a result set as a PDOStatement object
 * PDOStatement::execute — Executes a prepared statement
 */

/**
 * PDO::FETCH_ASSOC 	2
 * PDO::FETCH_NUM		3
 * PDO::FETCH_BOTH 		4
 * PDO::FETCH_OBJ 		5
 */

/*开始一个事务，关闭自动提交
$db = DB::model();
$db->beginTransaction();
$db->commit();
//  识别出错误并回滚更改
$db->rollback();
*/