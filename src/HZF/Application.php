<?php
namespace HZF;

/**
 *    hzf服务定位器
 */

use Exception;
use HZF\DI\Container;
use HZF\Support\Loader;
use HZF\Support\ServiceProvider;
use HZF\Support\ProviderRepository;
use HZF\Route\Router;
use HZF\Config\Config;
use HZF\Http\Request;
use HZF\Support\Providers\ConfigServiceProvider as ConfigProvider;
if (!defined('HZF_CORE_PATH')) {
    define('HZF_CORE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

if (!defined('HZF_CORE_LOADER_PATH')) {
    define('HZF_CORE_LOADER_PATH', HZF_CORE_PATH . 'Loader' . DIRECTORY_SEPARATOR);
}

if (!defined('HZF_CORE_HELPER_PATH')) {
    define('HZF_CORE_HELPER_PATH', HZF_CORE_PATH . 'Helpers' . DIRECTORY_SEPARATOR);
}

class Application extends Container
{

    public static $app = null;

    //是否启动
    protected $booted = false;

    //启动中触发的闭包集合
    protected $bootingCallbacks = [];

    //已经注册的服务提供者实例缓存
    protected $serviceProviders = [];

    //已经被载入的服务提供者名称
    protected $loadedServiceProviders = [];

    /**
     * 缓载的服务提供者：通过某些服务被调用时触发被缓载的服务提供者执行注册操作
     * 服务名 => 要缓载的服务提供者
     */
    protected $deferredServiceProviders = [];

    //所需文件
    protected $necessary_helpers = ['array', 'common'];

    //根目录
    protected $root;

    //应用目录
    protected $app_root;

    //核心配置目录
    protected $core_conf_folder;

    //应用名
    protected $APP_NAME = '';

    //版本号
    protected $VN = '';

    //框架默认命名
    protected $namespace = 'HZF';

    //服务收集器
    protected $providerRepository;

    //配置对象
    protected $config;

    /**注册app
     *    @param $app_name：应用名称
     *    @param $vn：版本号
     */
    public function registerApp($app_name, $vn = '')
    {
        $this->APP_NAME = $app_name;
        $this->VN       = $vn;
        $this->app_root = $this->root . 'apps' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR;
        //注册一个app
        Loader::registerRoot($app_name, $this->app_root . $vn . DIRECTORY_SEPARATOR);
        return $this;
    }

    //路由分发
    public function routeDispatcher(array $route_config = array())
    {
        //mvc模式的controller
        try {
            list($status, $callback, $params, $method) = Router::dispatch($this->make('request'), $route_config);
            switch ($status) {
                case Router::FOUND:
                    $this->dispatcher($callback, $params);
                    break;

                default:
                    self::error();
                    break;
            }            
        } catch (Exception $e) {
            echo $e->getMessage();
        } finally {
            return $this;
        }

    }

    //初始化app
    public function init($root_dir, $core_conf_folder = '')
    {
        //注册root路径
        $this->root = $root_dir;

        $this->core_conf_folder = $core_conf_folder ? : $this->root . 'conf' . DIRECTORY_SEPARATOR;

        //注册框架root路径
        Loader::registerRoot($this->namespace, HZF_CORE_PATH);

        //注册自动引入机制
        spl_autoload_register(array(Loader::class, 'loadClass'));

        //注册alias
        $this->registerCoreContainerAlias();

        //注册基础服务提供者
        $this->registerBaseServicesProviders();

        //引入辅助文件
        $this->loadNecessaryFiles();

        //单例
        self::$app = $this;

        //初始化配置
        $this->initConfig();
        //服务仓库收集服务提供者底层服务提供者
        $this->registerConfiguredProviders();

        return $this;
    }

    //收集服务提供者
    public function registerConfiguredProviders()
    {
        ((new ProviderRepository($this))->load($this->config['base']['providers']));
    }

    //启动
    public function bootstrap()
    {
        //启动服务
        $this->bootServices();
        return $this;
    }

    //初始化配置
    private function initConfig()
    {
        $config = $this->make('config');
        $config->loadConfig($this->core_conf_folder);
        $this->config = $config->configs;
    }

    //注册基础服务提供者
    protected function registerBaseServicesProviders()
    {
        $this->registerServiceProvider($this->getProviderInstance(ConfigProvider::class));
    }

    //获取服务提供者实例
    public function getProviderInstance($provider)
    {
        return new $provider($this);
    }

    //对象构造接口
    public function make($abstract, $params = [], $config = [])
    {
        $concrete = $this->getAlias($abstract);
        if(isset($this->deferredServiceProviders[$concrete])) {
            //缓载提供者
            $this->loadDeferredProvider($concrete);
        }

        return parent::make($concrete, $params, $config);
    }

    /**
     * 设置延迟注册的服务提供者
     */
    public function setDeferredServiceProviders(array $serviceProviders)
    {
        $this->deferredServiceProviders = $serviceProviders;
    }

    //加载缓载服务提供者
    public function loadDeferredProvider($service)
    {
        if(!isset($this->deferredServiceProviders[$service]))
            return ;
        if($service)
            unset($this->deferredServiceProviders[$service]);
        $provider = $this->deferredServiceProviders[$service];

        //不可重复加载
        if(!isset($this->loadedServiceProviders[$provider])){
            $this->registerServiceProvider($this->getProviderInstance($provider));
        }

    }

    //注册服务提供者
    public function registerServiceProvider(ServiceProvider $provider, $force = false)
    {
        //在非强制注册下，已经注册不能重复注册
        if(isset($this->loadedServiceProviders[get_class($provider)]) && !$force){
            return $provider;
        }
        //启动注册服务
        $provider->register(); 
        //标记服务提供者
        $this->markAsRegistered($provider);

        //app是否启动
        if($this->booted){
            //调用
            $this->bootProvider($provider);
        } else {
            $this->booting(function() use ($provider) {
                $this->bootProvider($provider);
            });
        }
    }

    //启动服务提供者
    public function bootServices()
    {
        //整个程序有且只有一次被启动
        if($this->booted)
            return ;
        //启动
        $this->fireCallbacks($this->bootingCallbacks);
        $this->booted = true;
    }

    //处理回调
    public function fireCallbacks(array $callbacks)
    {
        if(empty($callbacks)) return ;
        foreach($callbacks as $callback){
            call_user_func($callback, $this);
        }
    }

    //正在启动一个服务
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    //标记服务提供者被注册
    public function markAsRegistered(ServiceProvider $provider)
    {
        $class = get_class($provider);
        $this->serviceProviders[$class] = $provider;
        $this->loadedServiceProviders[$class] = true;
    }

    //启动服务提供者
    public function bootProvider(ServiceProvider $provider)
    {  
        //可以注入依赖对象
        if(method_exists($provider, 'boot'))
            $this->call([$provider, 'boot']);
    }

    //注册默认的class alias
    protected function registerCoreContainerAlias()
    {
        $aliases = [
            'app'     => ['HZF\Application', 'HZF\DI\Container'],
            'config'  => 'HZF\Config\Config',
            'request' => 'HZF\Http\Request',
        ];
        foreach($aliases as $alias => $concrete){
            $concrete = is_array($concrete) ? $concrete : [$concrete];
            foreach ($concrete as $c) {
                $this->alias($alias, $c);
            }
        }
    }

    private function loadNecessaryFiles()
    {
        //引入核心函数
        Loader::loadHelper($this->necessary_helpers, HZF_CORE_HELPER_PATH);
    }

    public function dispatcher($callback, $params = [])
    {
        try {
            if (!is_array($callback) && is_callable($callback)) {
                return call_user_func_array($callback, $params);
            }
            $controller = $this->make($callback[0]);
            $action     = $callback[1];
            if (method_exists($controller, '__remap')) {
                $params = array($action, $params);
                $action = '__remap';
            }
            if (method_exists($controller, $action)) {
                return $this->call(array($controller, $action), $params);
            }
        } catch (Exception $e) {
            self::error();
            echo $e->getMessage();
        }

    }

    //错误处理
    public static function error($callback = '')
    {
        if (is_callable($callback)) {
            $callback();
        }
        //方法调用
        else if (is_array($callback)) {
            call_user_func($callback);
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . " 404 NOT FOUND");
            echo "╮(╯_╰)╭ <br><br><br>404 NOT FOUND!" . (empty($callback) ? '' : " [ERR INFO: $callback]");
            exit;
        }
    }

    /**
     * 获取配置
     */
    public function config($key = '')
    {
        if(empty($key)) {
            return $this->config;
        }

        return $this->config[$key];
    }

}
