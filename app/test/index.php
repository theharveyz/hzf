<?php 
require_once "../../core/hzf.php";

if(!defined('APP_NAME'))
	define('APP_NAME', 'test');

if(!defined('VERSION_NUM'))
{
	$vn = file_get_contents(__DIR__ . DS . 'VERSION_NUM');
	if($vn)
	{
		define('VERSION_NUM', $vn);
	}
}
//注册一个app
\BOOT::registerApp(APP_NAME, VERSION_NUM);
//加载app配置文件！
\HZF_Config::getInstance()->loadConfig(__DIR__ . DS . 'config' . DS, '*', true);
//路由分发
\BOOT::routeDispatcher(\HZF_Config::getInstance()->get('route'), APP_NAME);