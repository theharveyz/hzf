<?php 
/**
 *	hzf引导文件
 */
if(!defined('HZF_START'))
{
	define('HZF_START', time(true));
}
if(!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

if(!defined('CORE_PATH'))
{
	define('CORE_PATH', __DIR__ . DS);
}

if(!defined('ROOT_PATH'))
{
	define('ROOT_PATH', CORE_PATH . '..' . DS);
}

if(!defined('ROOT_CONF_PATH'))
{
	define('ROOT_CONF_PATH', ROOT_PATH . 'conf' . DS);
}

if(!defined('ROOT_LOG_PATH'))
{
	define('ROOT_LOG_PATH', ROOT_PATH . 'logs' . DS);
}

if(!defined('ROOT_VENDOR_PATH'))
{
	define('ROOT_VENDOR_PATH', ROOT_PATH . 'vendor' . DS);
}

if(!defined('CORE_LIB_PATH'))
{
	define('CORE_LIB_PATH', CORE_PATH . 'lib' . DS);
}

if(!defined('CORE_LOADER_PATH'))
{
	define('CORE_LOADER_PATH', CORE_PATH . 'loader' . DS);
}

class BOOT {
	static $necessary_classes = array(
			CORE_LOADER_PATH . 'loader.php',
		);
	static $necessary_helpers = ['array', 'common', 'error'];

	//应用名
	static $APP_NAME = '';

	//版本号
	static $VN = '';

	/**注册app
	 *	@param $app_name：应用名称
	 *	@param $vn：版本号
	 */
	static function registerApp($app_name, $vn = '')
	{
		self::$APP_NAME = $app_name;
		self::$VN = $vn;
		//注册一个app
		\HZF_Loader::registerApp($app_name, $vn);
	}

	//路由分发
	static function routeDispatcher(array $route_config = array())
	{
		//mvc模式的controller
		$namespace = "APP\\" . strtoupper(self::$APP_NAME) . "\CONTROLLER";
		\HZF_Router::dispatch(\HZF_Intercepter::getInstance(), $namespace, $route_config);
	}

	static function init()
	{
		//引入必要的类
		foreach(self::$necessary_classes as $name)
		{
			include $name;
		}

		//注册自动引入机制
		spl_autoload_register(array('CORE\LOADER\Loader', 'loadClass'));

		//引入核心配置文件
		CORE\LIB\CONFIG\Config::getInstance()->loadConfig(ROOT_CONF_PATH);

		//设置自动引入类别名
		CORE\LOADER\Loader::setClassAlias(CORE\LIB\CONFIG\Config::getInstance()->get('class_alias'));

		//引入辅助文件
		self::load();
	}

	static function load()
	{
		//引入核心函数
		\HZF_Loader::loadHelper(self::$necessary_helpers, 'core/helper/');
	}
}

BOOT::init();
