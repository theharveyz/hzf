<?php 
namespace CORE\LIB\ROUTE;
/**
 *	路由分发器
 */

class Dispatcher {
	//拦截器
	static $intercepter = null;
	//路由解析器
	var $route_parser = null;
	
	public static function run(\HZF_Intercepter $intercepter)
	{
		if(is_null(self::$intercepter))
			self::$intercepter = $intercepter;
		self::$intercepter->foo();
	}
}