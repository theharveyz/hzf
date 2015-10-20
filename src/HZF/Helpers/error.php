<?php 
/**
 * 错误处理
 */
function header404($errno = '', $error_str = '')
{
	header($_SERVER['SERVER_PROTOCOL'] . " 404 NOT FOUND");
	echo "╮(╯_╰)╭ <br><br><br>404 NOT FOUND!";
	exit;
}