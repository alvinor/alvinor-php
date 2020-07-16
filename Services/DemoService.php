<?php
namespace Services;
use Models\DemoModel;
class DemoService extends BaseService
{
    private static $demoModel;
    public function __construct()
    {
        parent::__construct();
    }
    public function age()
    {
        return rand(18, 99);
    }

    public function salary(DemoModel $demoModel, $name)
    {
        return 321;
    }

}
