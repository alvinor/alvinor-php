<?php 
// -*-coding:utf-8;-*-
/**
 * index.php
 * 入口文件
 * 
 * @author xin du
 * @package webroot
 */
require_once __DIR__ . '/../boot/Ioc.php';
require_once __DIR__ . '/../boot/App.php';
$resourse = ['helpers'];
Ioc::make('App', 'run', [$resourse]);