<?php 
namespace Test\Controller;
class Test {
	public function foo($test = '')
	{
		$str = 'asdf\b\c';
		var_dump(strtr($str, "\\", "/"));
		echo $test;
	}

	public function xml()
	{
		echo "xml!";
	}

	// public function __remap($method, $pararms = [])
	// {
	// 	var_dump($method, $pararms);
	// 	foo();
	// }
}