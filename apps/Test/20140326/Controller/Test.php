<?php
namespace Test\Controller;
use HZF\Config\Config as config;
class Test
{
    public $a;
    public function __construct($a = 'haha')
    {
        $this->a = $a;
    }
    public function foo(Config $config)
    {
        $str = 'asdf\b\c';
        var_dump(strtr($str, "\\", "/"));
        var_dump($config->configs);
    }

    public function xml()
    {

        echo "xml!";
    }

    // public function __remap($method, $pararms = [])
    // {
    //     var_dump($method, $pararms);
    //     foo();
    // }
}
