<?php
namespace HZF\Contract;

/**
 *    HZF对象代码块
 */
trait HzfObjectTrait
{
    public function __construct($config = [])
    {
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $this->{$key} = $val;
            }
        }

        //指向实例
        // $this->init();
    }


    //tips: 这里报错：
    /*
     *  Strict standards: Declaration of HZF\Application::init() should be compatible with HZF\Contract\HzfObject::init($config = Array) 
     *  in /home/wwwroot/HZF/src/HZF/Application.php on line 30
     */
    // public function init($config = [])
    // {

    // }

    public function __set($name, $value = '')
    {
        
    }

    public function __get($name)
    {

    }

    public function __unset($name)
    {

    }

    public function __isset($name)
    {

    }

    public function __call($method, $params)
    {
        throw new \Exception('Call undefined method: ' . get_class($this) . '->' . $method, 999);
    }

    public static function __callStatic($method, $params)
    {
        throw new \Exception("Error Processing Request", 1);
        
    }

}
