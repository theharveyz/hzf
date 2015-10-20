<?php 
/**
 *	hzf引导文件
 */
namespace HZF;
use HZF\Config\Config as Config;
use HZF\LOADER\Loader as Loader;

if(!defined('HZF_CORE_PATH'))
{
	define('HZF_CORE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

if(!defined('HZF_CORE_LOADER_PATH'))
{
	define('HZF_CORE_LOADER_PATH', HZF_CORE_PATH . 'Loader' . DIRECTORY_SEPARATOR);
}

if(!defined('HZF_CORE_HELPER_PATH'))
{
	define('HZF_CORE_HELPER_PATH', HZF_CORE_PATH . 'Helpers' . DIRECTORY_SEPARATOR);
}


class Boot {
	var $necessary_classes = array(
			HZF_CORE_LOADER_PATH . 'Loader.php',
		);
	var $necessary_helpers = ['array', 'common', 'error'];

	//应用名
	var $APP_NAME = '';

	//版本号
	var $VN = '';

	//框架默认命名
	var $namespace = 'HZF';

	public function __construct()
	{
	}

	/**注册app
	 *	@param $app_name：应用名称
	 *	@param $vn：版本号
	 */
	public function registerApp($app_name, $vn = '')
	{
		$this->APP_NAME = $app_name;
		$this->VN = $vn;
		//注册一个app
		\HZF_Loader::registerRoot($app_name, $vn);
		return $this;
	}

	//路由分发
	public function routeDispatcher(array $route_config = array())
	{
		//mvc模式的controller
		\HZF_Router::dispatch(\HZF_Intercepter::getInstance(), $route_config);
		return $this;
	}

	public function init($core_conf_folder)
	{
		//引入必要的类
		foreach($this->necessary_classes as $name)
		{
			include $name;
		}
		//注册框架root路径
		Loader::registerRoot($this->namespace, HZF_CORE_PATH);

		//注册自动引入机制
		spl_autoload_register(array('HZF\Loader\Loader', 'loadClass'));
		//引入核心配置文件
		Config::getInstance()->loadConfig($core_conf_folder);

		//设置自动引入类别名
		Loader::setClassAlias(Config::getInstance()->get('class_alias'));
		//引入辅助文件
		$this->load();
		return $this;
	}

	private function load()
	{
		//引入核心函数
		\HZF_Loader::loadHelper($this->necessary_helpers, HZF_CORE_HELPER_PATH);
	}

}
