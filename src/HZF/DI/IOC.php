<?php 
namespace HZF\DI;
use HZF\DI\Contract as ContainerContract;
class IOC implements ArrayAccess,ContainerContract{
	//注册的实例
	protected static $instance = array();

	public function binds($abstract, $instance)
	{

	} 

	public function make($abstract, $params = [])
	{

	}
}