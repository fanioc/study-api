<?php

namespace app\xaufe\model;

use think\Model;
use org\util\Curl;

class StudyApp extends Model
{
	protected $connection = [
		
		// 数据库编码默认采用utf8
		'charset' => 'utf8',
		// 数据库表前缀
		'prefix' => 'study_',
	];
	
	public function login($code)
	{
		$url = config('StudyApp_sessionkey_url') . "&appid=" . config('StudyApp_appid') . '&secret=' . config('StudyApp_secret') . '&js_code=' . $code;
		$re_data = json_decode(file_get_contents($url), true);
		
		if (!empty($re_data['errcode'])) {
			return $re_data;
		} else {
			$openId = $re_data['openid'];
			$wx_session_key = $re_data['session_key'];
			
			$rand = '';
			for ($i = 0; $i < 8; $i++)
				$rand .= chr(mt_rand(33, 126));
			
			$session = md5($rand);
			
			$this->name('session')->insert(['openId' => $openId, 'session' => $session, 'wx_session_key' => $wx_session_key, 'update_time' => date('Y-m-d H:i:s', time())], true);
			
			return ['session' => $session];
		}
	}
	
	public function getUserBasicInfo($session)
	{
	
	}
	
	private function decryptData($encryptedData, $iv, $sessionKey, $appid)
	{
		if (strlen($sessionKey) != 24) {
			return ['errCode' => 41001];
		}
		$aesKey = base64_decode($sessionKey);
		
		if (strlen($iv) != 24) {
			return ['errCode' => 41002];
		}
		$aesIV = base64_decode($iv);
		
		$aesCipher = base64_decode($encryptedData);
		
		$result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
		
		$dataObj = json_decode($result, true);
		if ($dataObj == NULL) {
			return ['errCode' => 41003];
		}
		if ($dataObj['watermark']['appid'] != $appid) {
			return ['errCode' => 41003];
		}
		$data = $result;
		return $data;
	}
	
	
	public function updateUserBasicInfo($openId, $wx_session_key, $rawData, $encryptedData = null, $vi = null, $signature = null)
	{
		if ($encryptedData == null) {
			//将rawData数据写入数据库
			$re_data = json_decode($rawData, true);
			$re_data['openId'] = $openId;
		} else {
			//从微信服务器获取数据写入数据库
			$signature2 = sha1($rawData . $wx_session_key);
			if ($signature2 == $signature) {
				$re_data = json_decode($this->decryptData($encryptedData, $vi, $wx_session_key, config('StudyApp_appid')),true);
			} else return ['errCode', 5066];//非微信数据
		}
		$this->name('user_basic_info')->insert($re_data,true);
		return true;
	}
	
	/**
	 * @param $session
	 * @return array 成功返回 openId 和 wx_session_key
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function checkSession($session)
	{
		$result = $this->name('session')->where('session', '=', $session)->find()->toArray();
		if (!empty($result)) {
			return ['openId' => $result['openId'], 'wx_session_key' => $result['wx_session_key']];
		} else return ['errCode' => '4021'];//session不对
		
	}
	
	public function getUserCourse($session)
	{
	
	}
	
	public function getFreeClass($session)
	{
	
	}
	
	public function addUserCourse($session)
	{
	
	}
	
	public function delUserCourse($session)
	{
	
	}
	
	public function getUserScore($session)
	{
	
	}
	
	public function updateUserScore($session)
	{
	
	}
	
	public function getUserRecommend($session)
	{
	
	}
	
	public function addUserScore()
	{
	
	}
}