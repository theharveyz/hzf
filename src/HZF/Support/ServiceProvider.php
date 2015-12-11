<?php 
/**
 * 服务提供者抽象类
 */
namespace HZF\Support;
use HZF\Contract\HzfObject;
use HZF\Application;

abstract class ServiceProvider extends HzfObject{
	//Application 服务定位器
	protected $app;

	//是否延缓加载服务
	protected $defer = false;

	//监听器：暂时不启用
	protected $listeners = [];

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	abstract public function register ();

	//启动方法
	public function boot()
	{
		return ;
	}

	//启动完成后执行
	public function booted()
	{
		return ;
	}

	/*
	 * 当延缓加载时，提供服务名称，以确定当服务被调用时，才注册服务提供者
	 *	service
	 */
	public $providers = [];

	//是否缓载
	public function isDeferred()
	{
		return $this->defer;
	}

	//当为延迟注册服务时，返回provis
	public function providers()
	{
		return $this->providers;
	}

}