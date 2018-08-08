<?php
namespace Ctrls;

class BaseCtrl
{

    protected $webSockectServer;
    protected $webSockectRequest;

    public function __construct($webSocketServer = null, $request = null)
    {
        if ($request) {
            $_GET = $request->get;
            $_POST = $request->post;
            $this->webSockectServer = $webSocketServer;
            $this->webSockectRequest = $request;
        }
    }

}