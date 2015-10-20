<?php 
return [
	'/test/(:num)' => array(
		'get' => function($num){
			echo "GET $num";
		},
		'post' => function($num){
			echo "POST $num";
		}
	),
	'/(:num)' => function($num){
		echo "$num";
	},
	'/feed.xml' => 'test@xml'
];