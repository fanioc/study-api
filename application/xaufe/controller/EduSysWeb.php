<?php

namespace app\xaufe\controller;

use app\xaufe\model\EduSysWeb as EduSysWebModel;
use think\Controller;

class EduSysWeb extends Controller
{
	
	public function index()
	{

	}
	
	public function getCheckCode()
	{
		$model = new EduSysWebModel;
		$check_code = $model->getCheckCode();
		return $check_code;
	}
	
	public function loginSys($xh, $psw, $checkCode, $cookies)
	{
		$model = new EduSysWebModel;
		$out = $model->login($xh, $psw, $checkCode, $cookies);
		return $out;
	}
	
	
	public function getInfo()
	{
	
	}
	
	public function getCourse($xh, $cookies)
	{
		$model = new EduSysWebModel;
		$course = $model->getCourse($xh, $cookies);
		return $course;
		
	}
	
	public function getFreeClass($xh, $cookies)
	{
		$model = new EduSysWebModel;
		$freeclass = $model->getFreeClassDay($xh, $cookies,date('Y-m-d'));
		return $freeclass;
	}
	
	public function getScore()
	{
	
	}
	
	function getExam()
	{
	
	}
	
}