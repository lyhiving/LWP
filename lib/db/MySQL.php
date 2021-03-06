<?php
// 定义返回类型
define('CLIENT_MULTI_RESULTS', 0x20000);
/**
 * mysql 访问类
 * 
 * @author  Lukin <my@lukin.cn>
 * @version $Id$
 */
class DB_MySQL extends DBQuery {
    private $host     = 'localhost';
    private $user     = 'root';
    private $pwd      = '';
    private $pconnect = false;
    private $goneaway = 3;

    /**
     * 初始化连接
     *
     * @param array $config 数据库设置
     */
    public function __construct($config) {
        if (!function_exists('mysql_query')) {
            lwp_error(sprintf(__('您的 PHP 似乎缺少所需的 %s 扩展。'), 'MySQL'), LOGGER_FATAL);
        }
        if (!empty($config)) {
            $this->host     = isset($config['host']) ? $config['host'] : $this->host;
            $this->user     = isset($config['user']) ? $config['user'] : $this->user;
            $this->pwd      = isset($config['pwd']) ? $config['pwd'] : $this->pwd;
            $this->name     = isset($config['name']) ? $config['name'] : $this->name;
            $this->pconnect = isset($config['pconnect']) ? $config['pconnect'] : $this->pconnect;
            if ($this->connect()) {
                $this->select_db();
            }
            if ($this->conn && mysql_errno($this->conn)==0) {
                $this->ready = true;
            }
        }
    }
    /**
     * 连接Mysql
     *
     * @return bool|void
     */
    public function connect(){
        // 检验数据库链接参数
        if (!$this->host || !$this->user)
            lwp_error(__('数据库连接错误，请检查数据库设置！'), LOGGER_FATAL);
        // 连接数据库
        if (function_exists('mysql_pconnect') && $this->pconnect) {
            $this->conn = mysql_pconnect($this->host,$this->user,$this->pwd,CLIENT_MULTI_RESULTS);
        } elseif (function_exists('mysql_connect')) {
            $this->conn = mysql_connect($this->host,$this->user,$this->pwd,false,CLIENT_MULTI_RESULTS);
        }
                
        // 验证连接是否正确
        if (!$this->conn) {
            lwp_error(sprintf(__('数据库链接错误：%s'), mysql_error()), LOGGER_FATAL);
        }
        return $this->conn;
    }

    /**
     * 选择数据库
     *
     * @param string $db (optional)
     * @return bool|void
     */
    public function select_db($db=null){
        // 验证连接是否正确
        if (!$this->conn) $this->connect();
        if (empty($db)) $db = $this->name;
        // 选择数据库
        if (!mysql_select_db($db,$this->conn)) {
            lwp_error(sprintf(__('%s 数据库不存在！'),$db), LOGGER_FATAL);
        }
        // MYSQL数据库的设置
        if (version_compare($this->version(), '4.1', '>=')) {
            if (mysql_client_encoding($this->conn) != 'utf8') {
                mysql_query("SET NAMES utf8;", $this->conn);
            }
            if(version_compare($this->version(), '5.0.1', '>' )) {
                mysql_query("SET sql_mode='';", $this->conn);
            }
        } else {
            lwp_error(__('MySQL数据库版本低于4.1，请升级MySQL！'), LOGGER_WARN);
        }
                
        return true;
    }
    /**
     * 指定函数执行SQL语句
     *
     * @param string $sql	sql语句
     * @return resource
     */
    public function query($sql){
        // 验证连接是否正确
        if (!$this->conn) {
            lwp_error(__('提供的参数不是一个有效的MySQL的链接资源。'), LOGGER_ERROR);
        }
        $args = func_get_args();
                
        $sql = call_user_func_array(array(&$this,'prepare'), $args);

        if ( preg_match("/^\\s*(insert|delete|update|replace|alter table|create) /i",$sql) ) {
            $func = 'mysql_unbuffered_query';
        } else {
            $func = 'mysql_query';
        }

        $_begin_time = microtime(true);

        $this->last_sql = $sql;
        // 统计SQL执行次数
        DBQuery::$query_count++;
        // 执行SQL
        if (!($result = $func($sql,$this->conn))) {
            if (in_array(mysql_errno($this->conn),array(2006,2013)) && ($this->goneaway-- > 0)) {
                $this->close(); $this->connect(); $this->select_db();
                $result = call_user_func_array(array(&$this,'query'), $args);
            } else {
                // 重置计数
                $this->goneaway = 3;
                lwp_error(sprintf(__('MySQL 查询错误：%s'), $sql . "\r\n\t" . mysql_error($this->conn)), LOGGER_ERROR);
            }
        }
        // 记录sql执行日志
        Logger::instance()->debug(sprintf('%01.6f SQL: %s', microtime(true) - $_begin_time, $sql));
        // 查询正常
        if ($result) {
            // 重置计数
            $this->goneaway = 3;
            // 返回结果
            if ($func == 'mysql_unbuffered_query') {
                if ( preg_match("/^\\s*(insert|replace) /i",$sql) ) {
                    $result = ($insert_id = mysql_insert_id($this->conn)) >= 0 ? $insert_id : $this->result("select last_insert_id();");
                } else {
                    $result = mysql_affected_rows($this->conn);
                }
            }
        }
        return $result;
    }
    /**
     * 检查是否存在数据库
     *
     * @param string $dbname
     * @return bool
     */
    public function is_database($dbname){
        $res = $this->query("show databases;");
        while ($rs = $this->fetch($res,0)) {
            if ($dbname == $rs[0]) return true;
        }
        return false;
    }
    /**
     * 判断数据表是否存在
     *
     * 注意表名的大小写，是有区别的
     *
     * @param string $table    table
     * @return bool
     */
    public function is_table($table){
        $res = $this->query(sprintf("show tables from `%s`;", $this->name));
        if (strncmp($table, '#@_', 3) === 0)
            $table = str_replace('#@_', $this->prefix, $table);

        while ($rs = $this->fetch($res,0)) {
            if ($table == $rs[0]) return true;
        }
        return false;
    }
    /**
     * 列出表里的所有字段
     *
     * @param string $table 表名
     * @return array
     */
    public function list_fields($table){
        static $tables = array();
        if (empty($tables[$table])) {
            $res = $this->query(sprintf("show columns from `%s`;", $table));
            while ($rs = $this->fetch($res)) {
                $tables[$table][] = $rs['Field'];
            }
        }
        return $tables[$table];
    }
    /**
     * 获取自增ID
     *
     * @param string $table
     * @return int
     */
    public function autoindex($table) {
        if (strncmp($table, '#@_', 3) === 0) $table = str_replace('#@_', $this->prefix, $table);
        $res = $this->query(sprintf("show table status where `name` ='%s'", $table));
        if ($data = $this->fetch($res)) {
            return $data['Auto_increment'];
        }
        return 1;
    }
    /**
     * 取得数据集的单条记录
     *
     * @param resource  $result
     * @param int       $mode
     * @return array
     */
    public function fetch($result,$mode=1){
        switch (intval($mode)) {
            case 0: $mode = MYSQL_NUM;break;
            case 1: $mode = MYSQL_ASSOC;break;
            case 2: $mode = MYSQL_BOTH;break;
        }
        return mysql_fetch_array($result,$mode);
    }

    /**
     * 取得 MySQL 服务器信息
     *
     * @return string
     */
    public function version(){
        return mysql_get_server_info($this->conn);
    }
    /**
     * 关闭 MySQL 连接
     *
     * @return bool
     */
    public function close(){
        if (is_resource($this->conn)) {
            return mysql_close($this->conn);
        }
        return false;
    }
    /**
     * 转义SQL语句
     *
     * @param mixed $value
     * @return string
     */
    public function escape($value){
        // 空
        if ($value === null) return '';
        // 转义变量
        $value = $this->envalue($value);

        if ( $this->conn )
            $value = mysql_real_escape_string( $value, $this->conn );
        else
            $value = addslashes( $value );
        
        return $value;
    }
}
