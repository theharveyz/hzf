<?php 
namespace CORE\LIB\ROUTE;
/**
 *	路由分发器:
 *		1, 根据路由规则实现分发
 *		2, 路由器工作时必须注入一个拦截器实体
 */

class Router {
	//拦截器
	static $intercepter = null;
	//路由解析器
	static $route_parser = null;
	//路由空间
	static $namespace = '';
	//路由规则
	/**
	 *	'/a/b' => ['GET' => '', POST => ''] 或者
	 *	'/a/b' => 'xxx@xxx' //指每个请求方式都按照这个规则来
	 *	/a/{:num} => func($num){} or a@__construct($num) or a@b($num)
	 */
	static $route_rules = [];

	//pattern
	static $patterns = [
			'{'    => '(',
			'}'    => ')',
			':any' => '[^/]+',
			':num' => '[0-9]+',
			':all' => '.*',
		];

	//分发
	public static function dispatch(\HZF_Intercepter $intercepter, $namespace, array $route_rules = array())
	{
		self::$namespace = $namespace;
		if(is_null(self::$intercepter))
			self::$intercepter = $intercepter;
		self::$route_rules = array_merge(self::$route_rules, $route_rules);

		$params = [];
		$rule = '';

		$route_rules = empty(self::$route_rules) ? array('/' => 'index@index') : self::$route_rules;

		//遍历规则
		$pattern_key_words = array_keys(static::$patterns);
		$patterns_regxs = array_values(static::$patterns);

		foreach(self::$route_rules as $uri => $handle)
		{
			$uri = str_replace($pattern_key_words, $patterns_regxs, $uri);
			if($uri == self::$intercepter->uri)
			{
				$rule = $handle;
				break;
			}
			else
			{
				$uri = '#^' . $uri . '$#';
				preg_match($uri, self::$intercepter->uri, $matches);
				$matches = is_array($matches) ? array_filter($matches) : $matches;
				if(!empty($matches))
				{
					$rule = $handle;
					$params = array_slice($matches, 1);
				}
			}
		}
		
		$method = strtolower(self::$intercepter->method);
		if(is_array($rule))
			$rule = isset($rule[$method]) ? $rule[$method] : '';
		$class = $action = '';
		//处理匹配结果
		if(!empty($rule))
		{
			//闭包的情况
			if(is_callable($rule))
			{
				return self::exe($rule, $params);
			}
			else
			{
				$segments = explode('@', $rule);
				$class  = $segments[0];
				$action = isset($segments[1]) ? $segments[1] : 'index';
			}
		}
		else
		{
			$segments = self::$intercepter->segments;
			switch(count($segments)){
				case 0 :
					$class  = $action = 'index';
					break;
				case 1 :
				case 2 :
					$class  = $segments[0];
					$action = isset($segments[1]) ? $segments[1] : 'index';
					break;
				default :
					$class  = $segments[0];
					$action = $segments[1];
					$params = array_slice($segments, 2);

			}
		}
		try {
			$controller = self::$namespace . '\\' . ucfirst($class);
			$controller = new $controller;
			if(method_exists($controller, '__remap'))
			{
				return $controller->__remap($action, $params);
			}
			else
			{
				if(method_exists($controller, $action))
				{
					return self::exe(array($controller, $action), $params);
				}
				self::error();

			}
		} catch (Exception $e) {
			self::error();
		}

	}

	//匹配执行
	private static function exe($callback, $params = array())
	{
		if(empty($params))
			return call_user_func($callback);
		else
			return call_user_func_array($callback, $params);
	}

	//错误处理
	public static function error($callback = '')
	{
		if(is_callable($callback))
		{
			$callback();
		}
		//方法调用
		else if(is_array($callback))
		{
			call_user_func($callback);
		}
		else
		{
			header($_SERVER['SERVER_PROTOCOL'] . " 404 NOT FOUND");
			echo "╮(╯_╰)╭ <br><br><br>404 NOT FOUND!" . (empty($callback) ? '' : " [ERR INFO: $callback]");
			exit;	
		}
	}

	//路由方法重载
	public static function __callStatic($method, $params = '')
	{
		$method = strtoupper($method);
		if(empty($params)) return false;
		$route = $params[0];
		//空路由，即该路由执行操作为空时，什么操作都不进行
		$callback = isset($params[1]) ? $params[1] : '';
		self::$route_rules[$route] = $callback;
	}

	//设置pattern，使其在全局范围有效
	public static function pattern($pattern, $regx)
	{
		self::$patterns[$pattern] = $regx;
	}

	//设置某个路由的所有规则
	public static function group($route, $rule)
	{
		self::$route_rules[$route] = $rule;
	}

	//注册路由规则
	public static function setRules($rules = [])
	{
		self::$route_rules = array_merge(self::$route_rules, $rules);
	}
}