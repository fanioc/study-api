<?php

namespace app\xaufe\model;

use think\Model;

/**
 * EdySys用来处理教务数据库分发，整合网页教务和手机移动教务信息
 */
class EduSys extends Model
{
	protected $connection = [
		// 数据库表前缀
		'prefix' => 'edusys_',
	];
	
	static function loginMobile($xh, $pwd)
	{
		if (EduSysMobile::login($xh, $pwd))
			return true;
		else return false;
	}
	
	static function loginWeb($xh, $pwd, $checkCode, $cookies)
	{
		$web = new EduSysWeb();
		$re_data = $web->login($xh, $pwd, $checkCode, $cookies);
		//TODO::处理异常问题
		if (isset($re_data['errcode']))
			return false;
		return $re_data;
	}
	
	/**
	 * 返回当前学年学期信息
	 */
	static function getCurrentTerm()
	{
		$current_year = date('Y');
		$current_t = time() - 2 * 24 * 60 * 60;
		$t1 = strtotime("$current_year-08-30");
		if ($current_t < $t1) {
			$current_xn = ($current_year - 1) . '-' . $current_year;
		} else {
			$current_xn = $current_year . '-' . ($current_year + 1);
		}
		
		$xq1_star = strtotime(config('EduSysTermStarDate')[$current_xn][1]);
		$xq2_star = strtotime(config('EduSysTermStarDate')[$current_xn][2]);
		
		if ($current_t <= $xq2_star) {
			$current_xq = 1;
			if ($current_t <= $xq1_star) {
				$current_week = 1;
			} else {
				$tt = $current_t - $xq1_star;
				$current_week = (int)($tt / 60 / 60 / 24 / 7) + 1;
			}
		} else {
			$current_xq = 2;
			$tt = $current_t - $xq2_star;
			$current_week = (int)($tt / 60 / 60 / 24 / 7) + 1;
		}
		
		return ['xn' => $current_xn, 'xq' => $current_xq, 'week' => $current_week];
	}
	
	/**
	 * 获得验证码与web_cookies值
	 */
	static function getCheckCode()
	{
		$check = EduSysWeb::getCheckCode();
		//TODO::处理异常问题
		return $check;
	}
	
	/**
	 * @param null $date
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException+
	 */
	public function getFreeClass($date = null)
	{
		$re_data = $this->name('freeclass')->where('date', '=', $date)->select()->toArray();
		foreach ($re_data as &$data) {
			$data['jsh'] = json_decode($data['jsh']);
		}
		//TODO::处理异常问题
		return $re_data;
	}
	
	/**
	 * 更新数据库中未来2周的空课表（将会花费大量时间，平均更新一天30s,大约7分钟）
	 * @param $cookies
	 * @param $xh
	 */
	public function updateFreeClass($cookies, $xh)
	{
		//防止页面超时
		set_time_limit(0);
		//关闭浏览器后也可以继续更新
		ignore_user_abort(true);
		
		$web = new EduSysWeb();
		
		for ($i = 0; $i <= 14; $i++) {
			$day = date("Y-m-d", strtotime("+$i day"));
			$re_data[$day] = $web->getFreeClassDay($xh, $cookies, $day);
			
			$data = [];
			foreach ($re_data[$day] as $sjd => $lbs) {
				foreach ($lbs as $class) {
					$data[] = ['date' => $day,
						'sjd' => $sjd,
						'jxl' => $class['教学楼'],
						'xq' => $class['校区'],
						'jsh' => json_encode($class['教室号'], JSON_UNESCAPED_SLASHES),
						'update_time' => getCurrentTime()];
				}
			}
			
			$this->name('freeclass')->insertAll($data, true);
		}
		
		//TODO::处理异常问题
	}
	
	/**
	 * @param $xh
	 * @param $xn
	 * @param $xq
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getCourse($xh, $xn, $xq)
	{
		$course = $this->name('stu_course')->where('xh', '=', $xh)->where('xn', '=', $xn)->where('xq', '=', $xq)->select()->toArray();
		if (empty($course))
			return ['errCode' => 43]; //数据库内无课表数据，请尝试更新课表
		return $course;
	}
	
	/**
	 * @param $xh
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getInfo($xh)
	{
		$info = $this->name('stu_info')->where('xh', '=', $xh)->find()->toArray();
		if (empty($info))
			return ['errCode' => 42]; //数据库内无用户信息，请尝试更新
		return $info;
	}
	
	/**
	 * @param $xh
	 * @param $xn
	 * @param $xq
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getScore($xh, $xn, $xq)
	{
		$score = $this->name('stu_score')->where('xh', '=', $xh)->where('xn', '=', $xn)->where('xq', '=', $xq)->select()->toArray();
		
		if (empty($score))
			return ['errCode' => 41]; //数据库内无用户信息，请尝试更新
		return $score;
	}
	
	/**
	 * 更新数据库中有关该学号的所有信息
	 * @param $xh
	 * @return bool
	 */
	public function updateAll($xh)
	{
		$mobile = new EduSysMobile();
		$mobile->getCookies($xh);
		$stu_info = $mobile->getInfo($xh);
		$stu_info['xh'] = $xh;
		
		$stu_course = $mobile->getCourse($xh);
		foreach ($stu_course as &$item) {
			$item['time'] = json_encode($item['time'], JSON_UNESCAPED_SLASHES);
			$item['week'] = json_encode($item['week'], JSON_UNESCAPED_SLASHES);
			$item['xh'] = $xh;
		}
		
		
		$stu_score = $mobile->getScore($xh);
		foreach ($stu_score as &$item)
			$item['xh'] = $xh;
		
		$this->name('stu_info')->insert($stu_info, true);
		$this->name('stu_course')->insertAll($stu_course, true);
		$this->name('stu_score')->insertAll($stu_score, true);
		
		return true;
	}
	
	/**
	 * 更新数据库中的学生课表信息
	 * @param $xh
	 */
	public function updateCourse($xh)
	{
		//TODO::增加学年学期
		$mobile = new EduSysMobile();
		$mobile->getCookies($xh);
		$stu_course = $mobile->getCourse($xh);
		foreach ($stu_course as &$item) {
			$item['time'] = json_encode($item['time'], JSON_UNESCAPED_SLASHES);
			$item['week'] = json_encode($item['week'], JSON_UNESCAPED_SLASHES);
			$item['xh'] = $xh;
		}
		$this->name('stu_course')->insertAll($stu_course, true);
	}
	
	/**
	 * 更新数据库中的学生个人信息
	 * @param $xh
	 */
	public function updateInfo($xh)
	{
		$mobile = new EduSysMobile();
		$mobile->getCookies($xh);
		$stu_info = $mobile->getInfo($xh);
		$stu_info['xh'] = $xh;
		$this->name('stu_info')->insert($stu_info, true);
	}
	
	/**
	 * 更新数据库中的学生成绩信息
	 * @param $xh
	 */
	public function updateScore($xh)
	{
		//TODO::增加学年学期
		$mobile = new EduSysMobile();
		$stu_score = $mobile->getScore($xh);
		foreach ($stu_score as &$item)
			$item['xh'] = $xh;
		$this->name('stu_score')->insertAll($stu_score, true);
	}
	
}