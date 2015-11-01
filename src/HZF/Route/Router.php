<?php
namespace HZF\Route;

use Closure;
use HZF\Http\Request as Request;

/**
 *    路由分发器:
 *        1, 根据路由规则实现分发
 *        2, 路由器工作时必须注入一个拦截器实体
 */

class Router
{
    //未找到资源
    const NOT_FOUND = 404;

    //未找到合适的请求方式
    const METHOD_NOT_ALLOWED = 405;

    //成功
    const FOUND = 200;

    //拦截器
    static $intercepter = null;
    //路由解析器
    static $route_parser = null;
    //路由规则
    /**
     *    '/a/b' => ['GET' => '', POST => ''] 或者
     *    '/a/b' => 'xxx@xxx' //指每个请求方式都按照这个规则来
     *    /a/{:num} => func($num){} or a@__construct($num) or a@b($num)
     */
    static $route_rules = [];

    //pattern
    static $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*',
    ];

    //分发
    public static function dispatch(Request $intercepter, array $route_rules = array())
    {
        if (is_null(self::$intercepter)) {
            self::$intercepter = $intercepter;
        }

        self::$route_rules = array_merge(self::$route_rules, $route_rules);
        $params            = [];
        $rule              = '';

        $route_rules = empty(self::$route_rules) ? array('/' => 'index@index') : self::$route_rules;

        //遍历规则
        $pattern_key_words = array_keys(static::$patterns);
        $patterns_regxs    = array_values(static::$patterns);

        foreach ($route_rules as $uri => $handle) {
            $uri = str_replace($pattern_key_words, $patterns_regxs, $uri);
            if ($uri == self::$intercepter->uri) {
                $rule = $handle;
                break;
            } else {
                $uri = '#^' . $uri . '$#';
                preg_match($uri, self::$intercepter->uri, $matches);
                $matches = is_array($matches) ? array_filter($matches) : $matches;
                if (!empty($matches)) {
                    $rule   = $handle;
                    $params = array_slice($matches, 1);
                }
            }
        }

        $method = strtolower(self::$intercepter->method);
        if (is_array($rule)) {
            $rule = isset($rule[$method]) ? $rule[$method] : '';
        }

        $class = $action = '';
        //处理匹配结果
        if (!empty($rule)) {
            //闭包的情况: 注意与is_callable不同
            if ($rule instanceof Closure) {
                return [self::FOUND, $rule, $params, $method];
                // return self::exe($rule, $params);
            } else {
                $segments = explode('@', $rule);
                $class    = $segments[0];
                $action   = isset($segments[1]) ? $segments[1] : 'index';
            }
        } else {
            $segments = self::$intercepter->segments;
            switch (count($segments)) {
                case 0:
                    $class = $action = 'index';
                    break;
                case 1:
                case 2:
                    $class  = $segments[0];
                    $action = isset($segments[1]) ? $segments[1] : 'index';
                    break;
                default:
                    $class  = $segments[0];
                    $action = $segments[1];
                    $params = array_slice($segments, 2);

            }
        }
        $class = rtrim(ucfirst($class), '\\');
        //判断是否是标准类名
        foreach (array_filter(explode('\\', $class)) as $c) {
            preg_match('#^[a-zA-Z]([a-zA-Z0-9_])+$#', $c, $match);
            if (!$match) {
                return [self::NOT_FOUND];
            }
        }

        return [self::FOUND, [$class, $action], $params, $method];
    }

    //路由方法重载
    public static function __callStatic($method, $params = '')
    {
        $method = strtoupper($method);
        if (empty($params)) {
            return false;
        }

        $route = $params[0];
        //空路由，即该路由执行操作为空时，什么操作都不进行
        $callback                  = isset($params[1]) ? $params[1] : '';
        self::$route_rules[$route] = $callback;
    }

    //设置pattern，使其在全局范围有效
    public static function pattern($pattern, $regx)
    {
        self::$patterns[$pattern] = $regx;
    }

    //设置某个路由的所有规则
    public static function group($route, $rule)
    {
        self::$route_rules[$route] = $rule;
    }

    //注册路由规则
    public static function setRules($rules = [])
    {
        self::$route_rules = array_merge(self::$route_rules, $rules);
    }
}
