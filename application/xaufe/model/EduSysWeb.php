<?php

namespace app\xaufe\model;

use org\util\Curl;
use think\Model;

class EduSysWeb extends Model
{
	//一次实例化对象登入，可以连续进行后续操作
	public $xh;
	public $cookies;
	
	/**
	 * @param $html
	 * @return array
	 */
	private function getViewState($html)
	{
		preg_match_all('#<input type="hidden" name="(.*?)" value="(.*?)" />#i', $html, $result);
		
		$view_state = [];
		foreach ($result[1] as $key => $name) {
			$view_state[$name] = $result[2][$key];
		}
		
		return $view_state;
	}
	
	/** 将抓取的数据转换为可输出的数据格式
	 * @param $html
	 * @return array
	 */
	private function transCourse($html)
	{
		preg_match_all('/<td align="Center" rowspan="\d".*?>(.*?)<\/td>/i', $html, $courses);
		
		$format_course = array();
		foreach ($courses[1] as $course) {
			
			$couInfos = explode('<br><br>', $course);
			
			foreach ($couInfos as $couInfo) {
				$couInfo = explode('<br>', $couInfo);
				
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
				
				$format_course[] =
					["className" => $couInfo[0],
						"time" => $time,
						"week" => $week,
						"teacher" => $couInfo[2],
						"type" => 0,
						"location" => $couInfo[3]];
			}
		}
		
		return $format_course;
	}
	
	/**
	 * @return array|string
	 */
	static public function getCheckCode()
	{
		$check_code_url = config('EduSysWeb_url') . '/CheckCode.aspx';
		$image = Curl::get($check_code_url, null, $r_cookies, $r_code);
		if ($r_code == '200')
			return ['image' => 'data:image/gif;base64,' . base64_encode($image), 'cookies' => $r_cookies];
		else return ['errCode' => 32 + $r_code];
	}
	
	/**
	 * @param $xh
	 * @param $stu_psw
	 * @param $check_code
	 * @param $cookies
	 * @return array
	 */
	public function login($xh, $stu_psw, $check_code, $cookies)
	{
		$this->xh = $xh;
		$this->cookies = $cookies;
		
		$login_url = config('EduSysWeb_url') . '/Default2.aspx';
		$post_data = "__VIEWSTATE=dDwtNTE2MjI4MTQ7Oz681Ec4muNvkMixSRph9FM75zxqfQ%3D%3D&txtUserName=" . $this->xh . "&Textbox1=&TextBox2=" . $stu_psw . "&txtSecretCode=" . $check_code . "&RadioButtonList1=%D1%A7%C9%FA&Button1=&lbLanguage=&hidPdrs=&hidsc=";
		$output = Curl::post($login_url, $post_data, $this->cookies, $r_cookies, $r_code, null, $r_header, 10);
		$output = iconv("gbk", "utf-8//ignore", $output);  //编码转换
		
		if ($r_code == '302' && strpos($r_header, 'Location: /xs_main.aspx?xh=')) {  //判断是否重定向到正确的登入页面
			
			return ['name' => $this->loginMain()];
			
		} else {
			preg_match('/defer>alert\(\'(.*)\'\);document/i', $output, $error); //获取错误信息
			return ['errCode' => 3300, 'errMsg' => $error[1]];
		}
	}
	
	/**
	 * @param null $xh
	 * @param null $cookies
	 * @return string
	 */
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
		
		if ($r_code == 200) {
			preg_match("#<span id=\"xhxm\">(.*)</span>#i", $output, $name); //登入成功后获取名字
			return $name[1];
		} else return 'error:404';
		
	}
	
	/**
	 * @param null $xh
	 * @param null $cookies
	 */
	public function getInfo($xh = null, $cookies = null)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
	}
	
	/**
	 * @param null $xh
	 * @param null $cookies
	 * @return array|string
	 */
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
			$course = $this->transCourse($html);
			return $course;
		} else {
			return 'error:' . $r_code;
		}
	}
	
	/**
	 * @param null $xh
	 * @param null $cookies
	 * @param $day
	 * @return array
	 */
	public function getFreeClassDay($xh = null, $cookies = null, $day)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
		$url = config('EduSysWeb_url') . '/xxjsjy.aspx?xh=' . $this->xh . '&gnmkdm=N121611';
		$header = ['Referer: ' . config('EduSysWeb_url') . '/xs_main.aspx?xh=' . $this->xh];
		
		$html = Curl::get($url, $this->cookies, $r_cookes, $r_code, $header);
		$view1 = $this->getViewState($html);
//		print_r($html);
		
		$sjds = array(
			"'1'|'1','0','0','0','0','0','0','0','0'",
			"'2'|'0','3','0','0','0','0','0','0','0'",
			"'3'|'0','0','5','0','0','0','0','0','0'",
			"'4'|'0','0','0','7','0','0','0','0','0'",
			"'5'|'0','0','0','0','9','0','0','0','0'",
			"'6'|'0','0','0','0','0','11','0','0','0'"
		);
		
		preg_match_all('#<option.*?value="(\d{3})">(.*?)</option>#i', $html, $int_day);
		
		$days = [];
		foreach ($int_day[2] as $key => $date) {
			if (isset($days[$int_day[1][$key]])) break;
			$days[$date] = $int_day[1][$key];
		}
		
		// 协议头中必须包含Referer
		$header = ['Referer:' . $url];
		
		$free_class = [];
		//循环每时间段的数据
		foreach ($sjds as $sjd_key => $sjd) {
			$post_data = [
				'__EVENTTARGET' => $view1['__EVENTTARGET'],
				'__EVENTARGUMENT' => $view1['__EVENTARGUMENT'],
				'__VIEWSTATE' => $view1['__VIEWSTATE'],
				'kssj' => $days[$day],
				'jssj' => $days[$day],
				'sjd' => $sjd,
				'Button2' => '空教室查询'
			];
			
			$html = Curl::post($url, $post_data, $this->cookies, $r_cookes, $r_code, $header);
			$html = iconv("gbk", "utf-8//ignore", $html);
			
			//循环记录页数
			$jxl = [];//防止教室号重复
			$pages = getSubstr($html, "dpDataGrid1_lblTotalRecords\">", "</span>条记录，每页显示");
			
			if ($pages % 200) $pages = (int)($pages / 200) + 1;
			else $pages = (int)($pages / 200);
			
			for ($current_page = 1; $current_page <= $pages; $current_page++) {
				
				$view2 = $this->getViewState($html);
				
				if ($current_page == 1) {
					$type = ['Button2' => '空教室查询', '__EVENTTARGET' => 'dpDataGrid1:txtPageSize'];
				} else $type = ['dpDataGrid1:btnNextPage' => '下一页', '__EVENTTARGET' => ''];
				
				//2018-05-12(第11周)至2018-05-12(第11周)中 单周 星期六 第1,2节 有空的教室
//				$title = getSubstr($html, "class=\"button\" /><br><span id=\"lblbt\">", "</span>");
				
				$post_data_page = [
						'__EVENTARGUMENT' => '',
						'__VIEWSTATE' => $view2['__VIEWSTATE'],
						'kssj' => $days[$day],
						'jssj' => $days[$day],
						'sjd' => $sjd,
						'dpDataGrid1:txtChoosePage' => $current_page > 1 ? $current_page - 1 : $current_page,
						'dpDataGrid1:txtPageSize' => '200',
					] + $type;
				
				$html = Curl::post($url, $post_data_page, $this->cookies, $r_cookes, $r_code, $header);
				$html = iconv("gbk", "utf-8//ignore", $html);
				
				preg_match_all('#<td>(\d{3,})</td><td>(.*?教学楼)(.*?)</td><td>(.*?)</td><td>(.*?)</td><td>(.*?)</td><td>(.*?)</td><td>(.*?)</td><td>(.*?)</td>#i', $html, $lbs);
				
				/**
				 * 合并教室号
				 */
				foreach ($lbs[2] as $index => $Name) {
					$jxl[$Name][] = $lbs[3][$index];
					$xq[$Name] = $lbs[5][$index];
				}
				
				/**
				 * 以教学楼名称为键值
				 */
				foreach ($jxl as $jxlName => $jsh) {
					$free_class[$sjd_key + 1][] = [
//						'教室编号' => $lbs[1][$index],
						'教学楼' => $jxlName,
						'教室号' => $jsh,
//						'教室类别' => $lbs[4][$index],
						'校区' => $xq[$jxlName]
//						'座位数' => $lbs[6][$index]
					];
				}
			}
			//页数循环结束
			
		}
		
		/**
		 * array(1=>[['教学楼'=>1号教学楼,'教室号'=>...,'校区'=>...],],2=>['教学楼'=>2号教学楼,'教室号'=>...,'校区'=>...]...)
		 */

//		print_r($free_class);
		
		return $free_class;
	}
	
	/**
	 * @param null $xh
	 * @param null $cookies
	 */
	public function getScore($xh = null, $cookies = null)
	{
		if ($xh != null)
			$this->xh = $xh;
		if ($cookies != null)
			$this->cookies = $cookies;
		
	}
	
}