<?php 
namespace CORE\LIB\CONTAINER;
use CORE\LIB\CONTAINER\Contract as ContainerContract;
class IoC implements ArrayAccess,ContainerContract{
	//注册的实例
	protected static $instance = array();

	public function binds($abstract, $instance)
	{

	} 

	public function make($abstract, $params = [])
	{

	}
}