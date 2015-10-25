<?php 
/**
 *	依赖对象的引用实例
 */
namespace HZF\DI;
class Instance {
	public $class;

	public function __construct($class)
	{
		$this->class = $class;
	}

	public static function of($class)
	{
		return new static($class);
	}

	public static function get($class = null)
	{
		
	}
}