<?php

namespace app\xaufe\controller;

use app\xaufe\model\EduSysWeb as EduSysWebModel;
use think\Controller;

class EduSysWeb extends Controller
{
	protected $beforeActionList = [
	//		'check'
	];
	
	protected function check()
	{
		if ($this->request->param('time') != '10'){
			echo "404";
			$this->error(404);
		}
	}
	
	function index()
	{
		return "hello";
	}
	
	function getCheckCode()
	{
		$model = new EduSysWebModel;
		$check_code = $model->getCheckCode();
		echo "<img src=\"".$check_code['image']."\" />";
		return $check_code['cookies'];
	}
	
	function loginSys()
	{
		$model = new EduSysWebModel;
		$out = $model->login($this->request->param('xh'),$this->request->param('psw'),$this->request->param('code'),$this->request->param('cookie'));
		
		return $out;
	}
	
	function getInfo()
	{
	
	}
	
	function getCourse()
	{
	
	}
	
	function getFreeCourse()
	{
	
	}
	
	function getScore()
	{
	
	}
	
}