<?php
namespace Services;
class BaseService
{
    public function __construct()
    {

        $refl = new \ReflectionClass(get_called_class());
        $m = $refl->getMethods();
        var_dump((array)$m);
        \set_error_handler(function($errno, $errstr, $errfile, $errline) {
            echo "<b>Custom error:</b> [$errno] $errstr<br>";
            echo " 错误发生在 on line $errline in $errfile<br>";
        });

        \set_exception_handler(function($e){
            var_dump(get_class_methods($e));
            var_dump($e->getTrace());
        });

        
//        var_dump(\Ioc::getInstance(get_called_class()));
    }

}
