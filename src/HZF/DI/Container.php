<?php 
namespace HZF\DI;
use ArrayAccess;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use HZF\Contract\HzfObject;
use HZF\DI\Instance;

class Container implements ArrayAccess, HzfObject{
	//注册的实例
	public static $instance = null;

	/***********属性封闭，无法通过子类获取**********/
	//别名集合
	private $_alias = [];

	//单例集合：键名是具体类
	private $_singletons = [];

	//定义集合
	private $_definitions = [];

	//构造函数的参数集合
	private $_params = [];

	//依赖集合类的反射缓存
	private $_reflections = [];

	//关于依赖的缓存，可能是构造函数的依赖，或者依赖于方法
	private $_dependencies = [];

	//绑定
	public function bind($abstract, $definition = [], array $params = [])
	{
		//获取定义的统一格式
		$normalDefinitions = $this->getNormalDefinitions($abstract, $definition);

		// //区分下别名与具体类名
		// $concrete = is_array($normalDefinitions) ? $normalDefinitions['class'] : $abstract;

		// //如果抽象跟具体类名不同，则设置为别名
		// $concrete == $abstract OR $this->alias($abstract, $concrete);
		//注册定义
		$this->_definitions[$abstract] = $normalDefinitions;
		//绑定参数
		$this->_params[$abstract]   = $params;

		//由于执行了绑定参数，则会将原有的单例去除
		unset($this->_singletons[$abstract]);

		//支持链式调用
		return $this;
	} 

	//获取
	//参数可以在绑定时设置，也可以在获取时更新
	//config用以设置对象的属性
	public function make($abstract, $params = [], $config = [])
	{
		//是否是别名
		$concrete = $this->getAlias($abstract);
		//如果是单例，则返回单例
		if(isset($this->_singletons[$concrete]))
			return $this->_singletons[$concrete];

		//如果没有定义，则代表未注册，容器不会主动注册用户未注册的类
		if(!isset($this->_definitions[$concrete])){
			return $this->createObject($concrete, $params, $config);
		}

		//获取定义
		$definition = $this->_definitions[$concrete];
		//如果是回调
		if(is_callable($definition, true)){
			$params = $this->mergerParmas($concrete, $params);
			$this->resolveDependencies($params);
			$object = call_user_func($definition, $this, $params, $config);
		}

		//如果是数组
		if(is_array($definition)){
			$class = $definition['class'];
			unset($definition['class']);
			$config = array_merge($definition, $config);
			$params = $this->mergerParmas($concrete, $params);
			$this->resolveDependencies($params);
			if($concrete == $class){
				$object = $this->createObject($concrete, $params, $config);
			}else{
				$object = $this->make($class, $params, $config);
			}

		}
		else if(is_object($definition)){
			return $this->_singletons[$concrete] = $definition;
		}
		else{
			throw new \Exception("Error Processing Request", 1);
		}

		return $object;
	}

	//合并参数
	protected function mergerParmas($concrete, $params = [])
	{
		if(empty($params))
			return $this->_params[$concrete];
		else if(empty($this->_params[$concrete]))
			return $params;
		else{
			$ps = $this->_params[$concrete];
			foreach($params as $i => $v){
				$ps[$i] = $v;
			}
		}
		return $ps;
	}

	//创建对象
	protected function createObject($concrete, $params = [], $config = [])
	{
		//获取类反射：这里的反射在对象第一次创建时自动缓存
		list($dependencies, $reflectionClass) = $this->getRefectionAndDependencies($concrete);
		foreach($params as $i => $v){
			$dependencies[$i] = $v;
		}
		$this->resolveDependencies($dependencies, $reflectionClass);
		$object = $reflectionClass->newInstanceArgs($dependencies);
		if(!empty($config)){
			foreach($config as $name => $value){
				$object->{$name} = $value;
			}
		}
		return $object;
	}

	//解决依赖
	protected function resolveDependencies(&$dependencies, $reflection = null)
	{
		foreach($dependencies as $index => &$d){
			if($d instanceof Instance){
				if($d->class != null){
					$d = $this->make($d->class);
				}else{
					throw new Exception("Error Processing Request", 1);
					
				}
			}
		}
	}

	//获取/缓存依赖、反射。这里不光是类的依赖或者反射，还是方法的反射或者依赖
	protected function getRefectionAndDependencies($concrete)
	{
		//是否字符串
		if(is_string($concrete)){
			if(isset($this->_reflections[$concrete]))
				return [$this->_reflections[$concrete], $this->_dependencies[$concrete]];
			$dependencies = [];
			$reflection   = null;
			if(!strpos($concrete, '::')){
				$reflection = new ReflectionClass($concrete);
				//构造器
				$constructor = $reflection->getConstructor();
				if($constructor !== null){
					$dependencies = $this->getDependenciesByRefectionFunction($constructor);
				}
			}
			else{
				$reflection = new ReflectionMethod($concrete);
				if(!$reflection)
					throw new \Exception("Error Processing Request", 1);
				$dependencies = $this->getDependenciesByRefectionFunction($reflection);
			}
			$this->_dependencies[$concrete] = $dependencies;
			$this->_reflections[$concrete] = $reflection;
			return [$dependencies, $reflection];
		}
		//获取是闭包
		else if(is_callable($concrete)){
			return $this->getDependenciesByRefectionFunction(new ReflectionFunction($concrete));
		}

		throw new \Exception("Error Processing Request", 1);
		
	}

	//获取依赖的参数
	protected function getDependenciesByRefectionFunction(ReflectionFunction $func)
	{
		$dependencies = [];
		foreach($func->getParameters() as $param){
			if($param->isDefaultValueAvailable()){
				$dependencies[] = $param->getDefaultValue();
			}else{
				$c = $param->getClass();
				//如果该参数没有被声明为一个对象，则返回空的Instance对象进行站位
				$dependencies[] = $c == null ? '' : Instance::of($c);
			}
		}

		return $dependencies;
	}

	//设置别名：别名只能唯一存在
	public function alias($abstract, $concrete = null)
	{
		$this->_alias[$abstract] = $concrete;
	}

	//获取别名
	public function getAlias($abstract)
	{
		return isset($this->_alias[$abstract]) ? $this->_alias[$abstract] : $alias;
	}

	protected function getNormalDefinitions($abstract, $definition = [])
	{
		//如果为空
		if(empty($definition)){
			return ['class' => $abstract];
		}
		//如果位字符串
		else if(is_string($definition)){
			return ['class' => $definition];
		}
		//如果为数组:则可看做通过配置数组进行绑定，class必须存在
		else if(is_array($definition)){
			if(!isset($definition['class'])){
				if(strpos($abstract, '\\') !== false){
					$definition['class'] = $abstract;
				}else{
					throw new \Exception("Error Processing Request", 999);
					
				}
			}

			return $definition;
		}
		//如果是闭包或者对象
		else if(is_object($definition) || is_callable($definition)){
			return $definition;
		}
		//否则：错误的定义方式
		else{
			throw new Exception("Error Processing Request", 1);
			
		}
	}

	//调用
	public function call($call, $params = [])
	{
		if($this->isCanCallable($call))
		{
			$class = explode('::', $call);
			$method = isset($class[1]) ? $class[1] : '';
			$class = $class[0];
			if($method){
				$object = $this->make($class);
				list($dependencies, $reflection) = $this->getRefectionAndDependencies($call);
				$this->resolveDependencies($dependencies, $reflectionClass);
				foreach($params as $k => $p){
					$dependencies[$k] = $p;
				}
				return call_user_func_array([$object, $method], $dependencies);
			}
			throw new \Exception("Error Processing Request", 1);
			
		}
		throw new \Exception("Error Processing Request", 1);
	}

	//是否可以调用
	protected function isCanCallable($call = ''){
		if(is_string($call))
			return true;
		return false;
	}
}