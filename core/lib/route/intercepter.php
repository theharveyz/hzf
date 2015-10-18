<?php 
namespace CORE\LIB\ROUTE;

class Intercepter {
	static $instance = null;
	var $server = [];
	var $uri = '';
	var $segments = [];
	var $isAjax   = false;
	var $method   = '';
	public function __construct()
	{
		//全局server对象
		if(empty($this->server))
			$this->server = $_SERVER;
		//设置URI
		if(empty($this->uri))
			$this->uri = parse_url($this->server['REQUEST_URI'], PHP_URL_PATH);
		//设置段
		if(empty($this->segments))
			$this->segments = $this->fetchSegs($this->uri);

		//是否是Ajax
		if(!$this->isAjax)
			$this->isAjax();
		//请求方式
		if(!$this->method)
			$this->method = $this->server['REQUEST_METHOD'];	
	}

	public function fetchSegs($uri)
	{
		return array_values(array_filter(explode('/', $uri)));
	}

	//是否是Ajax请求
	public function isAjax()
	{
		//XHR
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) 
        {
            $this->isAjax = TRUE;
        }
        return $this->isAjax;
	}

	static function getInstance()
	{
		if(is_null(self::$instance))
			self::$instance = new self;
		return self::$instance;
	}
}