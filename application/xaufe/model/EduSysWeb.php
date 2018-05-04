<?php

namespace app\xaufe\model;

use org\util\Curl;
use think\Model;
use QL\QueryList;

class EduSysWeb extends Model
{
	
	
	public function login($stu_num, $stu_psw, $check_code, $cookies)
	{
		$login_url = config('EduSysWeb_url') . '/Default2.aspx';
		$post_data = "__VIEWSTATE=dDwtNTE2MjI4MTQ7Oz681Ec4muNvkMixSRph9FM75zxqfQ%3D%3D&txtUserName=" . $stu_num . "&Textbox1=&TextBox2=" . $stu_psw . "&txtSecretCode=" . $check_code . "&RadioButtonList1=%D1%A7%C9%FA&Button1=&lbLanguage=&hidPdrs=&hidsc=";
		$output = Curl::post($login_url, $post_data, $cookies, $r_cookies, $r_code, null, $header);
		$output = iconv("gbk", "utf-8//ignore", $output);
		
		if ($r_code == '302' && strpos($header, 'Location: /xs_main.aspx?xh=')) {  //判断是否重定向到正确的登入页面
			
			$login_drect_url = config('EduSysWeb_url') . '/xs_main.aspx?xh=' . $stu_num;
			$header = array("Referer: " . config('EduSysWeb_url') . "/Default2.aspx");
			$output = Curl::get($login_drect_url, $cookies, $r_cookies, $r_code, $header, $r_header);
			$output = iconv("gbk", "utf-8//ignore", $output);
			
			preg_match("#<span id=\"xhxm\">(.*)</span>#i", $output, $name); //登入成功后获取名字
			return $name[1];
			
		} else {
			
			preg_match('/defer>alert\(\'(.*)\'\);document/i', $output, $error); //获取错误信息
			return $error[1];
		}
		
	}
	
	public function getCheckCode()
	{
		$check_code_url = config('EduSysWeb_url') . '/CheckCode.aspx';
		$image = Curl::get($check_code_url, null, $r_cookies, $r_code);
		if ($r_code == '200')
			return ['image' => 'data:image/gif;base64,' . base64_encode($image), 'cookies' => $r_cookies];
		else return 'error:' . $r_code;
	}
	
	public function getInfo($xh,$cookies)
	{
	
	}
	
	public function getCourse($xh,$cookies)
	{
	
	}
	
	public function getFreeClass($xh,$cookies)
	{
	
	}
	
	public function getScore($xh,$cookies)
	{
	
	}
	
}