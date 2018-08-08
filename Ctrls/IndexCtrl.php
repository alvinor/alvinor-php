<?php
namespace Ctrls;

use Vendors\View;

class IndexCtrl extends LoginCtrl
{

    public function __construct()
    {
        parent::__construct();
        if (! $this->isLogin()) {
            self::$session->requestUri = $_SERVER['REQUEST_URI'];
            return $this->toAuth();
        }
    }
    public function main(){
        return view('index.main',['title'=>'home']);
    }
}
