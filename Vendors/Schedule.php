<?php
namespace Vendors;

class Schedule
{

    private $queueName = 'wlxs_schedule_tasks';

    /**
     * 存储类型
     *
     * @var string start
     */
    private $driver = 'redis';

    private $configs = [
        'driver' => 'redis',
        'timezone' => 'Asia/Shanghai',
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
            'table' => 'message_queues',
            'persistent' => '',
            'timeout' => - 1
        ]
    ];

    private $basePath = '';

    private $redisConfig = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'pass' => '',
        'select' => 0,
        'persistent' => '',
        'storage_key' => '',
        'timeout' => 2
    ];

    private $mysqlConfig = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'pass' => '',
        'libr' => '',
        'user' => '',
        'charset' => 'UTF8',
        'persistent' => '',
        'storage_table' => 'task',
        'timeout' => - 1
    ];

    private $fileConfig = [
        'schedule' => 'storage/cache/schedule'
    ];

    private static $handler;

    private $timestamp = 0;

    private static $instance;

    private $runLog = 'storage/cronlog';

    private $rsyncLog = 'storage/rsync.output';

    private static $commandBin = 'bin';

    public function __construct($configs = [])
    {
        $this->init($configs);
    }

    private function init($configs)
    {
        date_default_timezone_set($this->configs['timezone']);
        $this->configs = array_merge($this->configs, $configs);
        $this->driver = $this->configs['driver'];
        $this->timestamp = time();
        $this->basePath = dirname(__DIR__);
        switch ($this->driver) {
            case 'file':
                $this->fileConfig = array_merge($this->fileConfig, $this->configs['file']);
                self::$handler = $this->basePath . '/' . $this->fileConfig['schedule'];
                if (! file_exists(self::$handler)) {
                    $fp = fopen(self::$handler, 'w');
                    fclose($fp);
                }
                break;
            case 'redis':
                $this->redisConfig = array_merge($this->redisConfig, $this->configs['redis']);
                self::$handler = new \Redis();
                self::$handler->pconnect($this->redisConfig['host'], $this->redisConfig['port']);
                if ($this->redisConfig['pass']) {
                    self::$handler->auth($this->redisConfig['pass']);
                }
                break;
            case 'mysql':
                $this->mysqlConfig = array_merge($this->mysql, $this->configs['mysql']);
                self::$handler = new \mysqli($this->mysqlConfig['host'], $this->mysqlConfig['user'], $this->mysqlConfig['pass'], $this->mysqlConfig['libr'], $this->mysqlConfig['port']);
                self::$handler->query('set names "' . $this->mysqlConfig['charset'] . '"');
                break;
        }
    }

    public static function getInstance($configs = [])
    {
        if (! self::$instance) {
            self::$instance = new self($configs);
        }
        return self::$instance;
    }

    /**
     * 运行Job
     */
    public function run($sync = false)
    {
        $tasks = $this->getTasks();
        $currentTasks = $this->getCurrentTasks($tasks);
        if (empty($currentTasks)) {
            return true;
        }
        if ($sync) {
            return $this->syncExec($currentTasks);
        } else {
            return $this->rsyncExec($currentTasks);
        }
    }

    /**
     * 添加任务
     *
     * @param string $rule
     *            '* * * * * cat /dev/null'
     * @param string $is_block            
     * @return boolean
     */
    public function add($rule, $is_block = false)
    {
        $item = array_merge([
            'id' => 1,
            'command' => '',
            'times' => 0,
            'min' => '*',
            'hour' => '*',
            'date' => '*',
            'day' => '*',
            'min_range' => range(0, 60),
            'hour_range' => range(0, 24),
            'date_range' => range(0, 31),
            'month_range' => range(0, 12),
            'day_range' => range(0, 7),
            'create_at' => time(),
            'start_at' => time(),
            'last_time' => null,
            'is_block' => 0,
            'running' => 0,
            'result' => null,
            'output' => null,
            'note' => null,
            'status' => 1
        ], $this->parseRule($rule));

        switch ($this->driver) {
            case 'file':
                $result = $this->addTaskToFile($item, $is_block);
                break;
            case 'redis':
                $result = $this->addTaskToRedis($item, $is_block);
                break;
            case 'mysql':
                $result = $this->addTaskToMysql($item, $is_block);
                break;
            default:
                $result = false;
        }
        return $result;
    }

    /**
     * 显示所有任务
     *
     * @param string $status            
     * @param string $blocked            
     * @param string $running            
     * @return array
     */
    public function list($status = '*', $blocked = '*', $running = '*')
    {
        $tasks = $this->getTasks();
        $filterTasks = [];
        foreach ($tasks as $task) {
            if ((($status === '*') ? 1 : ($task['status'] == intval($status))) && (($blocked === '*') ? 1 : ($task['blocked'] == intval($blocked))) && (($running === '*') ? 1 : ($task['running'] == intval($running)))) {
                $filterTasks[$task['id']] = $task;
            }
        }
        return $filterTasks;
    }

    /**
     * 销毁任务
     *
     * @param string $task            
     */
    public function destory($task_id = '')
    {
        if (empty($task_id)) {
            switch ($this->driver) {
                case 'file':
                    $result = $this->destoryTasksFromFile();
                    break;
                case 'redis':
                    $result = $this->destoryTasksFromRedis();
                    break;
                case 'mysql':
                    $result = $this->destoryTasksFromMysql();
                    break;
                default:
                    $result = false;
            }
            return $result;
        } else {
            switch ($this->driver) {
                case 'file':
                    $result = $this->destoryTaskFromFile($task_id);
                    break;
                case 'redis':
                    $result = $this->destoryTaskFromRedis($task_id);
                    break;
                case 'mysql':
                    $result = $this->destoryTaskFromMysql($task_id);
                    break;
                default:
                    $result = false;
            }
            return $result;
        }
    }

    /**
     * 停止任务
     *
     * @param string $task            
     */
    public function drop($task_id = '')
    {
        if (empty($task_id)) {
            switch ($this->driver) {
                case 'file':
                    $result = $this->dropTasksFromFile();
                    break;
                case 'redis':
                    $result = $this->dropTasksFromRedis();
                    break;
                case 'mysql':
                    $result = $this->dropTasksFromMysql();
                    break;
                default:
                    $result = false;
            }
            return $result;
        } else {
            switch ($this->driver) {
                case 'file':
                    $result = $this->dropTaskFromFile($task_id);
                    break;
                case 'redis':
                    $result = $this->dropTaskFromRedis($task_id);
                    break;
                case 'mysql':
                    $result = $this->dropTaskFromMysql($task_id);
                    break;
                default:
                    $result = false;
            }
            return $result;
        }
    }

    /**
     * 重新启用任务
     *
     * @param string $task_id            
     * @return boolean
     */
    public function reuse($task_id = '')
    {
        if (empty($task_id)) {
            switch ($this->driver) {
                case 'file':
                    $result = $this->reuseTasksFromFile();
                    break;
                case 'redis':
                    $result = $this->reuseTasksFromRedis();
                    break;
                case 'mysql':
                    $result = $this->reuseTasksFromMysql();
                    break;
                default:
                    $result = false;
            }
            return $result;
        } else {
            switch ($this->driver) {
                case 'file':
                    $result = $this->reuseTaskFromFile($task_id);
                    break;
                case 'redis':
                    $result = $this->reuseTaskFromRedis($task_id);
                    break;
                case 'mysql':
                    $result = $this->reuseTaskFromMysql($task_id);
                    break;
                default:
                    $result = false;
            }
            return $result;
        }
    }

    /**
     * 获取 所有任务列表
     *
     * @return array
     */
    private function getTasks()
    {
        $tasks = [];
        switch ($this->driver) {
            case 'file':
                $tasks = $this->getTasksFromFile();
                break;
            case 'redis':
                $tasks = $this->getTasksFromRedis();
                break;
            case 'mysql':
                $tasks = $this->getTasksFromMysql();
                break;
            default:
                return false;
        }
        return $tasks;
    }

    private function parseRule($rule)
    {
        list ($min, $hour, $date, $month, $day, $command) = preg_split("/[\s]+/", $rule, 6);
        $item = [];
        $item['min_range'] = $this->getRange($min, 0, 60);
        $item['hour_range'] = $this->getRange($hour, 0, 24);
        $item['date_range'] = $this->getRange($date, 0, 31);
        $item['month_range'] = $this->getRange($month, 0, 12);
        $item['day_range'] = $this->getRange($day, 0, 7);
        $item['command'] = $command;
        return array_merge(compact('min', 'hour', 'date', 'month', 'day'), $item);
    }

    private function getRange($string, $min, $max, $start = 0)
    {
        $range = range($min, $max);
        if ($string == '*') {
            return $range;
        }
        $commas = preg_split('#\s*,{1}\s*#', $string);
        $tmpRange = [];
        
        foreach ($commas as $comma) {
            if (strpos($comma, '/')) {
                list ($tmp, $interval) = explode('/', $comma);
                $tmpRange = array_merge($tmpRange, range($min, $max, (int) $interval));
            } else if (strpos($comma, '-')) {
                list ($tmpMin, $tmpMax) = explode('-', $comma);
                $tmpRange = array_merge($tmpRange, range((int) $tmpMin, (int) $tmpMax));
            } else {
                $tmpRange = array_merge($tmpRange, [
                    (int) $comma
                ]);
            }
        }
        return array_values(array_intersect($range, $tmpRange));
    }

    private function getCurrentTasks($tasks)
    {
        $currentTasks = [];
        foreach ($tasks as $task) {
            if ($this->filterTask($task)) {
                $currentTasks[] = $task;
            }
        }
        return $currentTasks;
    }

    /**
     * 根据任务信息过滤能否执行
     *
     * @param array $task            
     * @return boolean
     */
    private function filterTask(array $task)
    {
        $now = $this->getCurrentTime();
        
        if ($task['is_block'] && $task['running']) {
            return false; // 任务阻塞中
        }
        
        if ($task['status'] == 0) {
            return false; // 任务已终止
        }
        
        if ($task['start_at'] > $this->timestamp) {
            return false; // 任务尚未开始
        }
        
        if ($task['min'] != '*') {
            if (! in_array($now['min'], $task['min_range'])) {
                return false;
            }
        }
        if ($task['hour'] != '*') {
            if (! in_array($now['hour'], $task['hour_range'])) {
                return false;
            }
        }
        
        if ($task['date'] != '*') {
            if (! in_array($now['date'], $task['date_range'])) {
                return false;
            }
        }
        
        if ($task['month'] != '*') {
            if (! in_array($now['month'], $task['month_range'])) {
                return false;
            }
        }
        
        if ($task['day'] != '*') {
            if (! in_array($now['day'], $task['day_range'])) {
                return false;
            } else {
                return true;
            }
        }
        
        return true;
    }

    private function getCurrentTime()
    {
        $datetime = date("i-G-j-n-N", $this->timestamp); // 分-时间-日-月-星期(0开始)
        $dArr = explode('-', $datetime);
        // 未使用 array_combine，因分钟 含有0 单独处理也可以
        $time = [];
        $time['min'] = $dArr[0];
        $time['hour'] = intval($dArr[1]);
        $time['date'] = $dArr[2];
        $time['month'] = $dArr[3];
        $time['day'] = $dArr[4];
        return $time;
    }

    /**
     * 同步执行
     *
     * @param array $tasks            
     */
    private function syncExec($tasks)
    {
        foreach ($tasks as $task) {
            $this->runTask($task['id']);
            $rst = exec($task['command'], $output, $result);
            $this->finishTask($task['id'], $output, $result);
        }
        return true;
    }

    /**
     * 异步执行
     *
     * @param array $tasks            
     */
    private function rsyncExec($tasks, $is_queue = false)
    {
        if ($is_queue) {
            $return = false;
            foreach ($tasks as $task) {
                $this->runTask($task);
                $result = Queue::getInstance()->push(json_encode($tasks), $this->queueName);
                $return = $this->finishTask($task['id']);
            }
            return $return;
        } else {
            if (PHP_OS == 'Linux' || PHP_OS == 'Darwin') {
                $currentPath = exec('pwd');
                $this->basePath = dirname($currentPath);
                foreach ($tasks as $task) {
                    $this->runTask($task['id']);
                    if(strpos('>>',$task["command"])){
                        $cmd = $task["command"] . ' >> ' . $this->basePath . '/' . $this->rsyncLog . ' 2>&1 &';
                    }else{
                        $cmd = $task["command"] . ' 2>&1 &';
                    }
                    exec($cmd);
                    $this->finishTask($task['id']);
                }
            } else if (PHP_OS == 'WINNT') {
                $this->runTask($task['id']);
                passthru('start  /B ' . $task["command"] . ' >' . $this->basePath . '/' . $this->rsyncLog);
                $this->finishTask($task['id']);
            } else {
                return false;
            }
        }
        return true;
    }

    private function runTask($task_id, string $output = null, $result = 0)
    {
        if (empty($task)) {
            switch ($this->driver) {
                case 'file':
                    $result = $this->runTaskFromFile($task_id);
                    break;
                case 'redis':
                    $result = $this->runTaskFromRedis($task_id);
                    break;
                case 'mysql':
                    $result = $this->runTaskFromMysql($task_id);
                    break;
                default:
                    $result = false;
            }
            return $result;
        }
    }

    private function finishTask($task_id, string $output = null, $result = 0)
    {
        if (empty($task)) {
            switch ($this->driver) {
                case 'file':
                    $result = $this->finishTaskFromFile($task_id, $output, $result);
                    break;
                case 'redis':
                    $result = $this->finishTaskFromRedis($task_id, $output, $result);
                    break;
                case 'mysql':
                    $result = $this->finishTaskFromMysql($task_id, $output, $result);
                    break;
                default:
                    $result = false;
            }
            return $result;
        }
    }

    private function addTaskToRedis(array $task, $is_block = false)
    {
        $incr = self::$handler->hGet($this->redisConfig['storage_key'], 'incr');
        if (! $incr) {
            $incr = 0;
        }
        $tasks = self::$handler->hGet($this->redisConfig['storage_key'], 'tasks');
        if (! $tasks) {
            $tasks = [];
        } else {
            $tasks = json_decode($tasks, true);
            if (! $tasks) {
                return false;
            }
        }
        $task['id'] = ++ $incr;
        $task['is_block'] = $is_block ? 1 : 0;
        $tasks[$incr] = $task;
        if (self::$handler->hSet($this->redisConfig['storage_key'], 'incr', $incr) == 0) {
            return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks)) == 0 ? $incr : false;
        } else {
            return false;
        }
    }

    private function addTaskToMysql(array $task, $is_block = false)
    {
        $result = self::$handler->query("select max(`id`) from `" . $this->mysqlConfig['storage_table'] . "` ;");
        if (! $result) {
            $incr = (int) $result[0];
        } else {
            return false;
        }
        $task['id'] = ++ $incr; // 从1开始计数
        $task['is_block'] = $is_block ? 1 : 0;
        
        $keys = $values = '';
        if (! array_walk($task, function ($value, $key) use (&$keys, &$values) {
            if (! is_null($value)) {
                $keys .= '`' . $key . '`,';
                $values .= is_array($value) ? '\'' . json_encode($value) . '\',' : '\'' . $value . '\',';
            }
        })) {
            return false;
        }
        $insertSql = 'insert into `' . $this->mysqlConfig['storage_table'] . '`(' . rtrim($keys, ',') . ') values (' . rtrim($values, ',') . ');';
        
        if(self::$handler->query($insertSql)){
		    return $task['id'];
		}else{
		    return false;
		}
    }

    private function addTaskToFile(array $task, $is_block = false)
    {
        $content = file_get_contents(self::$handler);
        $newContent = [];
        if (strlen($content) == 0) {
            $task['id'] = $newContent['incr'] = 1; // 从1开始计数
            $task['is_block'] = $is_block ? 1 : 0;
            $newContent['tasks'] = [
                $task['id'] => $task
            ];
            if(file_put_contents(self::$handler, json_encode($newContent))){
			    return $task['id'];
			}else{
			    return false;
			}
        } else {
            $current = json_decode($content, true);
            if (! $current) {
                return false;
            }
            $task['id'] = ++ $current['incr'];
            $task['is_block'] = $is_block ? 1 : 0;
            $current['tasks'][$task['id']] = $task;
			 if(file_put_contents(self::$handler, json_encode($current))){
			    return $task['id'];
			}else{
			    return false;
			}
        }
    }

    private function getTasksFromRedis()
    {
        $incr = self::$handler->hGet($this->redisConfig['storage_key'], 'incr');
        if (! $incr) {
            return [];
        }
        $tasks = self::$handler->hGet($this->redisConfig['storage_key'], 'tasks');
        if (! $tasks) {
            $tasks = [];
        } else {
            return json_decode($tasks, true);
        }
    }

    private function getTasksFromMysql()
    {
        $result = $result = self::$handler->query("select * from `" . $this->mysqlConfig['storage_table'] . "` ;");
        if ($result) {
            if ($result->num_rows) {
                $tasks = [];
                for ($i = 0; $i < $result->num_rows; $i ++) {
                    $task = $result->fetch_assoc();
                    $task['min_range'] = json_decode($task['min_range'], true);
                    $task['hour_range'] = json_decode($task['hour_range'], true);
                    $task['date_range'] = json_decode($task['date_range'], true);
                    $task['month_range'] = json_decode($task['month_range'], true);
                    $task['day_range'] = json_decode($task['day_range'], true);
                    $tasks[$task['id']] = $task;
                }
                return $tasks;
            } else {
                return [];
            }
        } else {
            return false;
        }
    }

    private function getTasksFromFile()
    {
        $content = file_get_contents(self::$handler);
        if (! $content) {
            return [];
        }
        $content = json_decode($content, true);
        if (! $content) {
            return [];
        }
        return $content['tasks'];
    }

    private function runTaskFromRedis($task_id)
    {
        $tasks = $this->getTasksFromRedis();
        $task = $tasks[$task_id];
        $task['last_time'] = time();
        $task['running'] = 1;
        $task['times'] = $task['times'] + 1;
        $tasks[$task_id] = $task;
        return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks));
    }

    private function runTaskFromMysql($task_id)
    {
        $sql = "update `" . $this->mysqlConfig['storage_table'] . "` set `last_time`=" . time() . ", `running`=1  ,`times`= `times` + 1  `where `id`=" . $task_id . "limit 1;";
        return self::$handler->query($sql);
    }

    private function runTaskFromFile($task_id)
    {
        $content = file_get_contents(self::$handler);
        $current = json_decode($content, true);
        if (! $current) {
            return false;
        }
        $task = $current['tasks'][$task_id];
        $task['last_time'] = time();
        $task['running'] = 1;
        $task['times'] = $task['times'] + 1;
        $current['tasks'][$task_id] = $task;
        return file_put_contents(self::$handler, json_encode($current));
    }

    private function finishTaskFromRedis($task_id, $output = null, $result = 1)
    {
        $tasks = $this->getTasksFromRedis();
        $task = $tasks[$task_id];
        $task['output'] = $output;
        $task['result'] = $result;
        $task['running'] = 0;
        $tasks[$task_id] = $task;
        return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks));
    }

    private function finishTaskFromMysql($task_id, $output = null, $result = 1)
    {
        if (is_null($output)) {
            $sql = "update `" . $this->mysqlConfig['storage_table'] . "` set `running`=0, `result`=" . $result . " where `id`=" . $task_id . "limit 1;";
        } else {
            $sql = "update `" . $this->mysqlConfig['storage_table'] . "` set `running`=0, `result`=" . $result . ", `output`='" . $output . "' where `id`=" . $task_id . "limit 1;";
        }
        return self::$handler->query($sql);
    }

    private function finishTaskFromFile($task_id, $output = null, $result = 1)
    {
        $content = file_get_contents(self::$handler);
        $current = json_decode($content, true);
        if (! $current) {
            return false;
        }
        $task = $current['tasks'][$task_id];
        $task['output'] = $output;
        $task['result'] = $result;
        $task['running'] = 0;
        $current['tasks'][$task_id] = $task;
        return file_put_contents(self::$handler, json_encode($current));
    }

    private function dropTaskFromRedis($task_id)
    {
        $tasks = $this->getTasksFromRedis();
        $task = $tasks[$task_id];
        $task['status'] = 0;
        $tasks[$task_id] = $task;
        return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks));
    }

    private function dropTaskFromMysql($task_id)
    {
        return self::$handler->query("update `" . $this->mysqlConfig['storage_table'] . "` set `status`=0 where `id`=" . $task_id . "limit 1;");
    }

    private function dropTaskFromFile($task_id)
    {
        $content = file_get_contents(self::$handler);
        $current = json_decode($content, true);
        if (! $current) {
            return false;
        }
        $task = $current['tasks'][$task_id];
        $task['status'] = 0;
        $current['tasks'][$task_id] = $task;
        return file_put_contents(self::$handler, json_encode($current));
    }

    private function dropTasksFromRedis()
    {
        $tasks = $this->getTasksFromRedis();
        if (! $tasks || empty($tasks)) {
            return false;
        } else {
            foreach ($tasks as $k => $v) {
                $tasks[$k]['status'] = 0;
            }
        }
        return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks));
    }

    private function dropTasksFromMysql()
    {
        return self::$handler->query("update `" . $this->mysqlConfig['storage_table'] . "` set `status`=0;");
    }

    private function reuseTaskFromFile($task_id)
    {
        $content = file_get_contents(self::$handler);
        $current = json_decode($content, true);
        if (! $current) {
            return false;
        }
        $task = $current['tasks'][$task_id];
        $task['status'] = 1;
        $current['tasks'][$task_id] = $task;
        return file_put_contents(self::$handler, json_encode($current));
    }

    private function reuseTaskFromRedis($task_id)
    {
        $tasks = $this->getTasksFromRedis();
        $task = $tasks[$task_id];
        $task['status'] = 0;
        $tasks[$task_id] = $task;
        return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks));
    }

    private function reuseTaskFromMysql($task_id)
    {
        return self::$handler->query("update `" . $this->mysqlConfig['storage_table'] . "` set `status`=0 where `id`=" . $task_id . "limit 1;");
    }

    private function reuseTasksFromRedis()
    {
        $tasks = $this->getTasksFromRedis();
        if (! $tasks || empty($tasks)) {
            return false;
        } else {
            foreach ($tasks as $k => $v) {
                $tasks[$k]['status'] = 1;
            }
        }
        return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks));
    }

    private function reuseTasksFromMysql()
    {
        return self::$handler->query("update `" . $this->mysqlConfig['storage_table'] . "` set `status`=1;");
    }

    private function dropTasksFromFile()
    {
        $content = file_get_contents(self::$handler);
        $current = json_decode($content, true);
        
        if (! $current) {
            return false;
        }
        
        $tasks = $current['tasks'];
        if (! $tasks || empty($tasks)) {
            return false;
        } else {
            foreach ($tasks as $k => $v) {
                $tasks[$k]['status'] = 0;
            }
        }
        $current['tasks'] = $tasks;
        return file_put_contents(self::$handler, json_encode($current));
    }

    private function destoryTaskFromRedis($task_id)
    {
        $tasks = $this->getTasksFromRedis();
        unset($tasks[$task_id]);
        return self::$handler->hSet($this->redisConfig['storage_key'], 'tasks', json_encode($tasks));
    }

    private function destoryTaskFromMysql($task_id)
    {
        return self::$handler->query("delete from `" . $this->mysqlConfig['storage_table'] . " where `id`=" . $task_id . "limit 1;");
    }

    private function destoryTaskFromFile($task_id)
    {
        $content = file_get_contents(self::$handler);
        $current = json_decode($content, true);
        if (! $current) {
            return false;
        }
        $tasks = $current['tasks'];
        unset($tasks[$task_id]);
        $current['tasks'] = $tasks;
        return file_put_contents(self::$handler, json_encode($current));
    }

    private function destoryTasksFromRedis()
    {
        if (self::$handler->hDel($this->redisConfig['storage_key'], 'tasks')) {
            return self::$handler->hDel($this->redisConfig['storage_key'], 'incr');
        } else {
            return false;
        }
    }

    private function destoryTasksFromMysql()
    {
        return self::$handler->query("truncate table `" . $this->mysqlConfig['storage_table'] . "` ;");
    }

    private function destoryTasksFromFile()
    {
        return fclose(fopen(self::$handler, 'w'));
    }

    private function initialTable()
    {
        $createsql = "
            CREATE TABLE `wl_tasks` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `command` varchar(255) NOT NULL COMMENT '命令',
            `times` int(11) unsigned NOT NULL DEFAULT 0  COMMENT '执行次数',
            `min` varchar(4) COMMENT '分钟',
            `hour` varchar(4) COMMENT '小时',
            `date` varchar(4) COMMENT '日期',
            `month` varchar(4) COMMENT '月',
            `day` varchar(4) COMMENT '星期',
            `min_range` varchar(255) COMMENT '分钟范围',
            `hour_range` varchar(255) COMMENT '小时范围',
            `date_range` varchar(255) COMMENT '日期范围',
            `month_range` varchar(255) COMMENT '月范围',
            `day_range` varchar(255) COMMENT '星期范围',
            `create_at` int(11) unsigned NOT NULL COMMENT '命令创建时间',
            `start_at` int(11) unsigned NOT NULL COMMENT '开始时间',
            `last_time` int(11) unsigned DEFAULT NULL COMMENT '上次执行时间',
            `is_block` tinyint(2) unsigned NOT NULL DEFAULT 0 COMMENT '是否需要阻塞',
            `running` tinyint(2) unsigned NOT NULL DEFAULT 0 COMMENT '是否执行中',
            `result` tinyint(2) unsigned DEFAULT NULL COMMENT '执行结果',
            `output` text DEFAULT NULL COMMENT '输出内容',
            `note` text DEFAULT NULL COMMENT '备注信息',
            `status` tinyint(2) unsigned DEFAULT 1 COMMENT '命令状态',
          PRIMARY KEY (`id`),
          KEY `command_start_at_status` (`command`,`start_at`,`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '计划任务表'
     ";
    }
}
