<?php
namespace Vendors;
/**
 * https://www.cnblogs.com/weafer/archive/2011/09/21/2184059.html
 * @author duxin
 *
 */
class Cache
{

    private static $handler = null;

    private static $_instance = null;

    private static $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'pass' => '',
        'select' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => ''
    ];

    private function __construct()
    {
        $options  = require  'redis.php';
        if (! empty($options)) {
            self::$options = array_merge(self::$options, $options);
        }
        $func = self::$options['persistent'] ? 'pconnect' : 'connect'; // 长链接
        self::$handler = new \Redis();
        self::$handler->$func(self::$options['host'], self::$options['port'], self::$options['timeout']);
        
        if ('' != self::$options['pass']) {
            self::$handler->auth(self::$options['pass']);
        }
        
        if (0 != self::$options['select']) {
            self::$handler->select(self::$options['select']);
        }
    }

    /**
     *
     * @return RedisPackage|null 对象
     */
    public static function getInstance()
    {
        if (! (self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 禁止外部克隆
     */
    public function __clone()
    {
        trigger_error('Clone is not allow!', E_USER_ERROR);
    }

    /**
     * 写入缓存
     * 
     * @param string $key
     *            键名
     * @param string $value
     *            键值
     * @param int $exprie
     *            过期时间 0:永不过期
     * @return bool
     */
    public static function set($key, $value, $exprie = 0)
    {
        if ($exprie == 0) {
            $set = self::$handler->set($key, $value);
        } else {
            $set = self::$handler->setex($key, $exprie, $value);
        }
        return $set;
    }

    /**
     * 读取缓存
     * 
     * @param string $key
     *            键值
     * @return mixed
     */
    public static function get($key)
    {
        $fun = is_array($key) ? 'Mget' : 'get';
        return self::$handler->{$fun}($key);
    }

    /**
     * 获取值长度
     * 
     * @param string $key            
     * @return int
     */
    public static function lLen($key)
    {
        return self::$handler->lLen($key);
    }

    /**
     * 将一个或多个值插入到列表头部
     * 
     * @param
     *            $key
     * @param
     *            $value
     * @return int
     */
    public static function LPush($key, $value)
    {
        return self::$handler->lPush($key, $value);
    }

    /**
     * 移出并获取列表的第一个元素
     * 
     * @param string $key            
     * @return string
     */
    public static function lPop($key)
    {
        return self::$handler->lPop($key);
    }
    
    public static function lall($key){
        return self::$handler->lGetRange($key,0,-1);
    }
}