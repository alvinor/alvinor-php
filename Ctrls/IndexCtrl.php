<?php
namespace Ctrls;

use Models\DemoModel;
use Services\DemoService;
use Vendors\View;

class IndexCtrl extends BaseCtrl
{

    private static $demoService;
    private static $demoModel;

    public function __construct(DemoService $demoService, DemoModel $demoModel)
    {
        self::$demoService = $demoService;

    }


    public function main($name){
        echo  self::$demoService->age().PHP_EOL;
        echo self::$demoService->salary($name).PHP_EOL;
        return view('index.main',['title'=>'home']);
    }
}
