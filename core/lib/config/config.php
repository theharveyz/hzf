<?php 
namespace CORE\LIB\CONFIG;

class Config {
	static $instance = null;
	var $configs  = array();
	public function loadConfig($folder, $config_file_name = '*')
	{
		if(!file_exists($folder)) return false;
		$all_files = glob($folder . $config_file_name . '.php');
		if(!empty($all_files))
		{
			foreach($all_files as $file)
			{
				$filename = explode('.', basename($file))[0];
				$val      = include $file;
				$this->push($filename, $val);
			}

			return $all_files;
		}
		else
		{
			return array();
		}
	}

	public function push($key, $val = '')
	{
		$cover = isset($this->configs[$key]);
		$this->configs[$key] = $val;
		return $cover;
	}

	public function get($keys = array())
	{
		if(empty($keys)) return $this->configs;
		$keys    = is_array($keys) ? $keys : array($keys);
		$configs = array();
		foreach($keys as $k)
		{
			$configs[$k] = $this->configs[$k];
		}
		return $configs;
	}

	static public function getInstance()
	{
		if(is_null(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}
}