<?php

namespace app\xaufe\controller;

use app\xaufe\model\EduSysWeb as EduSysWebModel;
use think\Controller;

class EduSysWeb extends Controller
{
	protected $beforeActionList = [
//			'check'
	];
	
	protected function check()
	{
		if ($this->request->param('time') != '10'){
			echo "404";
			$this->error(404);
		}
	}
	
	protected function formatData($code=0,$data)
	{
		return ['code'=>$code,'data'=>$data];
	}
	
	public function index()
	{
		$this->error('sadfa');
	}
	
	public function getCheckCode()
	{
		$model = new EduSysWebModel;
		$check_code = $model->getCheckCode();
//		echo "<img src=\"".$check_code['image']."\" />";
		return $this->formatData(2,$check_code);
	}
	
	public function loginSys($xh,$psw,$checkCode,$cookies)
	{
		$model = new EduSysWebModel;
		$out = $model->login($xh,$psw,$checkCode,$cookies);
//		echo $out;
		$course = $model->getCourse();
		return $course;
	}
	
	public function getInfo()
	{
	
	}
	
	public function getCourse()
	{
	
	}
	
	public function getFreeCourse()
	{
	
	}
	
	public function getScore()
	{
	
	}
	
}