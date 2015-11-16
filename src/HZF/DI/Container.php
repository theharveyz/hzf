<?php
namespace HZF\DI;

use HZF\Contract\HzfObject;
use HZF\DI\Instance;
use ReflectionClass;
use ReflectionMethod;

class Container extends HzfObject
{
    //注册的实例
    /**
     * @var mixed
     */
    public static $instance = null;

    /***********属性封闭，无法通过子类获取**********/
    //别名集合
    /**
     * @var array
     */
    private $_alias = [];

    //单例集合：键名是具体类
    /**
     * @var array
     */
    private $_singletons = [];

    /**
     *    定义的三种类型
     *    1，数组型，必不可少的class项
     *    2，类名型
     *    3，回调函数型
     *    4，对象型=》在make时将会被转换成单例
     */

    //定义集合
    private $_definitions = [];

    //构造函数的参数集合
    /**
     * @var array
     */
    private $_params = [];

    //依赖集合类的反射缓存
    /**
     * @var array
     */
    private $_reflections = [];

    //关于依赖的缓存，可能是构造函数的依赖，或者依赖于方法
    /**
     * @var array
     */
    private $_dependencies = [];

    //绑定为单例
    /**
     * @param $abstract
     * @param array $definition
     * @param array $params
     */
    public function singleton($abstract, $definition = [], array $params = [])
    {
        //获取定义的统一格式
        $normalDefinitions = $this->getNormalDefinitions($abstract, $definition);

        //注册定义
        $this->_definitions[$abstract] = $normalDefinitions;
        //绑定参数
        $this->_params[$abstract] = $params;

        //由于执行了绑定参数，则会将原有的单例去除
        $this->_singletons[$abstract] = null;

        //支持链式调用
        return $this;
    }

    //绑定
    /**
     * @param $abstract
     * @param array $definition
     * @param array $params
     * @return mixed
     */
    public function bind($abstract, $definition = [], array $params = [])
    {
        //获取定义的统一格式
        $normalDefinitions = $this->getNormalDefinitions($abstract, $definition);

        //注册定义
        $this->_definitions[$abstract] = $normalDefinitions;
        //绑定参数
        $this->_params[$abstract] = $params;

        //由于执行了绑定参数，则会将原有的单例去除
        unset($this->_singletons[$abstract]);

        //支持链式调用
        return $this;
    }

    //获取
    //参数可以在绑定时设置，也可以在获取时更新
    //config用以设置对象的属性
    /**
     * @param $abstract
     * @param array $params
     * @param array $config
     * @return mixed
     */
    public function make($abstract, $params = [], $config = [])
    {
        //是否是别名
        $concrete = $this->getAlias($abstract);
        //如果是单例，则返回单例
        if (isset($this->_singletons[$concrete])) {
            return $this->_singletons[$concrete];
        }

        //如果没有定义，则代表未注册，容器不会主动注册用户未注册的类
        if (!isset($this->_definitions[$concrete])) {
            return $this->createObject($concrete, $params, $config);
        }

        //获取定义
        $definition = $this->_definitions[$concrete];
        //如果是回调, 或者闭包
        if (is_callable($definition, true)) {
            $params = $this->mergerParmas($concrete, $params);
            $this->resolveDependencies($params);
            //这里要求：回调的第一个参数必须为DI本身
            $object = call_user_func($definition, $this, $params, $config);
        }

        //如果是数组
        if (is_array($definition)) {
            $class = $definition['class'];
            unset($definition['class']);
            $config = array_merge($definition, $config);
            $params = $this->mergerParmas($concrete, $params);
            $this->resolveDependencies($params);
            if ($concrete == $class) {
                $object = $this->createObject($concrete, $params, $config);
            } else {
                $object = $this->make($class, $params, $config);
            }

        } else if (is_object($definition)) {
            return $this->_singletons[$concrete] = $definition;
        } else {
            throw new \Exception("Error Processing Request", 1);
        }

        return $object;
    }

    //合并参数
    /**
     * @param $concrete
     * @param array $params
     * @return mixed
     */
    protected function mergerParmas($concrete, $params = [])
    {
        if (empty($params)) {
            return $this->_params[$concrete];
        } else if (empty($this->_params[$concrete])) {
            return $params;
        } else {
            $ps = $this->_params[$concrete];
            foreach ($params as $i => $v) {
                $ps[$i] = $v;
            }
        }
        return $ps;
    }

    //创建对象
    /**
     * @param $concrete
     * @param array $params
     * @param array $config
     * @return mixed
     */
    protected function createObject($concrete, array $params = [], array $config = [])
    {
        //获取类反射：这里的反射在对象第一次创建时自动缓存
        list($dependencies, $reflectionClass) = $this->getRefectionAndDependenciesParameters($concrete);
        $dependencies                         = $this->getDependencies($dependencies, $params);
        $this->resolveDependencies($dependencies, $reflectionClass);
        $object = $reflectionClass->newInstanceArgs($dependencies);
        if (!empty($config)) {
            foreach ($config as $name => $value) {
                $object->{$name} = $value;
            }
        }
        return $object;
    }

    //解决依赖
    /**
     * @param $dependencies
     * @param $reflection
     */
    protected function resolveDependencies(array &$dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => &$d) {
            if ($d instanceof Instance) {
                if ($d->class != null) {
                    $d = $this->make($d->class);
                } else {
                    throw new \Exception("Error Processing Request", 1);

                }
            }
        }
    }

    //获取/缓存依赖、反射。这里不光是类的依赖或者反射，还是方法的反射或者依赖
    /**
     * @param $concrete
     * @return mixed
     */
    protected function getRefectionAndDependenciesParameters($concrete)
    {
        //是否字符串
        if (is_string($concrete)) {
            if (isset($this->_reflections[$concrete])) {
                return [$this->_dependencies[$concrete], $this->_reflections[$concrete]];
            }
        }
        $dependencies = [];
        $reflection   = null;
        if (is_string($concrete) && !strpos($concrete, '::')) {
            $reflection = new ReflectionClass($concrete);
            if (!$reflection) {
                throw new \Exception("Error Processing Request", 1);
            }

            //构造器
            $constructor = $reflection->getConstructor();
            if ($constructor !== null) {
                $dependencies = $constructor->getParameters();
            }
        } else {
            if (is_array($concrete)) {
                $class  = $concrete[0];
                $method = isset($concrete[1]) ? $concrete[1] : null;

                //在这里method不能为空
                if (is_null($method)) {
                    throw new \Exception("Error Processing Request", 1);
                }

                if (is_string($class)) {
                    return $this->getRefectionAndDependenciesParameters($class . '::' . $method);
                }

            } else {
                $segmens = explode('::', $concrete);
                $class   = $segmens[0];
                $method  = $segmens[1];
            }
            $reflection = new ReflectionMethod($class, $method);
            if (!$reflection) {
                throw new \Exception("Error Processing Request", 1);
            }
            //要求被调用的方法，必须为public方式
            if (!$reflection->isPublic()) {
                throw new \Exception(" cannot access private method ", 1);

            }
            $dependencies = $reflection->getParameters();
        }

        if (is_string($concrete)) {

            $this->_dependencies[$concrete] = $dependencies;
            $this->_reflections[$concrete]  = $reflection;
        }
        return [$dependencies, $reflection];

    }

    //获取依赖的参数
    /**
     * @param  object $func
     * @return mixed
     */
    protected function getDependencies($reflectionParameters, array &$params = [])
    {
        $dependencies = [];
        if ($reflectionParameters) {
            foreach ($reflectionParameters as $param) {
                if (!empty($params) && array_key_exists($param->name, $params)) {
                    $dependencies[] = $params[$param->name];
                    //删除该参数值
                    unset($params[$param->name]);
                } else {
                    if ($param->isDefaultValueAvailable()) {
                        $dependencies[] = $param->getDefaultValue();
                    } else {
                        $c = $param->getClass();
                        //如果该参数没有被声明为一个对象，则返回空的Instance对象进行站位
                        $dependencies[] = $c == null ? '' : Instance::of($c->name);
                    }
                }

            }
        }
        return $dependencies;
    }

    //设置别名：别名只能唯一存在
    /**
     * @param $abstract
     * @param $concrete
     */
    public function alias($abstract, $concrete = null)
    {
        $this->_alias[$abstract] = $concrete;
    }

    //获取别名
    /**
     * @param $abstract
     */
    public function getAlias($abstract)
    {
        return isset($this->_alias[$abstract]) ? $this->_alias[$abstract] : $abstract;
    }

    /**
     * @param $abstract
     * @param array $definition
     * @return mixed
     */
    protected function getNormalDefinitions($abstract, $definition = [])
    {
        //如果为空
        if (empty($definition)) {
            return ['class' => $abstract];
        }
        //如果位字符串
        else if (is_string($definition)) {
            return ['class' => $definition];
        }
        //如果为数组:则可看做通过配置数组进行绑定，class必须存在
        else if (is_array($definition)) {
            if (!isset($definition['class'])) {
                if (strpos($abstract, '\\') !== false) {
                    $definition['class'] = $abstract;
                } else {
                    throw new \Exception("Error Processing Request", 999);

                }
            }

            return $definition;
        }
        //如果是闭包或者对象
        else if (is_object($definition) || is_callable($definition)) {
            return $definition;
        }
        //否则：错误的定义方式
        else {
            throw new \Exception("Error Processing Request", 1);

        }
    }

    //调用
    /**
     *    三种形式：
     *        1，a::b //通常用于调用类中的静态方法
     *        2，a@b  //调用类中的普通方法，此方法需要类被创建，并且方法的参数也要通过反射获取
     *               注意：当只写a时，有默认的调用方法也是可以的
     *        3，[(obj), method] 数组方式调用，一般情况下第三种方式可以先转换为第四种，然后再执行call方法
     */
    public function call($call, array $params = [], $defaultMethod = null)
    {
        if (is_string($call) && $this->isCanCallable($call)) {
            return $this->callClass($call, $params, $defaultMethod);
        }

        list($dependencies, $reflection) = $this->getRefectionAndDependenciesParameters($call);
        $dependencies                    = $this->getDependencies($dependencies, $params);
        //合并
        $dependencies = array_merge($dependencies, $params);
        $this->resolveDependencies($dependencies, $reflection);
        return call_user_func_array($call, $dependencies);
    }

    /**
     * @param $class
     * @param $method
     * @param $params
     */
    protected function callClass($call, array $params = [], $defaultMethod = null)
    {
        $segments = explode('@', $call);

        $method = isset($segmens[1]) ? $segmens[1] : $defaultMethod;
        if (is_null($method)) {
            throw new \Exception("Error Processing Request", 1);
        }
        return $this->call([$this->make($segmens[0]), $method], $params);
    }

    //是否可以调用
    /**
     * @param $call
     */
    protected function isCanCallable($call = '')
    {
        if (is_string($call) && strpos($call, '@') !== false) {
            return true;
        }

        return false;
    }
}
