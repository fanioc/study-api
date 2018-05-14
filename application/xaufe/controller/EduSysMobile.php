<?php

namespace app\xaufe\controller;

use app\xaufe\model\EduSysMobile as EduSysMobileModel;
use think\Controller;

class EduSysMobile extends Controller
{
	public function index()
	{
		return "hello";
	}
	
	function loginSys($xh,$psw)
	{
//		$model = new EduSysMobileModel;
	}
	
	function getInfo($xh)
	{
		$model = new EduSysMobileModel;
		return $model->getInfo($xh);
	}
	
	function getCourse($xh)
	{
		$model = new EduSysMobileModel;
		return $model->getCourse($xh);
	}
	
	function getScore($xh)
	{
		$model = new EduSysMobileModel;
		return $model->getScore($xh);
	}
	
	function getExam($xh)
	{
		$model = new EduSysMobileModel;
		return $model->getExam($xh);
	}
	
}