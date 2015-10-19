<?php 
namespace CORE\LOADER;
/**
 *  0：核心加载类基本功能：
 *		1)，可通过注册文件夹，来拓展加载类的作用范围
 *		2)，可以实现辅助函数的加载
 *		3), 对引入的类或者辅助函数进行缓存，方便查询！
 *	1: 类的命名要求
 *		1) 类必须包含命名空间
 *		2) 对于别名类的引入，配置$class_alias中必须包含该别名所对应的原类名称
 *
 */	

Final class Loader {
	static $DS = '/';
	static $root = null;
	static $class_alias = array();
	static $cache = array(
			'helper' => array(),
			'class'  => array(),
		);
	static $app_version_nums = array(

			);

	//设置别名类，避免依赖
	public static function setClassAlias(array $alias)
	{
		self::$class_alias = $alias;
	}
	//类自动引入
	public static function loadClass($class)
	{
		//优先从别名判断
		//每次都要初始化一下，防止被污染
		$class_alias = self::$class_alias;

		//如果是别名
		if(isset($class_alias[$class]))
		{
			//重新注册类别名
			class_alias($class_alias[$class], $class);
			//如果该类存在，则再次注册别名，并返回“from original class” ：从原类引入
			if(class_exists($class_alias[$class]))
			{
				return 'from original class';
			}
			$class = $class_alias[$class];
		}


		$class = explode('\\', $class);
		$type  = strtolower(current($class));
		if(empty($class)) return false;
		//针对app单独做处理
		if($type == 'app' && isset($class[1]) && isset(self::$app_version_nums[strtolower($class[1])]))
		{
			$version_num = self::$app_version_nums[strtolower($class[1])];
			$class[1] .= $version_num ? '/' . $version_num : ''; 
		}

		$file = strtolower(implode(self::$DS, $class)) . '.php';
		if(self::_load($file))
				return $file;
		//抛出异常警告
		throw new \Exception("class not found!");
		die(2);
	}

	//辅助函数引入
	public static function loadHelper($helpers = array(), $folder = '')
	{
		if(empty($helpers)) return null;
		$helpers = is_array($helpers) ? $helpers : array($helpers);
		foreach($helpers as $helper)
		{
			$file = $folder . $helper . '.php';
			self::_load($file, 'helper');
		}
	}
	
	//共用自动引入方法
	private static function _load($file, $type = 'class')
	{
		if(is_null(self::$root))
		{
			$return = self::registerRoot();
			if(!$return) return false;
		}
		$file = self::$root . $file;
		if(file_exists($file))
		{
			if(!in_array($file, self::$cache[$type]))
			{
				self::$cache[$type][] = $file;
				require_once $file;
			}
			return true;
		}
		throw new \Exception("helper not found!");
	}

	//注册ROOT_PATH
	public static function registerRoot($root = '')
	{
		if(empty($root))
		{
			if(defined('ROOT_PATH'))
			{
				self::$root = ROOT_PATH;

			}
			return !is_null(self::$root);
		}
		self::$root = $root;
		return true;
 	}

 	//注册自动引入路径
 	public static function registerApp($app_name, $app_version_num = '')
 	{
 		if(empty($app_name)) return false;
 		self::$app_version_nums[$app_name] = $app_version_num;
 		return true;
 	}

 	//注册vendor
 	public static function loadVendor($auto_load_file = '')
 	{
 		return require_once($auto_load_file);
 	}

}