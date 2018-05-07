<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/** 取中间文本的函数
 * @param $str string 预取全文本
 * @param $leftStr string 左边文本
 * @param $rightStr string 右边文本
 * @return bool|string
 */
function getSubstr($str, $leftStr, $rightStr)
{
	$left = strpos($str, $leftStr);
	//echo '左边:'.$left;
	$right = strpos($str, $rightStr, $left);
	//echo '<br>右边:'.$right;
	if ($left < 0 or $right < $left) return '';
	return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
}