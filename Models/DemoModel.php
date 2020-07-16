<?php
namespace Models;

use Vendors\DB;
class DemoModel extends DB
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getSalary($name)
    {
        $tmp = [
          'zhangsan'=>500,
          'lisi'=>300
        ];
        return isset($tmp[$name]) ? $tmp[$name] : rand(600,800);
    }
    
}
