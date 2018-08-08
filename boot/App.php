<?php
/**
 * @package Boot
 * @author Du xin
 */
namespace Boot;

use Ctrls;

class App
{

    private $baseDir;

    private static $instance;

    /**
     * 自动加载目录
     *
     * @var array
     */
    const autoloadPathes = [
        'Config' => 'Configs',
        'Service' => 'Services',
        'Ctrl' => 'Ctrls',
        'Model' => 'Models',
        'Resources',
        'Vendors' => 'Vendors',
        ''
    ];

    public function __construct()
    {
        $this->baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;
    }

    public static function getInstance()
    {
        if (! (self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 初始化函数
     * 当前修改系统路include_path
     */
    public function init()
    {
        $includePathes = get_include_path();
        foreach (self::autoloadPathes as $path) {
            $includePathes .= PATH_SEPARATOR . $this->baseDir . $path;
        }
        set_include_path($includePathes);
    }

    /**
     * 自动加载类
     */
    public static function loadClass()
    {
        $self = new self();
        spl_autoload_register(function ($class_name) use ($self) {
            if (strpos($class_name, '\\')) {
                list ($path, $class) = explode('\\', $class_name);
                $filename = $self->baseDir . $path . DIRECTORY_SEPARATOR . $class . '.php';
                if (file_exists($filename)) {
                    include_once $filename;
                }
            } else {
                if (preg_match_all('/(^[A-Z][a-z0-9]+([A-Z][a-z0-9]+)*)(Service|Ctrl|Model)$/x', $class_name, $match)) {
                    $path = $match[3][0];
                    $class = $match[0][0];
                    $filename = $self->baseDir . $self::autoloadPathes[$path] . DIRECTORY_SEPARATOR . $class_name . '.php';
                    if (file_exists($filename)) {
                        include_once $class_name . '.php';
                    }
                } else {
                    if ($class_name) {
                        include_once $class_name . '.php';
                    }
                }
            }
        });
    }

    /**
     * 把变量转变为安全的变量
     *
     * @param
     *            variable
     * @return void
     */
    private static function strAddSlashes(&$_value)
    {
        if (! empty($_value) && is_array($_value)) {
            foreach ($_value as $_key => $_val) {
                self::strAddSlashes($_value[$_key]);
            }
        } else if (! empty($_value)) {
            $_value = addslashes($_value);
        }
        return;
    }

    /**
     * 默认将POST和GET提交的数据转变为安全变量
     *
     * @param array $_GET
     *            $_POST
     * @return void
     */
    private static function argsAddSlashes()
    {
        if (get_magic_quotes_gpc())
            return;
        self::strAddSlashes($_GET);
        self::strAddSlashes($_POST);
    }

    public static function parseRouter($swooleRequest = null)
    {
        if ($swooleRequest) {
            $url = parse_url($swooleRequest->server['request_uri']);
        } else {
            $url = parse_url($_SERVER['REQUEST_URI']);
        }
        $routes = explode('/', $url['path']);
        $parameters = [];
        empty($routes[1]) ? $class = 'index' : $class = preg_replace('/.php$/', '', $routes[1]);
        
        ! isset($routes[2]) ? $method = 'main' : $method = $routes[2];
        
        $count = count($routes);
        
        if ($count > 3) {
            for ($i = 3; $i < $count; $i ++) {
                $parameters[] = $routes[$i];
            }
        }
        
        return [
            'class' => ucfirst($class) . 'Ctrl',
            'method' => $method,
            'parameters' => $parameters
        ];
    }

    /**
     * 注册加载Resources 目录下文件 区分大小写
     *
     * @param string $filename
     *            文件名 不含 '.php'
     * @return void
     */
    public function loadResource($filename)
    {
        $file = $this->baseDir . 'Resources' . DIRECTORY_SEPARATOR . $filename . '.php';
        if (file_exists($file)) {
            include_once $file;
        }
    }

    /**
     * 系统入口程序
     */
    public function run($webSocketServer = null, $request = null)
    {
        self::loadClass();
        
        self::argsAddSlashes();
        
        $info = self::parseRouter($request);
        $class = 'Ctrls\\' . $info['class'];
        if (method_exists($class, $info['method'])) {
            $return = call_user_func_array([
                new $class($webSocketServer, $request),
                $info['method']
            ], $info['parameters']);
            return $return;
        } else {
            return 'access_deny';
        }
    }
}