<?php
$webSocketServer = new swoole_websocket_server("0.0.0.0", 8889);
// 监听WebSocket连接打开事件
$webSocketServer->on('open', function ($webSocketServer, $request) {
    require_once __DIR__ . '/../boot/App.php';
    $app = Boot\App::getInstance();
    $app->init();
    $app->loadResource('helpers');
    $data = $app->run($webSocketServer, $request);
    if ($data == 'FAILED') {
        $webSocketServer->close($request->fd);
    } else {
        return true;
    }
});
// 监听HTTP请求
$webSocketServer->on('Request', function ($request, $respone) use ($webSocketServer) {
    if ($request->server['request_uri'] == '/favicon.ico') {
        $respone->end(null);
    } else {
        require_once __DIR__ . '/../boot/App.php';
        $app = Boot\App::getInstance();
        $app->init();
        $app->loadResource('helpers');
        $data = $app->run($webSocketServer, $request);
        $respone->end($data);
    }
});

// 监听WebSocket消息事件
$webSocketServer->on('message', function ($webSocketServer, $frame) {
    ;
});

// 监听WebSocket连接关闭事件
$webSocketServer->on('close', function ($webSocketServer, $fd) {
    $request = new \stdClass();
    $request->server = [
        'request_uri' => '/ws/close'
    ];
    $request->get = [];
    $request->post = [];
    $request->fd = $fd;
    require_once __DIR__ . '/../boot/App.php';
    $app = Boot\App::getInstance();
    $app->init();
    $app->loadResource('helpers');
    $app->run($webSocketServer, $request);
    $webSocketServer->close($fd);
});
$webSocketServer->set(array(
    'worker_num' => 4,
    'daemonize' => true,
    'backlog' => 128,
));
$webSocketServer->start();

/**
 * 将http转成大写 并保存在redis 中
 *
 * @param Swoole\Object $request            
 */
function tcp2http($request)
{
    $redisConfig = require 'redis.php';
    $redis = new \Redis();
    $redis->connect($redisConfig['host'], $redisConfig['port']);
    $redis->auth($redisConfig['pass']);
    $redis->hset('Swoole', 'header', json_encode(! empty($request->header) ? array_change_key_case($request->header, CASE_UPPER) : []));
    $redis->hset('Swoole', 'server', json_encode(! empty($request->server) ? array_change_key_case($request->server, CASE_UPPER) : []));
    $redis->hset('Swoole', 'cookie', json_encode(! empty($request->cookie) ? $request->cookie : []));
    $redis->hset('Swoole', 'get', json_encode(! empty($request->get) ? $request->get : []));
    $redis->hset('Swoole', 'files', json_encode(! empty($request->files) ? $request->files : []));
    $redis->hset('Swoole', 'post', json_encode(! empty($request->post) ? $request->post : []));
    $redis->hset('Swoole', 'tmpfiles', json_encode(! empty($request->tmpfiles) ? $request->tmpfiles : []));
}
