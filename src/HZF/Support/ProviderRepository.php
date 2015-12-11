<?php 

/**
 *	服务提供者仓库，负责服务提供者的管理
 */

namespace HZF\Support;

use HZF\Contract\HzfInterface;
use HZF\Application;

class ProviderRepository implements HzfInterface
{

	//内部成员变量：Application 实例
	protected $app;

	//服务提供者清单
	protected $manifest = [];

	//服务提供者对象缓存
	protected $providers = [];

	public function __construct(Application $app, $manifest = [])
	{
		$this->app = $app;
		$this->manifest = $manifest;
	}

	/**
	 *	这里参考lavarel的设计，删减了事件触发时注册服务提供者
	 */
	public function load(array $providers)
	{
		if(empty($providers))
			return NULL;
		$manifest = $this->compileManifest($providers);

		foreach($manifest['eager'] as $provider) {
			$this->app->registerServiceProvider($provider);
		}

		$this->app->setDeferredServiceProviders($manifest['deferred']);
	}


	/**
	 * 编译providers
	 */
	public function compileManifest($providers)
	{
		$manifest = $this->freshManifest($providers);
		foreach($providers as $provider) {
			$providerInstance = $this->app->getProviderInstance($provider);
			if($providerInstance->isDeferred()){
				//延迟注册，访问
				foreach($providerInstance->providers() as $service) {
					$manifest['deferred'][$service] = $provider;
				}
			}
			else {
				$manifest['eager'][] = $providerInstance;
			}
		}
		return $manifest;
	}

	/**
	 *
	 */
	public function freshManifest($providers)
	{
		return ['providers' => $providers, 'deferred' => [], 'eager' => []];
	}
}