<?php
/**
 * 帮助函数
 *
 * @author Du Xin <erntoo@126.com>
 * @todo 自定义帮助函数
 * @version
 */
date_default_timezone_set('PRC'); // 设置中国时区

if (! function_exists('random_code')) {

    /**
     * 生成随机数
     *
     * @param number $length            
     * @return number|string
     */
    function number_rand($length = 4)
    {
        $first = rand(0, 9);
        if ($length == 1) {
            return $first;
        }
        return $first . rand(pow(10, $length - 2), pow(10, $length - 1) - 1);
    }
}

if (! function_exists('unicode_encode')) {

    function unicode_encode($name)
    {
        $name = iconv('UTF-8', 'UCS-2', $name);
        $len = strlen($name);
        $str = '';
        for ($i = 0; $i < $len - 1; $i = $i + 2) {
            $c = $name[$i];
            $c2 = $name[$i + 1];
            if (ord($c) > 0) { // 两个字节的文字
                $str .= '\u' . base_convert(ord($c), 10, 16) . base_convert(ord($c2), 10, 16);
            } else {
                $str .= $c2;
            }
        }
        return $str;
    }
}

if (! function_exists('hex2str')) {

    /**
     * 将十六进制转化成字符
     *
     * @param string $hex            
     * @param string $delimiter            
     * @return string
     */
    function hex2str($hex, $delimiter = '')
    {
        $string = '';
        if ($delimiter) {
            $hex = explode($delimiter, $hex);
            $size = count($hex);
            for ($i = 0; $i < $size; $i ++) {
                $string .= chr(hexdec($hex[$i]));
            }
            return $string;
        }
        if (stripos($hex, '0x') === 0 || stripos($hex, '0x')) {
            $hex = substr(strtolower($hex), 2);
            $hex = explode('0x', $hex);
            $size = count($hex);
            for ($i = 0; $i < $size; $i ++) {
                $string .= chr(hexdec($hex[$i]));
            }
        } else {
            $size = strlen($hex);
            for ($i = 0; $i < $size; $i += 2) {
                $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
            }
        }
        
        return $string;
    }
}

if (! function_exists('str2hex')) {

    /**
     * 字符串转十六进制
     *
     * @param string $str            
     * @param boolean $_0x
     *            格式 0: 不带0x; 1: 带0x
     * @param boolean $case
     *            格式 0: 小写; 1 大写
     * @param string $delimiter
     *            连接符 默认为'' 例如 -
     * @return string
     */
    function str2hex($str, $_0x = false, $case = false, $delimiter = '')
    {
        $hex = '';
        $size = strlen($str);
        
        if ($_0x) {
            for ($i = 0; $i < $size; $i ++) {
                $hex .= '0x' . dechex(ord($str[$i])) . $delimiter;
            }
        } else {
            for ($i = 0; $i < $size; $i ++) {
                $hex .= dechex(ord($str[$i])) . $delimiter;
            }
        }
        
        $hex = ($delimiter) ? substr($hex, 0, - strlen($delimiter)) : $hex;
        
        return ($case) ? strtoupper($hex) : $hex;
    }
}

if (! function_exists('cache_set')) {

    /**
     *
     * @param string $key            
     * @param string $value            
     * @param int|\Datetime $expire
     *            有效时长:以分钟为单位,或者有效期至： 默认为0 不过期 ;
     */
    function cache_set($key, $value, $prefix = '')
    {
        if (empty($prefix)) {
            $prefix = config('CACHE_PREFIX');
        }
        $key = $prefix . $key;
        
        $redis = require 'redis.php';
        $cache = new \Redis();
        $cache->connect($redis['host'], $redis['port']);
        $cache->auth($redis['pass']);
        
        $cache->set($key, $value);
        return true;
    }
}

if (! function_exists('cache_get')) {

    /**
     * 获取值
     *
     * @param string $key            
     * @param string $prefix            
     * @return boolean
     */
    function cache_get($key, $prefix = '')
    {
        if (empty($prefix)) {
            $prefix = config('CACHE_PREFIX');
        }
        $key = $prefix . $key;
        
        $redis = require 'redis.php';
        $cache = new \Redis();
        $cache->connect($redis['host'], $redis['port']);
        $cache->auth($redis['pass']);
        
        return $cache->get($key);
    }
}

if (! function_exists('cache_flush')) {

    /**
     * 注意安全!!!!!!!
     * 会清除所有cache
     *
     * @param string $key            
     * @param
     *            boolean 清空所有 cache
     */
    function cache_flush()
    {
        return (bool) \Redis::flush();
    }
}

if (! function_exists('cache_has')) {

    /**
     *
     * @param string $key            
     * @param string $prefix            
     * @return boolean
     */
    function cache_has($key, $prefix = '')
    {
        if (empty($prefix)) {
            $prefix = config('CACHE_PREFIX');
        }
        $key = $prefix . $key;
        return (bool) \Redis::has($key);
    }
}

if (! function_exists('cache_unset')) {

    /**
     * 根据key清除缓存
     *
     * @param string $key            
     * @return boolean
     */
    function cache_unset($key, $prefix)
    {
        if (empty($prefix)) {
            $prefix = config('CACHE_PREFIX');
        }
        $key = $prefix . $key;
        
        $redis = require 'redis.php';
        $cache = new \Redis();
        $cache->connect($redis['host'], $redis['port']);
        $cache->auth($redis['pass']);
        
        return $cache->del($key);
    }
}

if (! function_exists('gbk2utf8')) {

    /**
     * gbk 转 utf-8
     *
     * @param unknown $gbk_string            
     * @return string
     */
    function gbk2utf8($gbk_string)
    {
        return iconv("GBK", "UTF-8", $gbk_string);
    }
}

if (! function_exists('utf82gbk')) {

    /**
     * utf-8 转gbk
     *
     * @param unknown $utf8_string            
     * @return string
     */
    function utf82gbk($utf8_string)
    {
        return iconv("UTF-8", "GBK//IGNORE", $utf8_string);
    }
}

if (! function_exists('is_ipv4')) {

    /**
     *
     * @param string $ipv4            
     * @return boolean
     */
    function is_ipv4($ipv4)
    {
        return (bool) filter_var($ipv4, FILTER_VALIDATE_IP);
    }
}
if (! function_exists('is_email')) {

    /**
     * 验证email 是否合法
     *
     * @param unknown $email            
     * @return boolean
     */
    function is_email($email)
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
if (! function_exists('is_url')) {

    /**
     * 验证url是否合法
     *
     * @param string $url            
     * @return boolean
     */
    function is_url($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
}
if (! function_exists('is_phone')) {

    /**
     * 验证手机号是否合法
     *
     * @see configs/const.php
     * @param string $phone            
     * @return boolean
     */
    function is_phone($phone)
    {
        return (bool) preg_match(config('const.PHONE'), trim($phone));
    }
}
if (! function_exists('is_realname')) {

    /**
     * 验证中文姓名是否合法
     *
     * @see config/const.php
     * @param string $realname            
     * @return boolean
     */
    function is_realname($realname)
    {
        return (bool) preg_match(config('const.REALNAME'), trim($realname));
    }
}
if (! function_exists('is_nickname')) {

    /**
     * 验证昵称是否合法
     *
     * @see config/const.php
     * @param string $nickname            
     * @return boolean
     */
    function is_nickname($nickname)
    {
        return (bool) preg_match(config('const.NICKNAME'), trim($nickname));
    }
}
if (! function_exists('is_account')) {

    /**
     * 验证帐号是否合法
     *
     * @see config/const.php
     * @param string $account            
     * @return boolean
     */
    function is_account($account)
    {
        return (bool) preg_match(config('const.ACCOUNT'), trim($account));
    }
}
if (! function_exists('is_idcard')) {

    /**
     * 验证身份证是否合法
     *
     * @see config/const.php
     * @param string $idcard            
     * @return boolean
     */
    function is_idcard($idcard)
    {
        return (bool) preg_match(config('const.IDCARD'), trim($idcard));
    }
}

if (! function_exists('id_number')) {

    /**
     * 创建一个整型ID
     * WOKR_ID 在.env 文件中定义 0-99
     *
     * @return string
     */
    function id_number()
    {
        try {
            $sn = sprintf("%d", microtime(true) * 10000);
            $mark = config('APP_KEY', 'lastid') . '_lastid';
            if (\Redis::exists($mark)) {
                if ($sn <= \Redis::exists($mark)) {
                    \Redis::increment($mark);
                    $sn = \Redis::get($mark);
                }
            }
            \Redis::set($mark, $sn);
            return $sn . sprintf("%02d", config('WORK_ID', '99'));
        } catch (\Exception $e) {
            return sprintf("%d", microtime(true) * 10000) . sprintf("%02d", config('WORK_ID', '99'));
        }
    }
}

if (! function_exists('trade_no')) {

    /**
     * 创建一个交易流水号 年月日时分秒开头
     * WOKR_ID 在.env 文件中定义 0-99
     *
     * @param string $prefix
     *            前缀
     * @return string
     */
    function trade_no($prefix = '')
    {
        try {
            list ($second, $microsecond) = explode('.', microtime(true));
            $tradeNO = date("YmdHis", $second) . $microsecond;
            $mark = 'get_last_trade_no';
            if (\Redis::has($mark)) {
                if ($tradeNO <= \Redis::has($mark)::get($mark)) {
                    \Redis::increment($mark);
                    $tradeNO = \Redis::get($mark);
                }
            }
            \Redis::set($mark, $tradeNO);
            return $prefix . $tradeNO . sprintf("%02d", config('WORK_ID', '99'));
        } catch (\Exception $e) {
            list ($second, $microsecond) = explode('.', microtime(true));
            $tradeNO = date("YmdHis", $second) . $microsecond;
            return $prefix . $tradeNO . sprintf("%02d", config('WORK_ID', '99'));
        }
    }
}

if (! function_exists('trade_number')) {

    /**
     * trade_no 别名
     * 创建一个交易流水号 年月日时分秒开头
     * WOKR_ID 在.env 文件中定义 0-99
     *
     * @param string $prefix
     *            前缀
     * @return string
     */
    function trade_number($prefix = '')
    {
        return trade_no($prefix);
    }
}

if (! function_exists('card_rand')) {

    /**
     * 根据当前无重复的$codes，补充生成长度为$length,总数为$amount的
     *
     * @param int $length
     *            长度
     * @param int $amount
     *            需要总数
     * @param string $prefix
     *            前缀
     * @param array $codes
     *            初始codes
     * @return array $codes 无重复的codes,含输入参数
     */
    function card_rand($length, $amount = 1, $prefix = '', $codes = [])
    {
        $seed = [
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'J',
            'K',
            'L',
            'M',
            'N',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z'
        ];
        $codes_tmp = [];
        $need = $amount - count($codes);
        for ($c = 0; $c < $need; $c ++) {
            $code = '';
            for ($i = 0; $i < $length; $i ++) {
                $index = rand(0, 31);
                $code .= $seed[$index];
            }
            $codes_tmp[] = $prefix . $code;
        }
        $codes = array_unique(array_merge($codes, $codes_tmp));
        unset($codes_tmp);
        $number = count($codes);
        
        $d = $amount - $number;
        if ($d > 0) {
            $codes = array_unique(array_merge($codes, card_rand($length, $amount, $prefix, $codes)));
        }
        return $codes;
    }
}

if (! function_exists('json')) {

    function json($code, $data = [], $message = null)
    {
        header("Content-Type:application/json");
        $response = [
            'code' => $code,
            'message' => $message ?? lang($code)
        ];
        if ($code == 0) {
            if (empty($data))
                $response['data'] = null;
            else if (preg_match('/^\[/', json_encode($data))) {
                $response['data']['data'] = $data;
            } else {
                $response['data'] = $data;
            }
        }
        return json_encode($response);
    }
}

if (! function_exists('config')) {

    function config($key = null, $default = null)
    {
        $config = require 'config.php';
        if (empty($key)) {
            return $config;
        }
        $keys = explode('.', $key);
        
        $deepth = count($keys);
        
        switch ($deepth) {
            case 1:
                return $config[$key] ?? $default;
                break;
            case 2:
                return $config[$keys[0]][$keys[1]] ?? $default;
                break;
            default:
                return $config[$keys[0]][$keys[1]][$keys[2]] ?? $default;
                break;
        }
    }
}

if (! function_exists('get')) {

    function get($key = null, $default = '')
    {
        if (empty($key)) {
            return $_GET ?? [];
        } else {
            return $_GET[$key] ?? $default;
        }
    }
}

if (! function_exists('post')) {

    function post($key = null, $default = '')
    {
        if (empty($key)) {
            return $_POST ?? [];
        } else {
            return $_POST[$key] ?? $default;
        }
    }
}

if (! function_exists('request')) {

    function request($key = null, $default = '')
    {
        if (empty($key)) {
            return $_REQUEST ?? [];
        } else {
            return $_REQUEST[$key] ?? $default;
        }
    }
}

if (! function_exists('lang')) {

    function lang($key = null)
    {
        $lang = require 'Lang.' . local() . '.php';
        $keys = explode('.', $key);
        
        $deepth = count($keys);
        
        switch ($deepth) {
            case 1:
                return $lang[$key] ?? null;
                break;
            case 2:
                return $lang[$keys[0]][$keys[1]] ?? null;
                break;
            default:
                return $lang[$keys[0]][$keys[1]][$keys[2]] ?? null;
                break;
        }
    }
}

if (! function_exists('local')) {

    function local($key = null)
    {
        if (! empty($key)) {
            return $key;
        } else {
            return config('LOCAL') ?? 'zh_CN';
        }
    }
}
if (! function_exists('underline2camel')) {

    /**
     * 下划线转驼峰
     * @param string $string
     * @param bool $ucfirst 
     * @return string|unknown
     */
    function underline2camel($string, $ucfirst = true)
    {
        $return = preg_replace_callback('/_+([a-z0-9])/i', function ($matches) {
            return strtoupper($matches[1]);
        }, $string);
        return ($ucfirst) ? ucfirst($return) : $return;
    }
}

if (! function_exists('camel2underline')) {

    /**
     * 驼峰转下划线
     * @param string $string
     * @param string $first
     * @return mixed
     */
    function camel2underline($string, $first = false)
    {
        $return = preg_replace_callback('/[A-Z]/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $string);
        return ($first) ? $return : substr($return, 1);
    }
}

if (! function_exists('pluralize')) {

    /**
     * 单数转复数
     *
     * @param string $string            
     * @return bool|string|mixed
     */
    function pluralize($string)
    {
        $plural = [
            [
                '/(quiz)$/i',
                "$1zes"
            ],
            [
                '/^(ox)$/i',
                "$1en"
            ],
            [
                '/([m|l])ouse$/i',
                "$1ice"
            ],
            [
                '/(matr|vert|ind)ix|ex$/i',
                "$1ices"
            ],
            [
                '/(x|ch|ss|sh)$/i',
                "$1es"
            ],
            [
                '/([^aeiouy]|qu)y$/i',
                "$1ies"
            ],
            [
                '/([^aeiouy]|qu)ies$/i',
                "$1y"
            ],
            [
                '/(hive)$/i',
                "$1s"
            ],
            [
                '/(?:([^f])fe|([lr])f)$/i',
                "$1$2ves"
            ],
            [
                '/sis$/i',
                "ses"
            ],
            [
                '/([ti])um$/i',
                "$1a"
            ],
            [
                '/(buffal|tomat)o$/i',
                "$1oes"
            ],
            [
                '/(bu)s$/i',
                "$1ses"
            ],
            [
                '/(alias|status)$/i',
                "$1es"
            ],
            [
                '/(octop|vir)us$/i',
                "$1i"
            ],
            [
                '/(ax|test)is$/i',
                "$1es"
            ],
            [
                '/s$/i',
                "s"
            ],
            [
                '/$/',
                "s"
            ]
        ];
        
        $singular = [
            [
                "/s$/",
                ""
            ],
            [
                "/(n)ews$/",
                "$1ews"
            ],
            [
                "/([ti])a$/",
                "$1um"
            ],
            [
                "/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/",
                "$1$2sis"
            ],
            [
                "/(^analy)ses$/",
                "$1sis"
            ],
            [
                "/([^f])ves$/",
                "$1fe"
            ],
            [
                "/(hive)s$/",
                "$1"
            ],
            [
                "/(tive)s$/",
                "$1"
            ],
            [
                "/([lr])ves$/",
                "$1f"
            ],
            [
                "/([^aeiouy]|qu)ies$/",
                "$1y"
            ],
            [
                "/(s)eries$/",
                "$1eries"
            ],
            [
                "/(m)ovies$/",
                "$1ovie"
            ],
            [
                "/(x|ch|ss|sh)es$/",
                "$1"
            ],
            [
                "/([m|l])ice$/",
                "$1ouse"
            ],
            [
                "/(bus)es$/",
                "$1"
            ],
            [
                "/(o)es$/",
                "$1"
            ],
            [
                "/(shoe)s$/",
                "$1"
            ],
            [
                "/(cris|ax|test)es$/",
                "$1is"
            ],
            [
                "/([octop|vir])i$/",
                "$1us"
            ],
            [
                "/(alias|status)es$/",
                "$1"
            ],
            [
                "/^(ox)en/",
                "$1"
            ],
            [
                "/(vert|ind)ices$/",
                "$1ex"
            ],
            [
                "/(matr)ices$/",
                "$1ix"
            ],
            [
                "/(quiz)zes$/",
                "$1"
            ]
        ];
        
        $irregular = [
            [
                'move',
                'moves'
            ],
            [
                'sex',
                'sexes'
            ],
            [
                'child',
                'children'
            ],
            [
                'man',
                'men'
            ],
            [
                'person',
                'people'
            ]
        ];
        
        $uncountable = [
            'sheep',
            'fish',
            'series',
            'species',
            'money',
            'rice',
            'information',
            'equipment'
        ];
        
        if (in_array(strtolower($string), $uncountable))
            return $string;
        foreach ($irregular as $noun) {
            if (strtolower($string) == $noun[0])
                return $noun[1];
        }
        
        foreach ($plural as $pattern) {
            if (preg_match($pattern[0], $string)) {
                return preg_replace($pattern[0], $pattern[1], $string);
            }
        }
        
        return $string;
    }
}

if (! function_exists('curl_request')) {

    /**
     *
     * @param string $url
     *            请求地址
     * @param array $parameters
     *            请求参数
     * @param string $method
     *            请求方式 POST GET
     * @param array $cookie
     *            发送的cookie
     * @param number $returnCookie            
     * @return string|unknown
     */
    function curl_request($url, $parameters = [], $method = 'get', $cookie = [], $returnCookie = 0)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'WLXS 1.0;Du Xin;');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        // curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        // curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        if ('post' == $method) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
        } else {
            $url .= strpos($url, '?') ? '&' : '?';
            $url .= http_build_query($parameters);
        }
        if ($cookie) {
            $cookies = http_build_query($cookie);
            $cookies = str_replace('=', ';', $cookies) . ';';
            curl_setopt($curl, CURLOPT_COOKIE, $cookies);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if ($returnCookie) {
            list ($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie'] = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        } else {
            return $data;
        }
    }
}

if (! function_exists('curl_post')) {

    function curl_post($url, $parameters = [])
    {
        return curl_request($url, $parameters, $method = 'post');
        // $client->url .= strpos($client->url, '?') ? '&' : '?';
        // $client->url .= $parameters_string;
    }
}

if (! function_exists('fsock')) {

    function fg_post($url, $data)
    {
        $data = http_build_query($data);
        // $data = json_encode($data);
        $json = file_get_contents($url, 0, stream_context_create(array(
            'http' => array(
                'timeout' => 30,
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded ",
                'content' => $data
            )
        )));
    }
}

if (! function_exists('curl_get')) {

    function curl_get($url, $parameters = [])
    {
        return curl_request($url, $parameters, $method = 'get');
    }
}

if (! function_exists('redirect')) {

    function redirect($url)
    {
        echo '<script> window.location.href="' . $url . '"</script>';
        exit();
    }
}
if (! function_exists('dt')) {

    function dt($type = 0)
    {
        $time = time();
        switch ($type) {
            case 0:
                return date("Y-m-d H:i:s", $time);
                break;
            case 1:
                return date("Ymdhis", $time);
                break;
            case 2:
                return date("Y-m-d", $time);
                break;
            case 3:
                return date("Ymd", $time);
                break;
            default:
                return false;
                break;
        }
    }
}
if (! function_exists('view')) {

    /**
     *
     * @param string $file            
     * @param array $values            
     */
    function view($file, $values)
    {
        $data = [
            'view' => [
                'get' => get(),
                'post' => post(),
                'session' => $_SESSION ?? [],
                'cookie' => $_COOKIE ?? [],
                'request' => $_REQUEST ?? []
            ],
            'tpl' => $values
        ];
        return Vendors\View::getInstance()->assign($data)->show($file);
    }
}

if (! function_exists('agent')) {

    /**
     *
     * @param string $user_agent            
     * @return unknown
     */
    function agent($user_agent = '')
    {
        if (! $user_agent) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }
        if (strpos($user_agent, 'MicroMessenger')) {
            return 'weixin';
        }
        if (strpos($user_agent, 'AlipayClient')) {
            return 'alipay';
        }
        return 'main';
    }
}

if (! function_exists('getallheaders')) {

    function getallheaders()
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (preg_match('/^HTTP(S)?_(.*)/', $key, $matches)) {
                $k = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $matches[2]))));
                $headers[$k] = $value;
            }
        }
        return $headers;
    }
}
