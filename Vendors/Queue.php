<?php
namespace Vendors;

class Queue
{

    private $channelPrefix = '';

    private $config = [
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'pass' => '',
            'select' => 0,
            'persistent' => true,
            'timeout' => - 1
        ],
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'pass' => '',
            'libr' => '',
            'table' => '',
            'persistent' => '',
            'timeout' => - 1
        ]
    ];

    private $prioritys = [
        1 => 'high',
        2 => 'normal',
        3 => 'low'
    ];

    private $redisConfig = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'pass' => '',
        'select' => 0,
        'persistent' => true,
        'timeout' =>  1
    ];

    private $driver;

    private $mysqlConfig = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'pass' => '',
        'libr' => '',
        'table' => '',
        'persistent' => '',
        'timeout' => - 1
    ];

    private static $instance;

    private static $queueDriver;

    private static $redis;

    private static $mysql;

    public function __construct($driver, $configs = [])
    {
        $this->config = array_merge($this->config, $configs);
        if (! self::$queueDriver) {
            self::$queueDriver = $this->getDriver($driver, $this->config);
        }
        $this->driver = $driver;
    }

    public static function getInstance($dirver = 'redis', $configs = [])
    {
        if (! self::$instance) {
            self::$instance = new self($dirver, $configs);
        }
        return self::$instance;
    }

    public function push($message, $channel, $priority = 'normal')
    {
        switch ($this->driver) {
            case 'redis':
                return $this->redisPush($message, $channel, $priority = 'normal');
                break;
            case 'mysql':
                return $this->mysqlPush($message, $channel, $priority = 'normal');
                break;
            default:
                return false;
        }
    }

    /**
     * 消费消息
     *
     * @param string $channel            
     * @param string $priority
     *            high|normal|low|0 表示全部
     *            
     */
    public function pop($channel, $priority = 0)
    {
        switch ($this->driver) {
            case 'redis':
                return $this->redisPop($channel, $priority);
                break;
            case 'mysql':
                return $this->mysqlPop($channel, $priority);
                break;
            default:
                return false;
        }
    }

    public function destory()
    {
        ;
    }

    private function getDriver($driver, $configs)
    {
        switch ($driver) {
            case 'redis':
                $configs = array_merge($this->redisConfig, $configs['redis']);
                $queueDriver = $this->getRedis($configs);
                return $queueDriver;
                break;
            case 'mysql':
                $configs = array_merge($this->redisConfig, $configs['mysql']);
                $queueDriver = $this->getMysql($configs);
                return $queueDriver;
                break;
            default:
                return false;
        }
    }

    private function getRedis($configs)
    {
        if (! self::$redis or ! self::$redis->isConnected()) {
            $func = $configs['persistent'] ? 'pconnect' : 'connect'; // 长链接
            self::$redis = new \Redis();
            self::$redis->$func($configs['host'], $configs['port']);
            if ('' != $configs['pass']) {
                self::$redis->auth($configs['pass']);
            }
            if (0 != $configs['select']) {
                self::$redis->select($configs['select']);
            }
        }
        return self::$redis;
    }

    private function redisPush($message, $channel, $priority)
    {
        $key = $this->channelPrefix . $channel . '_' . $priority;
        return self::$redis->lPush($key, $message);
    }

    private function redisPop($channel, $priority)
    {
        if ($priority == 0 || ! in_array($priority, $this->prioritys)) {
            $prefix = $this->channelPrefix . $channel . '_';
            $message = self::$redis->rPop($prefix . 'high');
            if ($message) {
                return $message;
            } else {
                $message = self::$redis->rPop($prefix . 'normal');
                if ($message) {
                    return $message;
                } else {
                    $message = self::$redis->rPop($prefix . 'low');
                    return $message;
                }
            }
        } else {
            $key = $this->channelPrefix . $channel . '_' . $priority;
            return self::$redis->rPop($key);
        }
    }

    private function getMysql($configs)
    {
        $this->mysqlConfig = array_merge($this->mysql, $this->configs['mysql']);
        $link = new \mysqli($this->mysqlConfig['host'], $this->mysqlConfig['user'], $this->mysqlConfig['pass'], $this->mysqlConfig['libr'], $this->mysqlConfig['port']);
        $link->query('set names "' . $this->mysqlConfig['charset'] . '"');
        return $link;
    }

    private function mysqlPush($message, $channel, $priority)
    {
        $insertSQL = "insert into `" . $this->mysqlConfig['table'] . "`";
        $insertSQL .= "(`queue`,`message`,`priority`,`reserved`,`created_at`)values(";
        $insertSQL .= "'{$channel}','{$message}',$priority,0," . time() . ");";
    }

    private function mysqlPop($channel, $priority = 0)
    {
        if ($priority) {
            $query = "select `id` , `message`,`queue`,`prority` from `" . $this->mysqlConfig['table'] . "` where `reserved`=0 and order by `priority`={$priority}, `id` asc limit 1";
        } else {
            $query = "select `id`,`message`,`queue`,`prority` from `" . $this->mysqlConfig['table'] . "` where `reserved`=0 order by `priority`, `id` asc limit 1";
        }
        $rst = self::$queueDriver->query($query);
        if ($rst->num_rows == 0) {
            return false;
        }
        $message = mysqli_fetch_assoc($rst);
        $update = "update `" . $this->mysqlConfig['table'] . "` set `reserved`=1, `reserved_at`=" . time() . "  where `id`={$message['id']}";
        self::$queueDriver->query($update);
        return $message['message'];
    }

    private function initialQueueTable()
    {
        $createsql = "
        CREATE TABLE `wl_message_queues` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `queue` varchar(255) NOT NULL COMMENT '队列，渠道名称',
          `message` text COMMENT '消息内容',
          `priority` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '队列优先级',
          `reserved` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否消费',
          `reserved_at` int(10) unsigned DEFAULT NULL COMMENT '消费时间',
          `created_at` int(10) unsigned NOT NULL COMMENT '创建时间',
          PRIMARY KEY (`id`),
          KEY `message_queue_priority_reserved_reserved_at_index` (`queue`,`priority`,`reserved`,`reserved_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '消息队列表'
     ";
    }
}

?>
