<?php
require_once "../vendor/autoload.php";
$root = dirname(__DIR__) . '/';
$app = new HZF\Application();
$app->init($root)
    ->registerApp('Test', 20140326)
    ->bootstrap()
    ->routeDispatcher([
        '/test/(:num)' => array(
            'get'  => function ($num) {
                echo "GET $num";
            },
            'post' => function ($num) {
                echo "POST $num";
            },
        ),
        '/(:num)'      => function ($num) {
            echo "$num";
        },
        '/feed.xml'    => 'Test\Controller\Test@xml',
        '/test/foo'    => 'Test\Controller\Test@foo',
    ]);
