<?php 
/**
 * HZF基本对象类
 */
namespace HZF\Contract;
use HZF\Contract\HzfInterface;

/**
 * HzfObject 参考yii实现了对象属性的动态重载，以及自定义截获处理。
 */

class HzfObject implements HzfInterface {
	use HzfObjectTrait;
}