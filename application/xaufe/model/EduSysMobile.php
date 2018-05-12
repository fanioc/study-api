<?php

namespace app\xaufe\model;

use think\Model;
use QL\QueryList;
use org\util\Curl;

class EduSysMobile extends Model
{
	private $cookies;
	
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
	
	private function getCookies($xh)
	{
		$course_url = $this->getUrl('info', $xh);
		
		Curl::get($course_url, null, $r_cookies, $r_code, null, $r_header);
		
		print_r($this->cookies);
		
		if ($r_code == '302' && strpos($r_header, 'Location: /XSXX/xsjbxx.aspx')) {
			
			$this->cookies = $r_cookies;
			return true;
			
		} else {
			
			return false;
		}
	}
	
	function login($xh, $psw)
	{
	
	}
	
	function transCousrse($html)
	{
		preg_match_all("<a href='(.*?)'>", $html, $cou_urls);
		$cou_url = array_unique($cou_urls[1]);
		
		$format_course = [];
		foreach ($cou_url as $url) {
			$course_url = config('EduSysMobile_url') . "/KBCXGL/" . $url;
			
			$tmp_html = Curl::get($course_url, $this->cookies);
			
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
					"className" => $className,
					"time" => $time,
					"week" => $week,
					"teacher" => $teacher,
					"type" => 0,
					"location" => $location
				];
			}
		}
		return $format_course;
	}
	
	function transScore($html)
	{
		$re_info = array(
			'课程名' => 'class=\'fl\'>',
			'学分' => '学分：',
			'学年学期' => '学年学期：',
			'课程代码' => '课程代码：',
			'成绩' => '成绩：',
			'补考成绩' => '补考成绩：',
			'辅修标记' => '辅修标记：',
			'重修标记' => '重修标记：',
			'课程性质' => '课程性质：',
			'开课学院' => '开课学院：'
		);
		
		preg_match_all("#<li>(.*?)</li>#i", $html, $subs);
		$subjects = [];
		foreach ($subs[1] as $id => $sub) {
			foreach ($re_info as $key => $re) {
				preg_match("#$re(.*?)(\) )?<#i", $sub, $sub_info);
				$subjects[$id][$key] = $sub_info[1];
			}
		}
		return $subjects;
	}
	
	function transInfo($html)
	{
		$re_info = array(
			"姓名" => 'labxm',
			"性别" => 'labxb',
			"年级" => 'labdqszj',
			"出生日期" => 'labcsrq',
			"入学日期" => 'labrxrq',
			"来源省" => 'lablys',
			"民族" => 'labmz',
			"专业" => 'labzy',
			"行政班" => 'labxzb',
			"学历层次" => 'labcc',
			"学制" => 'labxz',
			"学院" => 'labxy',
			"身份证号" => 'labsfzh',
			"家庭住址" => 'lbjtszd'
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
	
	
	function getCourse($xh)
	{
//		echo $this->getUrl('course',$xh);
		if ($this->getCookies($xh)) {
			$location_url = config('EduSysMobile_url') . "/KBCXGL/xskcbcx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			return $this->transCousrse($html);
		} else return false;
	}
	
	public function getScore($xh)
	{

		if ($this->getCookies($xh)) {
			$location_url = config('EduSysMobile_url') . "/XSCJCX/xsdqcjcx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			return $this->transScore($html);
		} else return false;
	}
	
	public function getInfo($xh)
	{

		if ($this->getCookies($xh)) {
			$location_url = config('EduSysMobile_url') . "/XSXX/xsjbxx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			$this->transInfo($html);
		} else return false;
	}
	
	
	function getExam($xh)
	{
		echo $this->getUrl('exam', $xh);
		if ($this->getCookies($xh)) {
			$location_url = config('EduSysMobile_url') . "/KSCXGL/xskscx.aspx";
			$html = Curl::get($location_url, $this->cookies, $r_cookies, $r_code);
			print_r($html);
		} else return false;
	}
	
	
}