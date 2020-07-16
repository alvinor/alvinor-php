<?php
class Ioc
{
    public static function getInstance($className)
    {
        $paramArr = self::getMethodParams($className);
        return (new ReflectionClass($className))->newInstanceArgs($paramArr);
    }
    public static function make($className, $methodName, $params = [])
    {
        $instance = self::getInstance($className);
        $paramArr = self::getMethodParams($className, $methodName);
        return $instance->{$methodName}(...array_merge($paramArr, $params));
    }
    protected static function getMethodParams($className, $methodsName = '__construct')
    {
        $class = new ReflectionClass($className);
        $paramArr = [];
        if($class->hasMethod($methodsName)){
            $construct = $class->getMethod($methodsName);
            $params = $construct->getParameters();
            if(count($params) > 0){
                foreach($params as $key => $param) {
                    if($paramClass = $param->getClass()){
                        $paramClassName = $paramClass->getName();
                        $args = self::getMethodParams($paramClassName);
                        $paramArr[] = (new ReflectionClass($paramClass->getName()))->newInstanceArgs($args);
                    }
                }
            }
        }
        return $paramArr;
    }
}
/*
class A {
    protected $cObj;
    public function __construct(C $c)
    {
        $this->cObj = $c;
    }
    public function aa() {
        echo 'this is A->test';
    }
    public function aac() {
        $this->cObj->cc();
    }
}

class B {
    protected $aObj;
    public function __construct(A $a)
    {
        $this->aObj = $a;
    }
    public function bb(C $c, $b)
    {
        $c->cc();
        echo "\r\n";
        echo 'params:' . $b;
    }
    public function bbb()
    {
        $this->aObj->aac();
    }
}

class C {
    public function cc()
    {
        echo 'this is C->cc';
    }
}

//测试构造函数的依赖注入
// 使用Ioc来创建B类的实例，B的构造函数依赖A类，A的构造函数依赖C类。
$bObj = Ioc::getInstance('B');
$bObj->bbb(); // 输出：this is C->cc ， 说明依赖注入成功。
// 打印$bObj
var_dump($bObj);
//测试方法依赖注入
Ioc::make('B', 'bb', ['this is param b']);*/
