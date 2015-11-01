<?php
/**
 *  0：核心加载类基本功能：
 *        1)，可通过注册文件夹，来拓展加载类的作用范围
 *        2)，可以实现辅助函数的加载
 *        3), 对引入的类或者辅助函数进行缓存，方便查询！
 *    1: 类的命名要求
 *        1) 类必须包含命名空间
 *        2) 对于别名类的引入，配置$class_alias中必须包含该别名所对应的原类名称
 *
 */
namespace HZF\Loader;

use HZF\Contract\HzfObject;

class Loader extends HzfObject
{
    static $root        = null;
    static $class_alias = array();
    static $cache       = array(
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
        if (isset($class_alias[$class])) {
            //重新注册类别名
            class_alias($class_alias[$class], $class);
            //如果该类存在，则再次注册别名，并返回“from original class” ：从原类引入
            if (class_exists($class_alias[$class])) {
                return 'from original class';
            }
            $class = $class_alias[$class];
        }

        $file = strtr($class, "\\", DIRECTORY_SEPARATOR) . ".php";
        //遍历root
        if (!empty(self::$root)) {
            foreach (self::$root as $ns => $path) {
                if (strpos($file, $ns) === 0) {
                    $file = $path . str_replace($ns . DIRECTORY_SEPARATOR, '', $file);
                    if (empty($path)) {
                        throw new \Exception("loader root path is empty, namespace : $ns", 999);
                    }

                    if (self::_load($file)) {
                        return $file;
                    }

                    //抛出异常警告
                }
            }
        }
        throw new \Exception("class not found!", 999);
    }

    //辅助函数引入
    public static function loadHelper($helpers = array(), $folder = '')
    {
        if (empty($helpers)) {
            return null;
        }

        $helpers = is_array($helpers) ? $helpers : array($helpers);
        foreach ($helpers as $helper) {
            $file = $folder . $helper . '.php';
            if (!self::_load($file, 'helper')) {
                throw new \Exception("helper not found!", 999);
            }
        }
        return true;
    }

    //共用自动引入方法
    private static function _load($file, $type = 'class')
    {
        if (file_exists($file)) {
            if (!in_array($file, self::$cache[$type])) {
                self::$cache[$type][] = $file;
                require_once $file;
            }
            return true;
        }
        return false;
    }

    //注册ROOT_PATH
    public static function registerRoot($namespace, $root = '')
    {
        if (empty($namespace) || empty($root)) {
            throw new Exception("register root error", 999);
        }
        self::$root[$namespace] = $root;
        return true;
    }

}
