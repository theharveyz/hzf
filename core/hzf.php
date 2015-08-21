<?php 
/**
 *	hzf引导文件
 */
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
			CORE_LIB_PATH . 'config/config.php',
			CORE_LOADER_PATH . 'loader.php',
		);
	static $necessary_helpers = array(
			'CORE\HELPER\COMMON',
			'CORE\HELPER\ARRAY',
		);
	static function run()
	{
		//初始化引入核心文件
		self::_init();

		//注册自动引入机制
		self::_autoLoad();
	}

	static private function _autoLoad()
	{
		spl_autoload_register(array('\HZF_Loader', 'loadClass'));
	}

	static private function _init()
	{
		//引入必要的类
		foreach(self::$necessary_classes as $name)
		{
			include $name;
		}
		//引入核心配置文件
		CORE\LIB\CONFIG\Config::getInstance()->loadConfig(ROOT_CONF_PATH);

		//引入核心函数
		call_user_func_array(array('CORE\LOADER\Loader', 'loadHelper'), self::$necessary_helpers);

		//声明类的别名
		$class_alias = CORE\LIB\CONFIG\Config::getInstance()->get("class_alias");
		if(!empty($class_alias))
		{
			foreach($class_alias as $class => $alias)
			{
				class_alias($class, $alias);
			}
		}
	}
}

BOOT::run();
