<?php

namespace app\xaufe\controller;

use app\xaufe\model\EduSysMobile as EduSysMobileModel;
use think\Controller;

class EduSysMobile extends Controller
{
	protected $beforeActionList = [ //前置操作
		'check' //检查api接口是否可以访问
	];
	
	protected function check() //检查api接口是否可以访问
	{
		$sign = md5($this->request->param('uid') . config('check_encrypt') . $this->request->param('time'));
	}
	
	public function index()
	{
		return "hello";
	}
	
	function loginSys()
	{
		$model = new EduSysMobileModel;
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