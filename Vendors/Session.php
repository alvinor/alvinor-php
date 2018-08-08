<?php
namespace Vendors;

class Session extends Structure
{

    const SESSION_STARTED = TRUE;

    const SESSION_NOT_STARTED = FALSE;

    private $sessionState = self::SESSION_NOT_STARTED;

    private static $instance;

    public $session_id;

    private $options = [
        'save_path' => '',
        'name' => 'PHPSESSID',
        'save_handler' => 'files',
        // 'auto_start' => 1,
        'gc_probability' => 1,
        'gc_divisor' => 100,
        'gc_maxlifetime' => 1440,
        'serialize_handler' => 'php',
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => '',
        'cookie_httponly' => '',
        'use_strict_mode' => '',
        'use_cookies' => 1,
        'use_only_cookies' => 1,
        'referer_check' => '',
        'cache_limiter' => 'nocache',
        'cache_expire' => 180,
        'use_trans_sid' => 0,
        'trans_sid_tags' => 'a=href,area=href,frame=src,form=',
        'sid_length' => 32,
        'sid_bits_per_character' => 5,
        'lazy_write' => 1
    ];

    public function __construct()
    {
        if (self::SESSION_NOT_STARTED == $this->sessionState) {
            $this->startSession();
        }
    }

    public static function getInstance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function startSession()
    {
        $this->options = array_merge($this->options, [
            'save_path' => dirname(__DIR__) . '/storage/cache/sessions'
        ], config('session', []));
        $this->sessionState = session_start($this->options);
        $this->session_id = session_id();
    }

    public function __set($name, $value)
    {
        $this->$name = $_SESSION[$name] = $value;
    }

    public function __get($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
    }

    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    public function __unset($name)
    {
        if (isset($this->$name)) :
            unset($this->$name); 
            endif;
        
        unset($_SESSION[$name]);
    }

    public function destroy()
    {
        if ($this->sessionState == self::SESSION_STARTED) {
            $this->sessionState = ! session_destroy();
            unset($_SESSION);
            return ! $this->sessionState;
        }
        return FALSE;
    }

    /**
     * 转换成数组
     *
     * @return array
     */
    public function toArray()
    {
        $arr = (array) $this;
        unset($arr['' . "\0" . 'Vendors\\Session' . "\0" . 'sessionState']);
        unset($arr['' . "\0" . 'Vendors\\Session' . "\0" . 'options']);
        return $arr;
    }
}
