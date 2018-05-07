<?php

namespace app\xaufe\model;

use org\util\Curl;
use think\Model;
use QL\QueryList;

class EduSysWeb extends Model
{
	//一次实例化对象登入，可以连续进行后续操作
	public $xh;
	public $cookies;
	private $view_state;
	
	private function updataViewState($html)
	{
		$QL = new QueryList();
		$date = $QL->html($html)->rules([
			'__VIEWSTATE' => [":input:hidden[name='__VIEWSTATE']", 'value'],
			'__EVENTTARGET' => [":input:hidden[name='__EVENTTARGET']", 'value'],
			'__EVENTARGUMENT' => [":input:hidden[name='__EVENTARGUMENT']", 'value']
		])->query()->getData();
		$this->view_state = $date->all();
		return $this->view_state;
	}
	
	private function updataCookies($new_cookies) //合并更新cookies
	{
		json();
	}
	
	
	/** 将抓取的数据转换为可输出的数据格式
	 * @param $courses
	 * @return array
	 */
	private function transCourse($courses)
	{
		$couInfos = array();
		foreach ($courses as $course) {
			$couInfo = explode('<br>', $course[1]);
			
			preg_match("/周(.*)第(\d{1,}),(\d{1,})节\{第(\d{1,})-(\d{1,})周\|?(.*周)?\}/i", $couInfo[1], $tem_info);
			
			unset($time);
			
			switch ($tem_info[1]) {
				case "一":
					$time[] = 1;
					break;
				case "二":
					$time[] = 2;
					break;
				case "三":
					$time[] = 3;
					break;
				case "四":
					$time[] = 4;
					break;
				case "五":
					$time[] = 5;
					break;
				case "六":
					$time[] = 6;
					break;
				case "日":
					$time[] = 7;
					break;
				default:
					$time[] = 0;
			}
			$time [] = (int)$tem_info[2];
			$time [] = (int)$tem_info[3];
			
			if (isset($tem_info[6])) {
				if ($tem_info[6] == '单周')
					$week = [1, (int)$tem_info[4], (int)$tem_info[5]];
				else if ($tem_info[6] == '双周')
					$week = [2, (int)$tem_info[4], (int)$tem_info[5]];
			} else $week = [0, (int)$tem_info[4], (int)$tem_info[5]];
			
			$couInfos[] =
				["className" => $couInfo[0],
					"time" => $time,
					"week" => $week,
					"teacher" => $couInfo[2],
					"type" => 0,
					"location" => $couInfo[3]];
		}
		return $couInfos;
	}
	
	static public function getCheckCode()
	{
		$check_code_url = config('EduSysWeb_url') . '/CheckCode.aspx';
		$image = Curl::get($check_code_url, null, $r_cookies, $r_code);
		if ($r_code == '200')
			return ['image' => 'data:image/gif;base64,' . base64_encode($image), 'cookies' => $r_cookies];
		else return 'error:' . $r_code;
	}
	
	public function login($xh, $stu_psw, $check_code, $cookies)
	{
		$this->xh = $xh;
		$this->cookies = $cookies;
		
		$login_url = config('EduSysWeb_url') . '/Default2.aspx';
		$post_data = "__VIEWSTATE=dDwtNTE2MjI4MTQ7Oz681Ec4muNvkMixSRph9FM75zxqfQ%3D%3D&txtUserName=" . $this->xh . "&Textbox1=&TextBox2=" . $stu_psw . "&txtSecretCode=" . $check_code . "&RadioButtonList1=%D1%A7%C9%FA&Button1=&lbLanguage=&hidPdrs=&hidsc=";
		$output = Curl::post($login_url, $post_data, $this->cookies, $r_cookies, $r_code, null, $r_header, 10);
		$output = iconv("gbk", "utf-8//ignore", $output);  //编码转换
		
		if ($r_code == '302' && strpos($r_header, 'Location: /xs_main.aspx?xh=')) {  //判断是否重定向到正确的登入页面
			
			return $this->loginMain();
			
		} else {
			preg_match('/defer>alert\(\'(.*)\'\);document/i', $output, $error); //获取错误信息
			return $error[1];
		}
	}
	
	public function loginMain($xh = null, $cookies = null)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
		$login_drect_url = config('EduSysWeb_url') . '/xs_main.aspx?xh=' . $this->xh;
		$header = array("Referer: " . config('EduSysWeb_url') . "/Default2.aspx");
		$output = Curl::get($login_drect_url, $this->cookies, $r_cookies, $r_code, $header, $r_header, 10);
		$output = iconv("gbk", "utf-8//ignore", $output);
		
		$this->updataViewState($output); //更新网页状态
		
		if ($r_code == 200) {
			preg_match("#<span id=\"xhxm\">(.*)</span>#i", $output, $name); //登入成功后获取名字
			return $name[1];
		} else return 'error:404';
		
	}
	
	
	public function getInfo($xh = null, $cookies = null)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
	}
	
	public function getCourse($xh = null, $cookies = null)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
		$course_url = config('EduSysWeb_url') . "/xskbcx.aspx?xh=" . $this->xh . "&gnmkdm=N121603";
		$header = array("Referer: " . config('EduSysWeb_url') . '/xs_main.aspx?xh=' . $this->xh);
		$html = Curl::get($course_url, $this->cookies, $r_cookies, $r_code, $header);
		
		if ($r_code == 200) {
			$html = iconv("gbk", "utf-8//ignore", $html);
			
			$QL = new QueryList();
			$html = getSubstr($html, "<table id=\"Table1\"", "</table>");
			$html = "<table id=\"Table1\"" . $html . "</table>";
			
			$data = $QL->html($html)->rules([
				'1' => ["", 'html']
			])->range("table>tr>td[align='Center'][rowspan]")->query()->getData()->all();
			
			$course = $this->transCourse($data);
			return $course;
			
		} else {
			return 'error:' . $r_code;
		}
	}
	
	public function getFreeClass($xh = null, $cookies = null)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
	}
	
	public function getScore($xh = null, $cookies = null)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
	}
	
}