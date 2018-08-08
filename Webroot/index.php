<?php 
// -*-coding:utf-8;-*-
/**
 * index.php
 * 入口文件
 * 
 * @author xin du
 * @package webroot
 */
require_once __DIR__ . '/../boot/App.php';
$app = Boot\App::getInstance();
$app->init();
$app->loadResource('helpers');
echo $app->run();