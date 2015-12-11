<?php 
/**
 * LoaderInterface : 加载器接口
 */
namespace HZF\Contract;
interface LoaderInterface {
	//注册根目录
    public static function registerRoot($namespace, $root = '');

    //加载类
    public static function loadClass($class);
}