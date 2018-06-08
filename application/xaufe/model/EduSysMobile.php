<?php

namespace app\xaufe\model;

use think\Model;
use org\util\Curl;

class EduSysMobile extends Model
{
	public $cookies;
	public $xh;
	
	private function getUrl($choice, $xh)
	{
		$url = config('EduSysMobile_url')
			. '/login_sso.aspx?procode=' . config('EduSysMobil_url_procode')
			. '&type=' . config('EduSysMobil_url_type')
			. '&choice=' . config('EduSysMobil_url_choice.' . $choice)
			. '&uid=' . $xh . '&role=XS';
		$time = time();
		$key = md5(config('EduSysMobil_url_procode') . config('EduSysMobil_url_choice.' . $choice) . $xh . config('EduSysMobile_encrypt') . $time);
		$url .= '&key=' . $key . '&time=' . $time;
		return $url;
	}
	
	public function getCookies($xh)
	{
		$this->xh = $xh;
		
		$course_url = $this->getUrl('info', $this->xh);
		
		
		Curl::get($course_url, null, $r_cookies, $r_code, null, $r_header);

//		print_r($r_cookies);
//		print_r($r_header);
		
		if ($r_code == '302' && strpos($r_header, 'Location: /XSXX/xsjbxx.aspx')) {
			
			$this->cookies = $r_cookies;
			return true;
			
		} else {
			
			return false;
		}
	}
	
	static function login($xh, $psd)
	{
		$url = 'http://jiaowu.xaufe.edu.cn:8001/zftal-mobile/webservice/newmobile/MobileLoginXMLService%20HTTP/1.1';
		$data = <<<data
<v:Envelope xmlns:i="0" xmlns:d="0" xmlns:c="0"  xmlns:v="http://schemas.xmlsoap.org/soap/envelope/"><v:Header /><v:Body><n0:Login id="o0" c:root="1" xmlns:n0="http://service.login.newmobile.com/"><userName i:type="d:string">##学号##</userName><passWord i:type="d:string">##密码##</passWord><strKey i:type="d:string">WYNn2rNOtkuMGGlPrFSaMB0rQoBUmssS</strKey></n0:Login></v:Body></v:Envelope>
data;
		$data = str_replace('##学号##', $xh, $data);
		$data = str_replace('##密码##', $psd, $data);
		$re_data = Curl::post($url, $data);
		
		if ($re_data==false)
			return ['errCode'=>1001];
		
		if (strpos($re_data, 'app_token') > 0)
			return true;
		else return false;
	}
	
	function transCousrse($html)
	{
		preg_match_all("<a href='(.*?)'>", $html, $cou_urls);
		$cou_url = array_unique($cou_urls[1]);
		
		preg_match('#selected="selected" value="(.*?)"#i', $html, $match);
		$xnxq = explode('@', $match[1]);
		
		$format_course = [];
		foreach ($cou_url as $url) {
			$course_url = config('EduSysMobile_url') . "/KBCXGL/" . $url;
			
			$tmp_html = Curl::get($course_url, $this->cookies);
			
			if ($tmp_html==false)
				return ['errCode'=>1003];
			
			preg_match_all("#<li class=\"ui-border-b\"\>(.*?)<\/li\>#is", $tmp_html, $re_sub);
			
			foreach ([4, 6, 7] as $class) {
				$re_sub[1][$class] = preg_replace('/<br>/', '#', $re_sub[1][$class]);
				$re_sub[1][$class] = preg_replace('/(\s.*?)|(<.*?>)/', '', $re_sub[1][$class]); //教师
			}
			$times = explode('#', explode("：", $re_sub[1][6])[1]);
			$locations = explode('#', explode("：", $re_sub[1][7])[1]);
			
			$className = explode('：', $re_sub[1][1])[1];
			$teacher = explode('：', $re_sub[1][4])[1];
			
			foreach ($times as $key => $time) {
				
				preg_match("/周(.*)第(\d{1,}),(\d{1,})节\{第(\d{1,})-(\d{1,})周\|?(.*周)?\}/i", $time, $tem_info);
				
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
				
				if (isset($locations[$key]))
					$location = $locations[$key];
				
				$format_course[] = [
					"xn" => $xnxq[0],
					"xq" => $xnxq[1],
					"class_name" => $className,
					"time" => $time,
					"week" => $week,
					"teacher" => $teacher,
					"type" => 1,
					"location" => $location
				];
			}
		}
		return $format_course;
	}
	
	function transScore($html)
	{
		$re_info = array(
			'course' => 'class=\'fl\'>',
			'credit' => '学分：',
			'xq' => '学年学期：',
			'course_code' => '课程代码：',
			'score' => '成绩：',
			'bk_score' => '补考成绩：',
			'fx_mark' => '辅修标记：',
			'cx_mark' => '重修标记：',
			'quality' => '课程性质：',
			'college_name' => '开课学院：'
		);
		
		preg_match_all("#<li>(.*?)</li>#i", $html, $subs);
		$subjects = [];
		foreach ($subs[1] as $id => $sub) {
			foreach ($re_info as $key => $re) {
				preg_match("#$re(.*?)(\) )?<#i", $sub, $sub_info);
				
				if ($key == 'xq') {
					$xnxq = explode('-', $sub_info[1]);
					$subjects[$id]['xn'] = $xnxq[0] . '-' . $xnxq[1];
					$subjects[$id]['xq'] = $xnxq[2];
					continue;
				}
				
				$subjects[$id][$key] = $sub_info[1];
			}
		}
		
		return $subjects;
	}
	
	function transInfo($html)
	{
		$re_info = array(
			"name" => 'labxm',
			"sex" => 'labxb',
			"grade" => 'labdqszj',
			"birthday" => 'labcsrq',
			"school_date" => 'labrxrq',
			"province" => 'lablys',
			"nation" => 'labmz',
			"major" => 'labzy',
			"class_name" => 'labxzb',
			"education" => 'labcc',
			"school_year" => 'labxz',
			"college_name" => 'labxy',
			"ID_number" => 'labsfzh',
			"address" => 'lbjtszd'
		);
		$stu_info = [];
		foreach ($re_info as $id => $re) {
			preg_match("#<label id=\"$re\"\>(.*?)</label\>#i", $html, $info);
			$stu_info[$id] = $info[1];
		}
		return $stu_info;
	}
	
	function transExam($html)
	{
	
	}
	
	private function checkCookies($xh, $cookies)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null) {
			$this->cookies = $cookies;
			return true;
		}
		if (empty($this->cookies))
			return $this->getCookies($xh);
		else return true;
	}
	
	public function getCourse($xh, $cookies = null, $xn = null, $xq = null)
	{
		$this->checkCookies($xh, $cookies);
		
		//TODO::对不同学期进行操作
//		echo $this->getUrl('course',$xh);
		if ($this->getCookies($xh)) {
			$location_url = config('EduSysMobile_url') . "/KBCXGL/xskcbcx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			if ($html==false)
				return ['errCode'=>1002];
			
			return $this->transCousrse($html);
		} else return false;
	}
	
	public function getScore($xh, $cookies = null, $xn = null, $xq = null)
	{
		//TODO::对不同学期进行操作
		if ($this->checkCookies($xh, $cookies)) {
			$location_url = config('EduSysMobile_url') . "/XSCJCX/xsdqcjcx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			if ($html==false)
				return ['errCode'=>1004];
			
			return $this->transScore($html);
		} else return false;
	}
	
	public function getInfo($xh, $cookies = null)
	{
		//TODO::获取用户的头像
		if ($this->checkCookies($xh, $cookies)) {
			$location_url = config('EduSysMobile_url') . "/XSXX/xsjbxx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			
			if ($html==false)
				return ['errCode'=>1002];
			
			$info = $this->transInfo($html);
			return $info;
		} else return false;
	}
	
	public function getExam($xh, $cookies = null)
	{
		//TODO::解析exam
		if ($this->checkCookies($xh, $cookies)) {
			$location_url = config('EduSysMobile_url') . "/KSCXGL/xskscx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			print_r($html);
			return true;
		} else return false;
	}
	
}