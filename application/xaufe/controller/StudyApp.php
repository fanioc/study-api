<?php

namespace app\xaufe\controller;

use app\xaufe\model\StudyApp as StudyAppModel;
use think\Controller;

class StudyApp extends Controller
{
	function checkSignature($signature, $timestamp, $nonce)
	{
		$token = config('StudyApp_mpToken');
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		
		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 第三方入口
	 */
	public function star()
	{
		$signature = request()->get('signature');
		$timestamp = request()->get('timestamp');
		$nonce = request()->get('nonce');
		$echostr = request()->get('echostr');
		
		if ($this->checkSignature($signature, $timestamp, $nonce))
			echo $echostr;
	}
	
	/**用小程序发送来的一次性code换取openid，并返回自定登入态NoBug
	 * @param $code
	 * @return string
	 */
	public function login($code)
	{
//		$code = request()->post('code');
		$model = new StudyAppModel();
		
		return $model->login($code);
	}
	
	public function bindEduSys($session, $xh, $psw, $checkCode)
	{
	
	}
	
	public function getEdusysCheckCode($session)
	{
	
	}
	
	public function getUserBasicInfo($session)
	{
		$model = new StudyAppModel();
		
	}
	
	/**
	 * @param $session
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserBasicInfo($session)
	{
		$model = new StudyAppModel();
		$result = $model->checkSession($session);
		if (!empty($result['errCode'])) {
			return $result;
		} else {
			$rawData = request()->get('rawData');
			$encryData = request()->get('encryData');
			$vi = request()->get('vi');
			$singure = request()->get('singure');
			
			$re_data = $model->updateUserBasicInfo($result['openId'], $result['wx_session_key'], $rawData, $encryData, $vi, $singure);
			
			return $re_data;
		}
		
	}
	
	public function updateSession()
	{
	
	}
}