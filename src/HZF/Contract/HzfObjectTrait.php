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

    // public function init()
    // {

    // }

    // public function __set($name)
    // {

    // }

    // public function __get($name)
    // {

    // }

    // public function __unset($name)
    // {

    // }

    public function __call($method, $params)
    {
        throw new \Exception('Call undefined method: ' . get_class($this) . '->' . $method, 999);
    }

}
