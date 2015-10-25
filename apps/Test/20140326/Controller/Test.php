<?php 
namespace Test\Controller;
class Test {
	public function foo(\HZF\Boot $test)
	{
		$str = 'asdf\b\c';
		var_dump(strtr($str, "\\", "/"));
		echo $test;
	}

	public function xml()
	{
		$reflector = new \ReflectionParameter(array(self::class, 'foo'), 0);
		var_dump($reflector->getClass()->name);
		$func = function(\HZF\Boot $test){

		};
		$reflector = new \ReflectionFunction($func);
		var_dump($reflector->getClass()->name);

		echo "xml!";
	}

	// public function __remap($method, $pararms = [])
	// {
	// 	var_dump($method, $pararms);
	// 	foo();
	// }
}