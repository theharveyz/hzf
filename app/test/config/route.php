<?php 
return [
	'/test/{:num}' => function($num){
		echo "$num";
	},
	'/{:num}' => function($num){
		echo "$num";
	},
	'/feed.xml' => 'test@xml'
];