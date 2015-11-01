<?php
namespace HZF;

/**
 *    hzf服务定位器
 */

use Exception;
use HZF\DI\Container;
use HZF\LOADER\Loader as Loader;

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

    public $necessary_classes = array(
        HZF_CORE_LOADER_PATH . 'Loader.php',
    );
    public $necessary_helpers = ['array', 'common', 'error'];

    //应用名
    public $APP_NAME = '';

    //版本号
    public $VN = '';

    //框架默认命名
    public $namespace = 'HZF';

    public function __construct()
    {
    }

    /**注册app
     *    @param $app_name：应用名称
     *    @param $vn：版本号
     */
    public function registerApp($app_name, $vn = '')
    {
        $this->APP_NAME = $app_name;
        $this->VN       = $vn;
        //注册一个app
        \HZF_Loader::registerRoot($app_name, $vn);
        return $this;
    }

    //路由分发
    public function routeDispatcher(array $route_config = array())
    {
        //mvc模式的controller
        list($status, $callback, $params, $method) = \HZF_Router::dispatch($this->make('HZF\Http\Request'), $route_config);
        switch ($status) {
            case \HZF_Router::FOUND:
                $this->dispatcher($callback, $params);
                break;

            default:
                self::error();
                break;
        }
        return $this;
    }

    public function init($core_conf_folder)
    {
        //引入必要的类
        foreach ($this->necessary_classes as $name) {
            include $name;
        }
        //注册框架root路径
        Loader::registerRoot($this->namespace, HZF_CORE_PATH);

        //注册自动引入机制
        spl_autoload_register(array('HZF\Loader\Loader', 'loadClass'));
        //引入核心配置文件
        //set
        $this->bind('config', ['class' => 'HZF\Config\Config']);
        $config = $this->make("config");
        $config->loadConfig($core_conf_folder);
        //设置自动引入类别名
        Loader::setClassAlias($config->get('class_alias'));
        //引入辅助文件
        $this->load();
        return $this;
    }

    private function load()
    {
        //引入核心函数
        \HZF_Loader::loadHelper($this->necessary_helpers, HZF_CORE_HELPER_PATH);
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
