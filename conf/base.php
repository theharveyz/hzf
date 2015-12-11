<?php
$return = [
    'timezone'  => "Asia/Shanghai",
    'providers' => [
    	HZF\Support\Providers\ConfigServiceProvider::class,
    	HZF\Support\Providers\Http\RequestServiceProvider::class,
    ],
];

//timezone set
date_default_timezone_set($return['timezone']);

return $return;
