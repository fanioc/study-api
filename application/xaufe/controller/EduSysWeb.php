<?php

namespace app\xaufe\controller;

use app\xaufe\model\EduSysWeb as EduSysWebModel;
use think\Controller;

class EduSysWeb extends Controller
{
	protected $beforeActionList = [
//			'check'
	];
	
	protected function check()  //前置校验操作
	{
		if ($this->request->param('time') != '10') {
			echo "404";
			$this->error(404);
		}
	}
	
	protected function formatData($code = 0, $data)
	{
		return ['code' => $code, 'data' => $data];
	}
	
	public function index()
	{

	}
	
	public function getCheckCode()
	{
		$model = new EduSysWebModel;
		$check_code = $model->getCheckCode();
		
		return $this->formatData(2, $check_code);
	}
	
	public function loginSys($xh, $psw, $checkCode, $cookies)
	{
		$model = new EduSysWebModel;
		$out = $model->login($xh, $psw, $checkCode, $cookies);
		
		return $this->formatData(0,$out);
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
		$freeclass = $model->getFreeClass($xh, $cookies);
		return $freeclass;

	}
	
	public function getScore()
	{
	
	}
	
	function getExam($xh)
	{
	
	}
	
}