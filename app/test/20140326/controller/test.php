<?php 
namespace APP\TEST\CONTROLLER;
class Test {
	public function foo($test = '')
	{
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