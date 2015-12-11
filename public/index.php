<?php
require_once "../vendor/autoload.php";
// $client = new Raven_Client('https://ed93a032bd5b4d03a4652f8531ac9ac3:ae0cd7541b794d06a2d3afd842424df1@app.getsentry.com/59159');
// // bind the logged in user
// $client->user_context(array('email' => 'zharvey@163.com'));

// // tag the request with something interesting
// $client->tags_context(array('interesting' => 'yes'));

// // provide a bit of additional context
// $client->extra_context(array('happiness' => 'very'));


// $error_handler = new Raven_ErrorHandler($client);
// $error_handler->registerExceptionHandler();
// $error_handler->registerErrorHandler();
// $error_handler->registerShutdownFunction();
// throw new Exception("Error Processing Request", 1);

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
