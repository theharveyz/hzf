<?php
/**
 * weizhao
 *
 * 参数 targ_w, targ_h : 想要生成图片的宽高
 * 		resize_w, resize_h : 缩放后的宽 ， 高
 * 		select_w, select_h : 选择框的宽, 高
 * 		select_x, select_y : 选择框的位置, x轴 y轴
 * 		ori_src : 源图像位置
 * 		
 * 需要裁剪的实际位置 x坐标cut_x， y坐标cut_y, 宽cut_x, 高cut_y
 *       $cut_x = $cut_y = $cut_w = $cut_y = 0;
 * @author Weizhao <weizhao029@foxmail.com>
 */

class Util_FileStorge {
	protected $storge_root = '';

	//默认文件类型
	protected $storge_file_type = ['php', 'json', 'log'];

	//写入文件
	public function cached($node_type, default, $)

	public function __construct($node_type, $file_type = 'php', $cache_mod = "single")
	{

	}

	//指定缓存仓库root
	public function setRoot($dir = "")
	{
		if(!empty($dir) && file_exists(dirname($dir)))
		{
			$this->storge_root = $dir;
		}
		else
		{
			throw new FileStorgeException("", 1);
			
		}
	}

}