<?php
namespace HZF;

/**
 *    hzf服务定位器
 */

use Exception;
use HZF\DI\Container;
use HZF\Loader\Loader as Loader;
use HZF\Route\Router as Router;
use HZF\Config\Config as Config;
use HZF\Http\Request as Request;

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

    public $necessary_helpers = ['array', 'common'];

    //根目录
    public $root;

    //应用目录
    public $app_root;

    //核心配置目录
    public $core_conf_folder;

    //应用名
    public $APP_NAME = '';

    //版本号
    public $VN = '';

    //框架默认命名
    private $namespace = 'HZF';

    //服务
    protected $services = [];


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
        list($status, $callback, $params, $method) = Router::dispatch($this->make('Request'), $route_config);
        switch ($status) {
            case Router::FOUND:
                $this->dispatcher($callback, $params);
                break;

            default:
                self::error();
                break;
        }
        return $this;
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

        //注册服务
        $this->registerServices();

        //引入辅助文件
        $this->loadNecessaryFiles();

        //单例
        self::$app = $this;

        return $this;
    }

    //启动
    public function bootstrap()
    {
        //初始化配置
        $this->initConfig();

        return $this;
    }

    //初始化配置
    private function initConfig()
    {
        $config = $this->make('Config');
        $config->loadConfig($this->core_conf_folder);
    }

    //注册服务
    private function registerServices()
    {
        $this->services = array_merge($this->defaultsServices(), $this->services);
        foreach($this->services as $service => $config){
            $this->singleton($config['class'], $config);
            $this->alias($service, $config['class']);
        }
    }

    private function defaultsServices()
    {
        return [
            'Config' => [
                'class' => Config::class
            ],
            'Request' => [
                'class' => Request::class
            ]
        ];
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

}
