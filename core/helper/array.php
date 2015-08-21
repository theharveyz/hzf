<?php 
/**
 *	转化二维数组的key
 */
if(!function_exists('array_change_key'))
{
	function array_change_key(&$arr, $column)
	{
		if(empty($arr)) return array();
		$new_arr = array();
		foreach($arr as $v)
		{
			$new_arr[$v[$column]] = $v;
		}

		$arr = $new_arr;
	}
}

/**
 *	从二维数组中抽出某项的值，返回一个新的数组
 */
if(!function_exists('array_get_column'))
{
	function array_get_column($arr, $column)
	{
		if(empty($arr)) return array();
		$new_arr = array();
		foreach($arr as $v)
		{
			$new_arr[] = $v[$column];
		}
		return $new_arr;
	}
}