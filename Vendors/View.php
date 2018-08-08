<?php
namespace Vendors;

class View
{

    /**
     * 模板引擎配置
     *
     * @var array
     */
    private $config = [
        'suffix' => '.php', // 模板文件后缀名
        'template_dir' => 'Resources/tpl', // 模板文件所在目录
        'compile_dir' => 'storage/caches',
        'suffix_compiled' => '.php', // 编译后文件后缀
        'is_recache_html' => false, // 是否需要重新编译成静态html文件
        'is_php' => true, // 是否支持php的语法
        'cache_time' => 1 // 缓存时间,单位秒
    ];

    private $p_foreach = '#\{\{foreach \\$([a-zA-Z_\x7f-\xff][\.a-zA-Z0-9_\x7f-\xff]*)\s+as\s+\\$([a-zA-Z_\x7f-\xff][\.a-zA-Z0-9_\x7f-\xff]*)(\s*=>\\$([a-zA-Z_\x7f-\xff][\.a-zA-Z0-9_\x7f-\xff]*))?\}\}#';

    private $p_var = '#\{\{\\$((view|tpl)\.[a-zA-Z0-9_\x7f-\xff^\.]*)*\}\}#';

    private $p_var_foreach = '#\{\{\\$((foreach)\.[a-zA-Z0-9_\x7f-\xff^\.]*)*\}\}#';

    private $p_include_file = '#\{\{include_file=\'(.*?)\'\}\}|\{\{include_file=\"(.*?)\"\}\}#';

    private $p_if = '#\{\{(if|else\s+if|elseif)\s*(.*?)\}\}#';

    private $p_elseif = '#\{\{(else if|elseif) (.*?)\}\}#';

    /**
     * 模板文件
     *
     * @var string
     */
    private $file;

    /**
     * 键值对
     *
     * @var array
     */
    private $valueMap = [];

    private $patten = [
        '#\{\{else\}\}#',
        '#\{\{\/(foreach|if)\}\}#',
        '#\{\{(endforeach|endif|fi)\}\}#'
    ];

    private $translation = [
        '<?php }else {?>',
        '<?php }?>',
        '<?php }?>'
    ];

    private static $instance;

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    public static function getInstance($config = [])
    {
        if (! self::$instance) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * 传值
     *
     * @param array $values            
     * @throws \Exception
     * @return \Vendors\View
     */
    public function assign($values = [])
    {
        if (is_array($values)) {
            $this->valueMap = $values;
        } else {
            throw new \Exception('Array need!');
        }
        return $this;
    }

    /**
     * 带编译缓存的文件
     *
     * @param unknown $file            
     * @throws \Exception
     */
    public function show($file)
    {
        $this->file = $file;
        if (! is_file($this->path())) {
            echo $this->path() . "\n";
            throw new \Exception('Template ' . $file . ' not exists!');
        }
        $compileFile = $this->compilePath() . md5($file) . $this->config['suffix_compiled'];
        $cacheFile = $this->compilePath() . md5($file) . '.html';
        if (! is_file($compileFile) || $this->isRecompile($compileFile)) {
            $content = $this->compile($this->path(), $this->valueMap);
            file_put_contents($compileFile, $content);
            $this->config['is_recache_html'] = true;
            if ($this->isSupportPhp()) {
                extract($this->valueMap, EXTR_OVERWRITE); // 从数组中将变量导入到当前的符号表
            }
        }
        if ($this->isReCacheHtml()) {
            ob_start();
            ob_clean();
            include ($compileFile);
            file_put_contents($cacheFile, ob_get_contents());
            ob_end_flush();
        } else {
            readfile($cacheFile);
        }
    }

    /**
     * 根据缓存时间判断是否需要重新编译
     *
     * @param string $compileFile            
     * @return boolean
     */
    private function isRecompile($compileFile)
    {
        return time() - filemtime($compileFile) > $this->config['cache_time'];
    }

    /**
     * 是否需要重新生成html文件
     *
     * @return string|boolean|number
     */
    private function isReCacheHtml()
    {
        return $this->config['is_recache_html'];
    }

    /**
     * 是否支持PHP
     *
     * @return string|boolean|number
     */
    private function isSupportPhp()
    {
        return $this->config['is_php'];
    }

    /**
     * 模板文件绝对路径
     *
     * @param string $file            
     * @return string
     */
    private function path($file = '')
    {
        if (! $file) {
            $file = $this->file;
        }
        return dirname(__DIR__) . '/' . $this->config['template_dir'] . '/' . $file . $this->config['suffix'];
    }

    /**
     * 编译文件的路径
     *
     * @param string $file            
     * @return string
     */
    private function compilePath()
    {
        return realpath(__DIR__ . '../../' . $this->config['compile_dir']) . '/';
    }

    /**
     *
     * @param string $source            
     * @param array $values            
     * @return string
     */
    private function compile($source, $values)
    {
        $content = file_get_contents($source);
        $this->valueMap = $values;
        if (strpos($content, '{{') !== false) {
            $my = self::$instance;
            
            // 编译include
            $content = preg_replace_callback($this->p_include_file, function ($m) use ($my, $values) {
                return $my->compile($my->path($m[1]), $values);
            }, $content);
            
            // 编译 fi endif endforeach /if /foreach 等关闭标签
            $content = preg_replace($this->patten, $this->translation, $content);
            
            // 编译foreach
            $foreachValue = false;
            $content = preg_replace_callback($this->p_foreach, function ($m) use ($my, $values, &$foreachValue) {
                $keys = preg_split("/(\.)/", $m[1]);
                $keySet = '';
                foreach ($keys as $k) {
                    $keySet .= "['" . trim($k) . "']";
                }
                $from = "\$this->valueMap" . $keySet;
                $foreachValue = true;
                if (isset($m[4])) {
                    return '<?php foreach (' . $from . ' as $' . $m['2'] . '=>$' . $m['4'] . '){ ?>';
                } else {
                    return '<?php foreach (' . $from . ' as $' . $m['2'] . '){ ?>';
                }
            }, $content);
            if ($foreachValue) {
                // 编译foreach values
                $content = preg_replace_callback($this->p_var_foreach, function ($matches) {
                    $keys = preg_split("/(\.)/", $matches[1]);
                    $keySet = '';
                    $values = '';
                    foreach ($keys as $k => $v) {
                        if ($k == 1) {
                            $value = $v;
                        } else if ($k > 1) {
                            
                            $keySet .= "['" . trim($v) . "']";
                        }
                    }
                    return "<?php echo \$" . $value . $keySet . ";?>";
                }, $content);
            }
            
            // 编译if elseif else
            $content = preg_replace_callback($this->p_if, function ($m) use ($my, $values) {
                $c2 = preg_replace_callback('#\\$((view|tpl)\.[a-zA-Z0-9_\x7f-\xff^\.]*)*#', function ($m2) {
                    $keys = preg_split("/(\.)/", $m2[1]);
                    $keySet = '';
                    foreach ($keys as $k) {
                        $keySet .= "['" . trim($k) . "']";
                    }
                    return "\$this->valueMap" . $keySet;
                }, $m[2]);
                return '<?php ' . $m[1] . $c2 . '{?>';
            }, $content);
            
            // 编译变量
            $content = preg_replace_callback($this->p_var, function ($matches) {
                $keys = preg_split("/(\.)/", $matches[1]);
                $keySet = '';
                foreach ($keys as $k) {
                    $keySet .= "['" . trim($k) . "']";
                }
                return "<?php echo \$this->valueMap" . $keySet . ";?>";
            }, $content);
            
            $isFunction = false;
            
            // 编译函数内变量
            $content = preg_replace_callback('#\\$(view|tpl)\.?(([a-zA-Z0-9_\x7f-\xff^\.]*)*)#', function ($matches) use (&$isFunction) {
                $keys = preg_split("/(\.)/", $matches[2]);
                $keySet = '';
                foreach ($keys as $k) {
                    $keySet .= "['" . trim($k) . "']";
                }
                $isFunction = true;
                return "\$this->valueMap['" . $matches['1'] . "']" . $keySet;
            }, $content);
            if ($isFunction) {
                $content = preg_replace([
                    '#\{\{(.*)\}\}#'
                ], [
                    '<?php echo \\1;?>'
                ], $content);
            }
        }
        return $content;
    }
}
