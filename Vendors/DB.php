<?php
namespace Vendors;

use Configs\DbConfig;

class DB
{
    
    private static $dbh;
    
    public $tableName = '';
    
    public $prefix = '';
    
    public $where = '';
    
    public $limit = '';
    
    public $between = '';
    
    public $order = '';
    
    public $group = '';
    
    public $having = '';
    
    public $leftjoin = '';
    
    public $fields = '';
    
    public $sql = '';
    
    public $numberPerPage = 10;
    
    protected $primaryKey = 'id';
    
    private $rst;
    
    private $dbConfig;
    
    private static $instance;
    
    public function __construct($table = null)
    {
        $this->dbConfig = (new DbConfig())->toArray();
        if (! self::$dbh) {
            self::$dbh = $this->connect();
        }
        $this->initialTable($table);
    }
    
    public static function getInstance($table = null)
    {
        if (! (self::$instance instanceof self)) {
            self::$instance = new self($table);
        } else {
            self::$instance->initialTable($table);
        }
        return self::$instance;
    }
    
    private function initialTable($table = null)
    {
        if (! $this->tableName) {
            if (! $table) {
                preg_match_all('/\\\(([A-Z][a-z0-9]+)+)Model$/x', get_called_class(), $matches);
                $table = pluralize(camel2underline($matches[1][0]));
            }
            $this->tableName = $table;
        }
        if (! preg_match('/^' . $this->dbConfig['prefix'] . '/', $this->tableName)) {
            $this->tableName = $this->dbConfig['prefix'] . $this->tableName;
        }
    }
    
    private function connect()
    {
        if (! $this->dbConfig) {
            return false;
        }
        $dsn = 'mysql:dbname=' . $this->dbConfig['libr'] . ';host=' . $this->dbConfig['host'] . ';port=' . $this->dbConfig['port'] . ';charset=' . $this->dbConfig['charset'];
        try {
            if (isset($this->dbConfig['persistent']) && $this->dbConfig['persistent']) {
                
                self::$dbh = new \PDO($dsn, $this->dbConfig['user'], $this->dbConfig['pass'], [
                    \PDO::ATTR_PERSISTENT => true
                ]);
            } else {
                self::$dbh = new \PDO($dsn, $this->dbConfig['user'], $this->dbConfig['pass']);
            }
            self::$dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$dbh->query('set names utf8');
            self::$dbh->query('set time_zone = \'+8:00\'');
        } catch (\PDOException $e) {
            echo 'msg:' . $e->getMessage() . '<br/>';
            echo 'file:' . $e->getFile() . '<br/>';
            echo 'line:' . $e->getLine() . '<br/>';
            echo 'code:' . $e->getCode() . '<br/>';
        }
        $this->prefix = $this->dbConfig['prefix'];
        
        return self::$dbh;
    }
    
    /**
     * 查询
     *
     * @param mix $fields
     * @param array $where
     */
    private function read($fields = '*', $where = 1, $extra = '', $isAll = false)
    {
        $clums = '';
        if (is_array($fields) && ! empty($fields)) {
            foreach ($fields as $clum) {
                $clums .= '`' . $clum . '`,';
            }
            $fields = substr($clums, 0, - 1);
        }
        
        $condition = '';
        
        if (is_array($where) && ! empty($fields)) {
            foreach ($where as $k => $v) {
                $condition .= $k . ' = \'' . $v . '\' and ';
            }
            $where = substr($condition, 0, - 4);
        }
        $query = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' WHERE ' . $where;
        if (! empty($extra)) {
            $query .= ' ' . $extra;
        }
        if (! $isAll) {
            $query .= ' limit 1';
        }
        
        return self::$dbh->query($query);
    }
    
    public function selectOne($sql)
    {
        $rst = self::$dbh->query($sql);
        $rst->setFetchMode(\PDO::FETCH_ASSOC);
        return $rst->fetchColumn();
    }
    
    public function selectRow($sql)
    {
        $rst = self::$dbh->query($sql);
        $rst->setFetchMode(\PDO::FETCH_ASSOC);
        return $rst->fetch();
    }
    
    public function selectAll($sql)
    {
        $rst = self::$dbh->query($sql);
        $rst->setFetchMode(\PDO::FETCH_ASSOC);
        return $rst->fetchAll();
    }
    
    /**
     * 查询一条记录
     *
     * @param mix $fields
     *            查询字段
     * @param mix $where
     *            查询条件
     * @return array $rst 返回结果为一维数组
     */
    public function readRow($fields = '*', $where = 1, $extra = '')
    {
        $rst = $this->read($fields, $where, $extra);
        $rst->setFetchMode(\PDO::FETCH_ASSOC);
        return $rst->fetch();
    }
    
    /**
     * 查询记录集
     *
     * @param mix $fields
     *            查询字段
     * @param mix $where
     *            查询条件
     * @return array $rst 返回结果为二维数组
     */
    public function readAll($fields = '*', $where = 1, $extra = '')
    {
        $rst = $this->read($fields, $where, $extra, true);
        $rst->setFetchMode(\PDO::FETCH_ASSOC);
        return $rst->fetchAll();
    }
    
    /**
     * 查询一条记录
     *
     * @param mix $fields
     *            查询字段
     * @param mix $where
     *            查询条件
     * @return string $rst 返回结果为一个字符串
     */
    public function readOne($fields = '*', $where = 1, $extra = '')
    {
        $rst = $this->read($fields, $where, $extra);
        return $rst->fetchcolumn();
    }
    
    /**
     * 数据更新操作
     *
     * @param mix $fields
     *            更新字段，可以为数组
     * @param mix $where
     *            更新条件，可以为数组
     * @return int 受影响的行数
     */
    public function update($fields, $where)
    {
        $condition = '';
        $clums = '';
        if (is_array($fields)) {
            foreach ($fields as $k => $v) {
                $clums .= $k . '=\'' . $v . '\',';
            }
            $fields = substr($clums, 0, - 1);
        }
        
        if (is_array($where)) {
            foreach ($where as $k => $v) {
                $condition .= $k . '=\'' . $v . '\' and ';
            }
            $where = substr($condition, 0, - 4);
        }
        $sql = 'UPDATE ' . $this->tableName . ' SET ' . $fields . ' WHERE ' . $where;
        return self::$dbh->exec($sql);
    }
    
    /**
     * 数据插入
     *
     * @param array $fields
     *            插入的字段和值
     * @return state
     */
    public function insert($fields, $insertid = false, $onDuplicate = null)
    {
        $columns = '';
        $columns1 = '';
        $values = '';
        if (is_array($fields)) {
            foreach ($fields as $k => $v) {
                $columns .= '`' . $k . '`,';
                $values .= '\'' . $v . '\',';
            }
            $columns = substr($columns, 0, - 1);
            $values = substr($values, 0, - 1);
        } else {
            return false;
        }
        $query = 'INSERT INTO ' . $this->tableName . '(' . $columns . ' ) VALUES ( ' . $values . ' ) ';
        if (is_array($onDuplicate)) {
            foreach ($onDuplicate as $k1 => $v1) {
                $columns1 .= '`' . $k1 . '`=\'' . $v1 . '\',';
            }
            $onDuplicate = substr($columns1, 0, - 1);
        }
        if (! empty($onDuplicate)) {
            $query .= 'ON DUPLICATE KEY UPDATE ' . $onDuplicate;
        }
        
        $rst = self::$dbh->exec($query);
        if ($insertid) {
            return self::$dbh->lastInsertId();
        }
        return $rst;
    }
    
    /**
     * delete
     *
     * @param mix $fields
     * @param array $where
     */
    public function delete($where = 1)
    {
        $condition = '';
        if (is_array($where)) {
            foreach ($where as $k => $v) {
                $condition .= $k . ' = \'' . $v . '\' and ';
            }
            $where = substr($condition, 0, - 4);
        }
        $query = 'DELETE  FROM ' . $this->tableName . ' WHERE ' . $where;
        return self::$dbh->query($query);
    }
    
    /**
     * 连查用
     *
     * @param number $from
     * @param number $number
     * @return \Vendors\DB
     */
    public function limit($from = 0, $number = 1)
    {
        $limit = ' LIMIT ' . $from . ',' . $number;
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * 连查用
     *
     * @param string $having
     * @return \Vendors\DB
     */
    public function having($having = '')
    {
        $this->having = empty($having) === false ? ' HAVING ' . $having : '';
        return $this;
    }
    
    /**
     * 连查用
     *
     * @param unknown $table
     * @param unknown $condition
     * @return \Vendors\DB
     */
    public function leftJoin($table, $condition)
    {
        $this->joinTable = $this->prefix . pluralize($table);
        $left_join = '';
        if (! empty($table) && is_array($condition)) {
            foreach ($condition as $key => $value) {
                $left_join .= " `{$this->tableName}`.`{$key}`=`{$this->joinTable}`.`{$value}` AND ";
            }
            $left_join = " LEFT JOIN {$this->joinTable} ON " . substr($left_join, 0, - 4);
        }
        $this->leftjoin = $left_join;
        return $this;
    }
    
    /**
     * 连查用
     *
     * @param number $conditions
     * @param string $connect
     * @return \Vendors\DB
     */
    public function where(...$conditions)
    {
        $condition = '';
        if (isset($conditions[0]) && ! empty($conditions[0])) {
            foreach ($conditions[0] as $k => $v) {
                $condition .= " AND `{$this->tableName}`.`" . $k . '` = \'' . $v . '\' ';
            }
        }
        if (isset($conditions[1]) && !empty($conditions[1])) {
            foreach ($conditions[1] as $k => $v) {
                $condition .= " AND `{$this->joinTable}`.`" . $k . '` = \'' . $v . '\' ';
            }
        }
        $conditions = $condition;
        $this->where = ' WHERE 1 ' . $conditions;
        return $this;
    }
    
    /**
     * 连查用
     *
     * @param unknown $segments
     * @param string $asc
     * @return \Vendors\DB
     */
    public function order($segments, $asc = 'asc')
    {
        $orderSql = '';
        if (is_array($segments)) {
            foreach ($segments as $column) {
                $orderSql .= '`' . $column . '`,';
            }
            $orderSql = ' order by  ' . substr($orderSql, 0, - 1) . ' ' . $asc;
        } else {
            $orderSql = ' order by  ' . $segments . ' ' . $asc;
        }
        $this->order = $orderSql;
        return $this;
    }
    
    /**
     * 连查用
     *
     * @param unknown $segments
     * @param string $asc
     */
    public function group($segments)
    {
        $groupSql = '';
        if (is_array($segments)) {
            foreach ($segments as $column) {
                $groupSql .= '`' . $column . '`,';
            }
            $groupSql = ' GROUP BY  ' . substr($groupSql, 0, - 1);
        }
        $this->group = empty($groupSql) === false ? $groupSql : ' GROUP BY  ' . $segments;
        return $this;
    }
    
    /**
     * 连查用
     *
     * @param string $fields
     * @return \Vendors\DB
     */
    public function fields($fields = '*')
    {
        $clums = '';
        if (is_array($fields) && ! empty($fields)) {
            foreach ($fields as $clum) {
                $clums .= '`' . $clum . '`,';
            }
            $fields = substr($clums, 0, - 1);
        }
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * 连查用
     *
     * @return \Vendors\DB
     */
    private function sql()
    {
        $this->sql = '';
        if ($this->fields) {
            $this->sql = 'SELECT ' . $this->fields . ' FROM ' . $this->tableName;
        } else {
            $this->sql = 'SELECT * FROM ' . $this->tableName;
        }
        if ($this->leftjoin) {
            $this->sql .= $this->leftjoin;
        }
        if ($this->where) {
            $this->sql .= $this->where;
        }
        if ($this->group) {
            $this->sql .= $this->group;
        }
        
        if ($this->having) {
            $this->sql .= $this->having;
        }
        
        if ($this->order) {
            $this->sql .= $this->order;
        }
        
        if ($this->limit) {
            $this->sql .= $this->limit;
        }
        return $this;
    }
    
    /**
     * 连查用
     *
     * @return \Vendors\DB
     */
    public function queryDB()
    {
        $rst = self::$dbh->query($this->sql);
        $rst->setFetchMode(\PDO::FETCH_ASSOC);
        $this->rst = $rst;
        return $this;
    }
    
    /**
     * 连查用
     *
     * @return array
     */
    public function get(...$feilds)
    {
        $rst = $this->sql()->queryDB($this->sql);
        return $this->rst->fetchAll();
    }
    
    /**
     * 连查用
     *
     * @return array
     */
    public function getAll(...$fields)
    {
        $rst = $this->sql();
        
        if (empty($fields)) {
            $this->fields = '*';
        }
        if (count($fields) > 1) {
            foreach ($fields[0] as $tableKey1) {
                $this->fields .= "`{$this->tableName}`.`{$tableKey1}`,";
            }
            foreach ($fields[1] as $tableKey2) {
                $this->fields .= "`{$this->joinTable}`.`{$tableKey2}`,";
            }
            $this->fields = trim($this->fields, ',');
        }
        
        $rst = $rst->queryDB($this->sql);
        
        return $this->rst->fetchAll();
    }
    
    /**
     * 连查用
     *
     * @return mixed
     */
    public function getRow(...$fields)
    {
        if (empty($fields)) {
            $this->fields = '*';
        }
        if (count($fields) > 1) {
            foreach ($fields[0] as $tableKey1) {
                $this->fields .= "`{$this->tableName}`.`{$tableKey1}`,";
            }
            foreach ($fields[1] as $tableKey2) {
                $this->fields .= "`{$this->joinTable}`.`{$tableKey2}`,";
            }
            $this->fields = trim($this->fields, ',');
        }
        
        $rst = $this->sql()->queryDB($this->sql);
        return $this->rst->fetch();
    }
    
    /**
     * 连查用
     *
     * @return mixed
     */
    public function getOne(...$fields)
    {
        if (empty($fields)) {
            $this->fields = '*';
        }
        if (count($fields) > 1) {
            foreach ($fields[0] as $tableKey1) {
                $this->fields .= "`{$this->tableName}`.`{$tableKey1}`,";
            }
            foreach ($fields[1] as $tableKey2) {
                $this->fields .= "`{$this->joinTable}`.`{$tableKey2}`,";
            }
            $this->fields = trim($this->fields, ',');
        }
        $rst = $this->sql()->queryDB($this->sql);
        return $this->rst->fetchColumn();
    }
    
    public function paginate($page = 1, $numberPerPage = 0, $sql = '')
    {
        if (! $numberPerPage) {
            $numberPerPage = $this->numberPerPage;
        }
        $this->limit = '';
        if ($sql) {
            $this->sql = $sql;
        } else {
            $rst = $this->sql();
        }
        $rst = $this->queryDB($this->sql);
        
        $data = $this->rst->fetchAll();
        $total = count($data);
        if ($total <= $numberPerPage) {
            if ($page > 1) {
                $data = [];
            }
            return [
                'prevPage' => 1,
                'currentPage' => $page,
                'nextPage' => 1,
                'totalPage' => 1,
                'numberPerPage' => $numberPerPage,
                'total' => $total,
                'data' => $data
            ];
        } else {
            $start = ($page - 1) * $numberPerPage;
            if ($start >= $total) {
                $data = [];
            } else {
                $this->sql .= ' LIMIT ' . $start . ',' . $numberPerPage;
                $rst = $this->queryDB($this->sql);
                $data = $this->rst->fetchAll();
            }
            
            return [
                'prevPage' => ($page - 1) ? ($page - 1) : 1,
                'currentPage' => $page,
                'nextPage' => $page + 1,
                'totalPage' => ceil($total / $numberPerPage),
                'numberPerPage' => $numberPerPage,
                'total' => $total,
                'data' => $data
            ];
        }
    }
    
    /**
     * 连查用
     *
     * @return number
     */
    protected function execDB()
    {
        $rst = self::$dbh->exec($this->sql);
        return $rst;
    }
    
    protected function fetch($one = 0)
    {
        $info = $this->rst->fetchAll();
        if (count($info) == 1 && $one == 0) {
            return $info[0];
        } else {
            return $info;
        }
    }
    
    public function releases()
    {
        $this->fields = '';
        $this->leftjoin = '';
        $this->where = '';
        $this->group = '';
        $this->having = '';
        $this->order = '';
        $this->limit = '';
        return $this;
    }
    
    /**
     * 获取表中的字段名
     *
     * @param type $table
     * @return type
     */
    public function desc($table = '')
    {
        $this->sql = 'desc ' . ($table ? $table : $this->tableName);
        $result = $this->querydb()->fetch(1);
        $info = array();
        if ($result) {
            foreach ($result as $v) {
                $info[] = $v['Field'];
            }
        }
        return $info;
    }
    
    public function table($table)
    {
        $this->tableName = $this->prefix . $table;
        return $this;
    }
}