<?php

namespace app\xaufe\controller;

use think\Controller;

class StudyApp extends Controller
{
	/**
	 * 第三方入口
	 */
	public function star()
	{
	
	}
	
	/**用小程序发送来的一次性code换取openid，并返回自定登入态NoBug
	 * @param $code
	 * @return string
	 */
	public function login($code)
	{
		$session='';
		
		return $session;
	}
	
	public function bindEduSys($session,$xh,$psw,$checkCode)
	{
	
	}
	
	public function getEdusysCheckCode($session)
	{
	
	}
	
	public function getUserInfo($session,$lb)
	{
	
	}
	
	public function uopdateSession()
	{
	
	}
}