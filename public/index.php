<?php
require_once "../vendor/autoload.php";
$boot = new HZF\Application();
$boot->init(__DIR__ . '/../' . 'conf' . '/')
    ->registerApp('Test', __DIR__ . '/../apps/Test/20140326/')
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
    ]);
