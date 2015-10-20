<?php 
/**
 *	容器接口
 */
namespace HZF\DI;
interface Contract {
	public function binds($abstract, $instance);

	public function make($abstract, $params);
}