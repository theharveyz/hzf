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

if(!defined('CORE_DRIVER_PATH'))
{
	define('CORE_DRIVER_PATH', CORE_PATH . 'driver' . DS);
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
	static $necessary_helpers = array(
			'CORE\HELPER\COMMON',
			'CORE\HELPER\ARRAY',
		);
	static function run()
	{
		/**初始化
		 * 1, 引入核心文件
		 * 2，引入自动载入机制
		 * 3，引入核心配置文件
		 * 4，引入辅助函数
		 */
		self::_init();

		//路由分发
		self::_routeDispatcher();
		
	}

	static private function _routeDispatcher()
	{
		\HZF_Dispatcher::run(new \HZF_Intercepter());
	}

	static private function _init()
	{
		//引入必要的类
		foreach(self::$necessary_classes as $name)
		{
			include $name;
		}

		//注册自动引入机制
		self::_autoLoad();

		//引入核心配置文件
		CORE\LIB\CONFIG\Config::getInstance()->loadConfig(ROOT_CONF_PATH);

		//设置自动引入类别名
		CORE\LOADER\Loader::setClassAlias(CORE\LIB\CONFIG\Config::getInstance()->get('class_alias'));

		//引入核心函数
		call_user_func_array(array('\HZF_Loader', 'loadHelper'), self::$necessary_helpers);
	}

	//注册自动载入机制
	static private function _autoLoad()
	{
		spl_autoload_register(array('CORE\LOADER\Loader', 'loadClass'));
	}
}

BOOT::run();
