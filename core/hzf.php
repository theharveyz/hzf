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
	static $necessary_file_name = array(
			CORE_LIB_PATH . 'config/config.php',
			CORE_LOADER_PATH . 'loader.php',
		);
	static function run()
	{
		self::init();
		CORE\LIB\CONFIG\Config::getInstance()->loadConfig(ROOT_CONF_PATH);
		CORE\LOADER\Loader::loadHelper('CORE\HELPER\COMMON');
		var_dump(CORE\LOADER\Loader::$cache);
		// CORE\HELPER\COMMON\test();
		CORE\HELPER\COMMON\test();
		CORE\LOADER\Loader::registerLoadPaths('app', 'helper', 'app/test/20140326/helper/');
		var_dump(CORE\LOADER\Loader::$paths);
		CORE\LOADER\Loader::loadHelper('APP\TEST\HELPER\APP');
		var_dump(CORE\LOADER\Loader::$cache);
		APP\TEST\HELPER\APP\foo();

	}

	static function init()
	{
		foreach(self::$necessary_file_name as $name)
		{
			include $name;
		}
	}
}

BOOT::run();
