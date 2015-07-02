<?php
/**
 * 简易的PDO封装
 * @version v1.0
 * @author Tang
 * @date 2015-01-07
 */
class PDOModel{
    protected $plink;               //数据库连接资源
    protected $tableName;           //表名
    protected $fieldList;           //表中所有的字段
    protected $pk;                  //主键字段
    protected $limit = '';          //限制条数
    protected $lastSql;             //最后查询执行sql
    protected $selectedStr = '*';   //要查询的字段
    protected $whereStr = '';       //查询的where条件
    protected $paramsArr = array(); //绑定的参数值
    protected $orderStr = '';       //order排序

    public static function model($tableName,$db = null,$options = null){
        return new PDOModel($tableName,$db = null,$options = null);
    }

    public function __construct($tableName,$db = null,$options = null){
        if(is_null($db)){
            if(IS_SAE){
                $db = CoCo::app()->config['db'] = array(
                    'host'=>SAE_MYSQL_HOST_M,
                    'port'=>SAE_MYSQL_PORT,
                    'user'=>SAE_MYSQL_USER,
                    'password'=>SAE_MYSQL_PASS,
                    'dbName'=>SAE_MYSQL_DB,
                    'tablePrefix'=>'tt_'
                );
            }else{
                $db = CoCo::app()->config['db'];
            }
        }
        $this->tableName = $db['tablePrefix'].$tableName; //初始化表名
        //创建一个pDO对象
        // dsn : mysql:host=xxx;port=xxx;dbname=xxx', 'user', 'pass', array(...)
        try{
            if(is_null($options)){
                $options = array(
                    PDO::ATTR_AUTOCOMMIT=>0,                    //是否关闭自动提交
                    //PDO::ATTR_ERRMODE=>PDO::ERRMODE_SILENT,   //对错误 沉默
                    //PDO::ATTR_ERRMODE=>PDO::ERRMODE_WARNING,  //对错误 警告
                    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,  //对错误 异常
                );
            }
            
            $this->plink = new PDO('mysql:host='.$db['host'].';port='.$db['port'].';dbname='.$db['dbName'],$db['user'],$db['password'],$options);
        }catch(Exception $e){
            echo '连接错误：'.$e->getMessage();
            exit;
        }
        //设置错误处理方式
        //$this->plink->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT);    //对错误 沉默
        //$this->plink->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_WARNING);   //对错误 警告
        //$this->plink->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); //对错误 异常
        
        //$this->plink->query('set names utf8');
        $this->plink->exec('set names utf8');
        $this->loadField();
    }
    
    /**
     * 插入
     */
    public function insert($data = array()){
        if(empty($data)){
            return false;
        }
        try{
            //过滤data的值
            $fields = array(); //用于存放取出来的字段
            $bindFields = array(); //用于存放取出来的绑定参数字段
            $values = array(); //用于存放取出来的值
            foreach ($data as $key => $value) {
                if(in_array($key, $this->fieldList)){
                    $fields[] = $key;
                    $bindFields[] = ':'.$key;           //用:key 时
                    //$bindFields[] = '?';              //用？时
                    $values[] = $value;
                }
            }
            $this->lastSql = "insert into ".$this->tableName."(".implode($fields,",").") values(".implode($bindFields,",").")";
            $stmt = $this->plink->prepare($this->lastSql);
            foreach ($bindFields as $k => $v) {
                $stmt->bindParam($v,$values[$k]);       //用:key 时
                //$stmt->bindParam($k+1,$values[$k]);    //用？时
            }
            $res = $stmt->execute();
            if($res){
                return $this->plink->lastInsertId();
            }else{
                return false;
            }
        }catch(PDOException $e){
            echo 'SQL错误：'.$e->getMessage();
            exit;   
        }
    }

    /**
     * 要查询的字段
     * @params $str string like:'id,name,create_time'
     */
    public function select($str = null){
        if(empty($str)){
            $this->selectedStr = '*';
        }else{
            //TODO过滤表字段
            $this->selectedStr = $str;
        }
        return $this;   
    }

    /**
     * 查询总数
     * @params $str string like:'id,name,create_time'
     */
    public function getTotal(){
        $this->lastSql = 'select ';
        //查询字段
        if($this->selectedStr == '*'){
            $this->selectedStr = 'count(`'.$this->pk.'`)';
        }
        $this->lastSql .= $this->selectedStr;
        //表名
        $this->lastSql .= ' from `'.$this->tableName.'`';
        //where条件
        if(!empty($this->whereStr)){
            $this->lastSql .= $this->whereStr;
        }
        //绑定参数查询
        $stmt = $this->plink->prepare($this->lastSql);
      
        $stmt->execute($this->paramsArr);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if(isset($row[0])){
            return (int)$row[0];
        }else{
            return false;
        }
    }

    /**
     * 查询单条
     */
    public function fetchOne($fetch_type = PDO::FETCH_ASSOC){
        $this->lastSql = 'select ';
        //查询字段
        $this->lastSql .= $this->selectedStr;
        //表名
        $this->lastSql .= ' from `'.$this->tableName.'`';
        //where条件
        if(!empty($this->whereStr)){
            $this->lastSql .= $this->whereStr;
        }
        //order
        if(!empty($this->orderStr)){
            $this->lastSql .= ' order by '.$this->orderStr;
        }
        //查询一条
        $this->lastSql .= ' limit 1';
        //绑定参数查询
        $stmt = $this->plink->prepare($this->lastSql);
      
        $stmt->execute($this->paramsArr);
        return $result = $stmt->fetch($fetch_type);
    }

    /**
     * 获取offset 0
     */
    public function getOne(){
        $result = $this->fetchOne(PDO::FETCH_NUM);
        if(isset($result[0])){
            return $result[0];
        }else{
            return false;
        }  
    }

    /**
     * 查询多条
     */
    public function fetchAll($fetch_type = PDO::FETCH_ASSOC){
        $this->lastSql = 'select ';
        //查询字段
        $this->lastSql .= $this->selectedStr;
        //表名
        $this->lastSql .= ' from `'.$this->tableName.'`';
        //where条件
        if(!empty($this->whereStr)){
            $this->lastSql .= $this->whereStr;
        }
        //order条件
        if(!empty($this->orderStr)){
            $this->lastSql .= ' order by '.$this->orderStr;
        }
        //limit条件
        if(!empty($this->limit)){
            $this->lastSql .= $this->limit;
        }
        //绑定参数查询
        $stmt = $this->plink->prepare($this->lastSql);
      
        $stmt->execute($this->paramsArr);
        return $stmt->fetchAll($fetch_type);
    }

    /**
     * 删除
     */
    public function delete(){
        $this->lastSql = 'delete from `'.$this->tableName.'`';
        if(empty($this->whereStr)){
             die('delete操作不写where条件的都是王八蛋！');
        }
        $this->lastSql .= $this->whereStr;
        $stmt = $this->plink->prepare($this->lastSql);
        $result = $stmt->execute($this->paramsArr);
        if($result){
            return $stmt->rowCount();   //成功返回受影响行数
        }else{
            return $result;
        }
    }

    /**
     * 更新
     */
    public function update($data=array()){
        //判断$data
        if(empty($data)){
            return false;
        }
        $fields = array();
        foreach ($data as $k => $v) {
            if(in_array($k, $this->fieldList) && $k != $this->pk){
                $fields[] = "`$k`=:$k";
                $this->paramsArr[":$k"] = $v;
            }
        }

        $this->lastSql = "update `$this->tableName` set ".implode($fields,',');
        if(!empty($this->whereStr)){
            $this->lastSql .= $this->whereStr;
        }
        $stmt = $this->plink->prepare($this->lastSql);
        $result = $stmt->execute($this->paramsArr);
        if($result){
            return $stmt->rowCount();   //成功返回受影响行数
        }else{
            return $result;
        }
    }

    /**
     * query
     */
    public function query($sql,$method = 'fetch',$fetch_type = PDO::FETCH_ASSOC){
        $this->lastSql = $sql;
        $stmt = $this->plink->prepare($this->lastSql);
        $res = $stmt->execute($this->paramsArr);
        if($res){
            switch($method){
                case 'fetch':
                    $this->lastSql .= ' limit 1';
                    return $stmt->fetch($fetch_type);
                    break;
                case 'fetchAll':
                    return $stmt->fetchAll($fetch_type);
                    break;
                case 'update':
                    return $stmt->rowCount();   //成功返回受影响行数
                    break;
                case 'delete':
                    return $stmt->rowCount();
                    break;
            }
        }else{
            return $res;
        }
    }

    /**
     * bindParams
     */
    public function bindParams($params = array()){
        if(empty($params)){
            return $this;
        }
        $this->paramsArr = $params;
        return $this;
    }

    /**
     * order by
     * @params $str string like: 'id desc,username asc'
     */
    public function order($str){
        $this->orderStr = $str;
        return $this;
    }

    /**
     * limit
     * @params $n1 int  $n2 int 
     */
    public function limit($n1 = null,$n2 = null){
        if(isset($n1) && isset($n2)){
            $n1 = $n1 < 0 ? 0 : $n1;
            $n2 = $n2 < 0 ? 0 : $n2;
            $this->limit = " limit $n1,$n2 ";
        }else if(isset($n1) && !isset($n2)){
            $n1 = $n1 < 0 ? 0 : $n1;
            $this->limit = " limit $n1 ";
        }else{
            $this->limit = '';
        }
        return $this;
    }

    /**
     * 查询条件
     */
    public function where($where = array()){
        if(empty($where)){
            return $this;              
        }
        $this->whereStr = ' where';
        $this->paramsArr = array();

        foreach ($where as $k => $v) {

            //array('ne'=>array('status'=>1))
            if($k == '<>' && is_array($v)){
                foreach ($v as $kk => $vv) {
                    $this->whereStr .= " and `$kk` <> :$kk and"; 
                    $this->paramsArr[":$kk"] = $vv;   
                }
                $this->whereStr = rtrim($this->whereStr,'and');
                continue;
            }

            //array('or'=>array('status'=>1,'status'=>0))
            if($k == 'or' && is_array($v)){
                foreach ($v as $kk => $vv) {
                    $this->whereStr .= " `$kk` = :$kk or"; 
                    $this->paramsArr[":$kk"] = $vv;   
                }
                $this->whereStr = rtrim($this->whereStr,'or');
                continue;
            }

            $this->whereStr .= " `$k` = :$k and";
            $this->paramsArr[":$k"] = $v;
        }
        $this->whereStr = rtrim($this->whereStr,'and');
        return $this;
    }

    /**
     * 自动获得表中的主键字段,自动获得表中所有的字段
     */
    private function loadField(){
        //设置sql语句
        $this->lastSql = 'desc `'.$this->tableName.'`';
        //发送sql语句
        $result = $this->plink->query($this->lastSql,2);
        foreach ($result as $rows) {
            if($rows['Key'] == 'PRI'){
                $this->pk = $rows['Field'];
            }
            $this->fieldList[] = $rows['Field'];
        }
        unset($result);
    }

    /**
     * 获取lastSql
     */
    public function getLastSql(){
        return $this->lastSql;
    }

    /**
     * 获取Mysql服务器版本信息
     */
    public function dbInfo(){
        echo "Mysql服务器版本信息：".$this->plink->getAttribute(PDO::ATTR_SERVER_VERSION).
        PHP_EOL."PDO是否关闭自动提交功能：". $this->plink->getAttribute(PDO::ATTR_AUTOCOMMIT).
        PHP_EOL."当前PDO的错误处理的模式：". $this->plink->getAttribute(PDO::ATTR_ERRMODE). 
        PHP_EOL."表字段字符的大小写转换： ". $this->plink->getAttribute(PDO::ATTR_CASE). 
        PHP_EOL."与连接状态相关特有信息： ". $this->plink->getAttribute(PDO::ATTR_CONNECTION_STATUS). 
        PHP_EOL."空字符串转换为SQL的null：". $this->plink->getAttribute(PDO::ATTR_ORACLE_NULLS).
        PHP_EOL."应用程序提前获取数据大小：".$this->plink->getAttribute(PDO::ATTR_PERSISTENT). 
        PHP_EOL."与数据库特有的服务器信息：".$this->plink->getAttribute(PDO::ATTR_SERVER_INFO). 
        PHP_EOL."数据库服务器版本号信息：". $this->plink->getAttribute(PDO::ATTR_SERVER_VERSION).
        PHP_EOL."数据库客户端版本号信息：". $this->plink->getAttribute(PDO::ATTR_CLIENT_VERSION);
    }

    
}