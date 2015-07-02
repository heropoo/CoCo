<?php
/**
 * EDB
 * @version v0.1 beta
 * @author TTT
 * @date 2015-06-09
 */
class EDB{
	protected $selectStr = '*';			//查询字符串
	protected $whereStr = '';			//where字符串
	protected $groupStr = '';			//group by字符串
	protected $orderStr = '';			//order by字符串
	protected $limitStr = '';			//limit字符串
	protected $tableName;				//表名
	protected $lastSql;					//最后执行的sql
	protected $dbConfig;				//数据库配置
	protected $prepareParams = array();	//预处理绑定参数
	protected $pk;						//表主键
	protected $fieldList;				//表字段
	private static $_db;				//指定数据库PDO实例
	private static $_instance;			//self实例

	public function __construct($db = null,$options = null){
		//$dsn = 'mysql:host=localhost;port=6379;dbname=blog';
		if(is_null($db)){
			$db = CoCo::app()->config['db'];
			$this->dbConfig = $db;
		}

		//默认端口
		if(empty($this->dbConfig['port'])){
			$dsn = $this->dbConfig['type'].':host='.$this->dbConfig['host'].';dbname='.$this->dbConfig['dbName'];	
		}else{
			$dsn = $this->dbConfig['type'].':host='.$this->dbConfig['host'].';port='.$this->dbConfig['port'].';dbname='.$this->dbConfig['dbName'];
		}

		//$username = $this->dbConfig['user'];
		//$password = $this->dbConfig['password'];

		if(is_null($options)){
			$options = array(
            	PDO::ATTR_AUTOCOMMIT=>0,                    //是否关闭自动提交
            	//PDO::ATTR_ERRMODE=>PDO::ERRMODE_SILENT,   //对错误 沉默
            	//PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING,  //对错误 警告
            	PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,  //对错误 异常
        	);	
		}

		try{
			//self::$_db = new PDO($dsn,$username,$password,$options);
			self::$_db = new PDO($dsn,$this->dbConfig['user'],$this->dbConfig['password'],$options);
		}catch(Exception $e){
			echo '连接错误：'.$e->getMessage();
	        exit;
		}
	}

	//单例调用
	public static function model($db = null,$options = null){
		if(is_null(self::$_instance)){
			self::$_instance = new self($db,$options);	
		}
		return self::$_instance; 
	}

	//返回指定数据库PDO实例
	//PDO::exec() - 执行一条 SQL 语句，并返回受影响的行数
	//PDO::prepare() - Prepares a statement for execution and returns a statement object
	//PDOStatement::execute()
	public function db(){
		return self::$_db;
	}

	public function getLastSql(){
		return $this->lastSql;
	}

	public function fetchOne($fetchType = PDO::FETCH_ASSOC){
        $this->limitStr = ' limit 1';
		$this->_setLastSql();
		$stmt = self::$_db->prepare($this->lastSql);
		$stmt->execute($this->prepareParams);
		return $stmt->fetch($fetchType);
	}

	public function fetchAll($fetchType = PDO::FETCH_ASSOC){
		$this->_setLastSql();
		$stmt = self::$_db->prepare($this->lastSql);
		$stmt->execute($this->prepareParams);
		return $stmt->fetchAll($fetchType);
	}

	public function getOne(){
        $this->limitStr = ' limit 1';
		$this->_setLastSql();
		$stmt = self::$_db->prepare($this->lastSql);
		$stmt->execute($this->prepareParams);
		return $stmt->fetchColumn();
	}

	public function select($selectStr = '*'){
		$this->selectStr = $selectStr;
		$this->lastSql = 'select '.$this->selectStr;
		return $this;
	}

	public function delete($tableName, $conditions = array()){
		$this->_setTableName($tableName);
        $this->prepareParams = array();
		$str_sql = $this->checkConditions($conditions);
		$this->lastSql = 'delete from '.$this->tableName.' where '.$str_sql;
		$stmt = self::$_db->prepare($this->lastSql);
		$result = $stmt->execute($this->prepareParams);
        if($result){
            return $stmt->rowCount();   //成功返回受影响行数
        }else{
            return $result;
        }
	}

	public function insert($tableName, $columnsData){
		$this->_setTableName($tableName);
		$this->_loadField();
		if(empty($columnsData)){
            return false;
        }
        try{
            //过滤data的值
            $fields = array(); //用于存放取出来的字段
            $bindFields = array(); //用于存放取出来的绑定参数字段
            $values = array(); //用于存放取出来的值
            foreach ($columnsData as $key => $value) {
                if(in_array($key, $this->fieldList)){
                    $fields[] = $key;
                    $bindFields[] = ':'.$key;           //用:key 时
                    //$bindFields[] = '?';              //用？时
                    $values[] = $value;
                }
            }
            $this->lastSql = "insert into ".$this->tableName."(".implode($fields,",").") values(".implode($bindFields,",").")";
            $stmt = self::$_db->prepare($this->lastSql);
            foreach ($bindFields as $k => $v) {
                $stmt->bindParam($v,$values[$k]);       //用:key 时
                //$stmt->bindParam($k+1,$values[$k]);    //用？时
            }
            $res = $stmt->execute();
            if($res){
                return self::$_db->lastInsertId();
            }else{
                return false;
            }
        }catch(PDOException $e){
            echo 'SQL错误：'.$e->getMessage();
            exit;   
        }
	}

	public function update($tableName, $columnsData, $conditions = array()){
		if(empty($columnsData)){
			return false;
		}
	    $this->_setTableName($tableName);
		$this->_loadField();
		$fields = array();
        $this->prepareParams = array();
        foreach ($columnsData as $k => $v) {
            if(in_array($k, $this->fieldList) && $k != $this->pk){
                $fields[] = "`$k`=:{$k}_0";
                $this->prepareParams[":{$k}_0"] = $v;
            }
        }

        $this->lastSql = "update `{$this->tableName}` set ".implode($fields,',');

		$where_sql = $this->checkConditions($conditions);
		$this->lastSql .= ' where '.$where_sql;
		$stmt = self::$_db->prepare($this->lastSql);
		$result = $stmt->execute($this->prepareParams);
        if($result){
            return $stmt->rowCount();   //成功返回受影响行数
        }else{
            return $result;
        }
	}

	public function pk($tableName){
		$this->_setTableName($tableName);
		$this->lastSql = "SHOW KEYS FROM `$this->tableName` WHERE `Key_name`='PRIMARY'";
		$stmt = self::$_db->query($this->lastSql);
		$row = $stmt->fetch(2);
		$this->pk = $row['Column_name'];
		return $this->pk;
	}

	// 选择表： 支持 不带表前缀的{{tableName}} 或 完整表名
	public function from($tableName){
		$this->_setTableName($tableName);
		$this->lastSql .= ' from '.$this->tableName;
		return $this;
	}

	//查询条件
	public function where($conditions){
        $this->prepareParams = array();
		$str_sql = $this->checkConditions($conditions);
		if('' === $str_sql) {
            return $this;
        }
        $this->whereStr = ' where'.$str_sql;
        return $this;
	}

	//group by
	public function group($groupStr = ''){
		if(!empty($groupStr)){
			$this->groupStr = ' group by '.$groupStr;
		}
		return $this;
	}

	//order by
	public function order($orderStr = ''){
		if(!empty($orderStr)){
			$this->orderStr = ' order by '.$orderStr;
		}
		return $this;
	}

	//limit 
	public function limit($limit1 = null,$limit2 = null){
		if(!is_null($limit1) && !is_null($limit2)){
			$this->limitStr = " limit $limit1,$limit2";
		}else if(!is_null($limit1) && is_null($limit2)){
			$this->limitStr = " limit $limit1";
		}
		return $this;
	}

    protected function checkConditions($conditions = array(), $op = '=') {
        if(0 === count($conditions)) {
            return '';
        }
        $ary_sql_items = array();
        foreach($conditions as $condition_key => $condition_val) {
            $condition_key = strtolower($condition_key);
            $res_sql = $this->parseKey($condition_key, $condition_val, $op);
            if(is_array($res_sql)) {
                $ary_sql_items = array_merge($ary_sql_items, $res_sql);
            }else {
                $ary_sql_items[] = $res_sql;
            }
        }

        $str_sql = '';
        if(count($ary_sql_items) > 1) {
            $str_sql = " (".implode(' and ', $ary_sql_items).") ";
        }else {
            $str_sql = $ary_sql_items[0];
        }
        return $str_sql;
    }

    protected function parseKey($key, $val, $op = '=') {
        $str_sql = '';
        $ary_sql_items = array();
        switch($key) {
            case 'and':
                if(is_array($val)) {
                    $ary_and_sql_items = array();
                    foreach($val as $sub_key => $sub_val) {
                        $res_and_sql = $this->parseKey($sub_key, $sub_val, $op);
                        if(is_array($res_and_sql)) {
                            $ary_and_sql_items = array_merge($ary_and_sql_items, $res_and_sql);
                        }else{
                            $ary_and_sql_items[] = $res_and_sql;
                        }
                    }
                    $str_sql .= " (".implode(' and ', $ary_and_sql_items).") ";
                }
                break;
            case 'or':
                if(is_array($val)) {
                    $ary_or_sql_items = array();
                    foreach($val as $sub_key => $sub_val) {
                        $res_and_sql = $this->parseKey($sub_key, $sub_val, $op);
                        if(is_array($res_and_sql)) {
                            $ary_or_sql_items = array_merge($ary_or_sql_items, $res_and_sql);
                        }else{
                            $ary_or_sql_items[] = $res_and_sql;
                        }
                    }
                    $str_sql .= " (".implode(' or ', $ary_or_sql_items).") ";
                }
                break;
            case 'like':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, 'LIKE');
                }
                break;
            case 'nlike':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, 'NOT LIKE');
                }
                break;
            case 'in':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, 'IN');
                }
                break;
            case 'nin':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, 'NOT IN');
                }
                break;
            case 'lt':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, '<');
                }
                break;
            case 'lte':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, '<=');
                }
                break;
            case 'gt':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, '>');
                }
                break;
            case 'gte':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, '>=');
                }
                break;
            case 'ne':
                if(is_array($val) && count($val) > 0) {
                    $str_sql .= $this->checkConditions($val, '<>');
                }
                break;
            default:
                if(is_array($val)) {
                    if('IN' === $op || 'NOT IN' === $op) {
                        $ary_sql_items[] = $this->parseInItem($key, $val);
                    }else {
                        foreach($val as $repeat_val) {
                            $ary_sql_items[] = $this->parseNormalItem($key, $repeat_val, $op);
                        }
                    }
                }else {
                    $str_sql .= $this->parseNormalItem($key, $val, $op);
                }
            break;
        }

        if(count($ary_sql_items) > 0) {
            return $ary_sql_items;
        }

        if('' !== $str_sql) {
            return $str_sql;
        }

        return $str_sql;
    }

    protected function parseInItem($key, $val) {
        $alias_key = ':'.strtr($key, '.', '_');
        $count = count($val);
        $ary_in_items = array();
        $j = 0;
        for($i = 0; $i < $count; $i++) {
            $in_key = "{$alias_key}_{$j}";
            while(array_key_exists($in_key, $this->prepareParams)) {
                $j++;
                $in_key = "{$alias_key}_{$j}";
            }
            $this->prepareParams[$in_key] = $val[$i];
            $ary_in_items[] = $in_key;
        }
        $str_in_sql = implode(',', $ary_in_items);

        $str_sql = " {$key} IN ({$str_in_sql}) ";

        return $str_sql;
    }

    protected function parseNormalItem($key, $val, $op = '=') {
        $alias_key = ':'.strtr($key, '.', '_');
        $j = 0;
        $normal_key = "{$alias_key}_{$j}";
        while(array_key_exists($normal_key, $this->prepareParams)) {
            $j++;
            $normal_key = "{$alias_key}_{$j}";
        }
        $this->prepareParams[$normal_key] = $val;

        $str_sql = " {$key} {$op} {$normal_key} ";

        return $str_sql;
    }

	//获取表名
	public function getTableName($tableName){
		$this->_setTableName($tableName);
		return $this->tableName;
	}

	//给表名赋值
	private function _setTableName($tableName){
		$pattern = '/{{(\w+)}}/';
		$res = preg_match($pattern,$tableName,$arr);
		if($res){
			$this->tableName = empty($this->dbConfig['tablePrefix']) ? $arr[1] : $this->dbConfig['tablePrefix'].$arr[1];
		}else{
			$this->tableName = $tableName;
		}
	}

	// 自动获得表中的主键字段,自动获得表中所有的字段
    private function _loadField(){
        //设置sql语句
        $this->lastSql = 'desc `'.$this->tableName.'`';
        //发送sql语句
        $result = self::$_db->query($this->lastSql,PDO::FETCH_ASSOC);
        foreach ($result as $rows) {
            if($rows['Key'] == 'PRI'){
                $this->pk = $rows['Field'];
            }
            $this->fieldList[] = $rows['Field'];
        }
    }

    //拼接lastSql
	private function _setLastSql(){
		//where
		if(!empty($this->whereStr)){
			$this->lastSql .= $this->whereStr;
		}
		//group by
		if(!empty($this->groupStr)){
			$this->lastSql .= $this->groupStr;
		}
		//order by
		if(!empty($this->orderStr)){
			$this->lastSql .= $this->orderStr;
		}
		//limit
		if(!empty($this->limitStr)){
			$this->lastSql .= $this->limitStr;
		}

		return $this->lastSql;
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
$db = EDB::model()->db;
$db->beginTransaction();
$db->commit();
//识别出错误并回滚更改
$db->rollback();
*/

}

