<?php 
namespace CORE\LIB\ROUTE;

class Intercepter {
	static $instance = null;
	var $uri = '';
	var $segments = [];
	var $isAjax   = false;
	var $method   = '';
	public function __construct()
	{
		//是否是Ajax
		if(!$this->isAjax)
			$this->isAjax();
		//请求方式
		if(!$this->method)
			$this->method = $_SERVER['REQUEST_METHOD'];
		//设置URI
		if(empty($this->uri))
			$this->uri = $this->_detectUri();
		//设置段
		if(empty($this->segments))
			$this->segments = $this->fetchSegs($this->uri);
	}

	public function fetchSegs($uri_string)
	{
		return $this->_explodeSegments($uri_string);
		// return array_values(array_filter(explode('/', $uri)));
	}

	/**
	 * Detects the URI
	 *
	 * This function will detect the URI automatically and fix the query string
	 * if necessary.
	 *
	 * @access	private
	 * @return	string
	 */
	private function _detectUri()
	{
		if ( ! isset($_SERVER['REQUEST_URI']) OR ! isset($_SERVER['SCRIPT_NAME']))
		{
			return '';
		}

		$uri = $_SERVER['REQUEST_URI'];
		if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
		{
			$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		}
		elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
		{
			$uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
		}

		// This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		// URI is found, and also fixes the QUERY_STRING server var and $_GET array.
		if (strncmp($uri, '?/', 2) === 0)
		{
			$uri = substr($uri, 2);
		}
		$parts = preg_split('#\?#i', $uri, 2);
		$uri = $parts[0];
		if (isset($parts[1]))
		{
			$_SERVER['QUERY_STRING'] = $parts[1];
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		}
		else
		{
			$_SERVER['QUERY_STRING'] = '';
			$_GET = array();
		}

		if ($uri == '/' || empty($uri))
		{
			return '/';
		}
		$uri = parse_url($uri, PHP_URL_PATH);

		// Do some final cleaning of the URI and return it
		//去除多个'/', 或者'../'的情况！
		$uri = str_replace(array('//', '../'), '/', $uri);
		if(strpos($uri, '/') !== 0)
			$uri = '/' . $uri;
		return $this->_removeInvisibleCharacters($uri);
	}


	//是否是Ajax请求
	public function isAjax()
	{
		//XHR
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) 
        {
            $this->isAjax = TRUE;
        }
        return $this->isAjax;
	}

	static function getInstance()
	{
		if(is_null(self::$instance))
			self::$instance = new self;
		return self::$instance;
	}

	/**过滤uri中的特殊字符
	 *
	 */
	private function _removeInvisibleCharacters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();
		
		// every control character except newline (dec 10)
		// carriage return (dec 13), and horizontal tab (dec 09)
		
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
		
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		//注意：循环多次，直到没有特殊字符为止，以防止后面匹配后的结果又满足前者！
		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);
		return $str;
	}

	private function _explodeSegments($uri_string)
	{
		$segments = [];
		foreach (explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $uri_string)) as $val)
		{
			// Filter segments for security
			$val = trim($this->_filterUri($val));

			if ($val != '')
			{
				$segments[] = $val;
			}
		}
		return $segments;
	}

	//过滤危险字符
	private function _filterUri($str)
	{
		//被允许的字符：数字、字母、下划线、冒号、百分号、以及"-"
		$permitted_uri_chars = 'a-z 0-9~%.:_\-';
		if ($str != '' && $permitted_uri_chars != '')
		{
			// preg_quote() in PHP 5.3 escapes -, so the str_replace() and addition of - to preg_quote() is to maintain backwards
			// compatibility as many are unaware of how characters in the permitted_uri_chars will be parsed as a regex pattern
			if ( ! preg_match("|^[".str_replace(array('\\-', '\-'), '-', preg_quote($permitted_uri_chars, '-'))."]+$|i", $str))
			{
				$this->_showError('The URI you submitted has disallowed characters.');
			}
		}

		// Convert programatic characters to entities
		$bad	= array('$',		'(',		')',		'%28',		'%29');
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');

		return str_replace($bad, $good, $str);
	}

	private function _showError($msg)
	{
		//HTTP 400 BAD REQUEST! 
		//注意：HTTP版本号 /HTTP状态吗
		header($_SERVER['SERVER_PROTOCOL'] . " 400 BAD REQUEST");
		echo $msg;exit;
	}

}