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

class Loader {
	static $DS = '/';
	static $root = null;
	static $class_alias = array();
	static $cache = array(
			'helper' => array(),
			'class'  => array(),
		);
	static $paths = array(
				'app'    => array(
						'class'  => array(),
						'helper' => array(),
					), 
				'core'   => array(
						'class'  => array(),
						'helper' => array(),
					), 
				'publics' => array(
						'class'  => array(),
						'helper' => array(),
					),
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
		$class_name = array_pop($class);
		if(empty($class)) return false;
		if(isset(self::$paths[$type]))
		{
			$path = strtolower(implode(self::$DS, $class)) . self::$DS;
			if(!in_array($path, self::$paths[$type]['class']))
			{
				array_unshift(self::$paths[$type]['class'], $path);
			}
			$file = null;
			foreach(self::$paths[$type]['class'] as $p)
			{
				$file = $p . $class_name . '.php';
				if(self::_load($file))
					break;
			}
			return $file;
		}
		return false;
	}

	//辅助函数自动引入
	public static function loadHelper($helper)
	{
		$helper = explode('\\', $helper);
		$type = strtolower(current($helper));
		$helper_name = array_pop($helper);
		if(empty($helper)) return false;
		if(isset(self::$paths[$type]))
		{
			$path = strtolower(implode(self::$DS, $helper)) . self::$DS;
			if(!in_array($path, self::$paths[$type]['helper']))
			{
				array_unshift(self::$paths[$type]['helper'], $path);
			}
			$file = null;
			foreach(self::$paths[$type]['helper'] as $p)
			{
				$file = $p . strtolower($helper_name) . '.php';
				if(self::_load($file, 'helper'))
					break;
			}
			return $file;
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
		return false;
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
 	public static function registerLoadPaths($folder_type, $file_type, $paths)
 	{
 		if(isset(self::$paths[$folder_type]) && !empty($paths))
 		{
 			$paths = is_array($paths) ? $paths : array($paths);
 			foreach($paths as $p)
 			{
 				if(file_exists(self::$root . $p))
 				{
 					self::$paths[$folder_type][$file_type][] = $p;
 				}
 			}
 			return true;
 		}
 		return false;
 	}

}