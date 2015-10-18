<?php 
namespace APP\TEST\CONTROLLER;
class Test {
	public function foo($test = '')
	{
		echo $test;
		echo "This is app test !";
	}

	public function __remap($method, $pararms = [])
	{
		var_dump($method, $pararms);
	}
}