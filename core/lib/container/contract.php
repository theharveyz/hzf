<?php 
/**
 *	容器接口
 */
interface Contract {
	public function binds($abstract, $instance);

	public function make($abstract, $params);
}