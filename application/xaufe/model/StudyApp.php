<?php

namespace app\xaufe\model;

use think\Model;

class StudyApp extends Model
{
	protected $connection = [
		// 数据库编码默认采用utf8
		'charset' => 'utf8',
		// 数据库表前缀
		'prefix' => 'study_',
	];
	
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
	
	public function loginStudy($code)
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
			
			$this->name('session')->insert(['openId' => $openId, 'session' => $session, 'wx_session_key' => $wx_session_key, 'update_time' => getCurrentTime()], true);
			//TODO::解决id列增加的问题
			return ['session' => $session];
		}
	}
	
	public function bindEduSys($uid, $openId, $xh, $psd)
	{
		if (EduSys::loginMobile($xh, $psd)) {
			$this->name('user_bind')->insert(['uid' => $uid, 'openId' => $openId, 'bind_xh' => $xh, 'bind_psd' => $psd, 'bind_time' => getCurrentTime()], true);
			return true;
		} else return false;
	}
	
	/**
	 * @param $uid
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserBasicInfo($uid)
	{
		$info = $this->name('user_basic_info')->where('uid', '=', $uid)->select()->toArray();
		if (empty($info))
			return ['errCode' => 235];//没有信息，请求更新
		else return $info;
	}
	
	/**
	 * @param $uid
	 * @param $openId
	 * @param $wx_session_key
	 * @param $rawData
	 * @param null $encryptedData
	 * @param null $vi
	 * @param null $signature
	 * @return array|bool
	 */
	public function updateUserBasicInfo($uid, $openId, $wx_session_key, $rawData, $encryptedData = null, $vi = null, $signature = null)
	{
		if ($encryptedData == null) {
			//将rawData数据写入数据库
			$re_data = json_decode($rawData, true);
		} else {
			//从微信服务器获取数据写入数据库
			$signature2 = sha1($rawData . $wx_session_key);
			if ($signature2 == $signature) {
				$re_data = json_decode($this->decryptData($encryptedData, $vi, $wx_session_key, config('StudyApp_appid')), true);
			} else return ['errCode', 5066];//非微信数据
		}
		
		$re_data['uid'] = $uid;
		$re_data['openId'] = $openId;
		
		$this->name('user_basic_info')->insert($re_data, true);
		//TODO::解决id列增加的问题，改replace 为 unique key update
		return true;
	}
	
	/**
	 * 返回的openId在控制器中进行处理
	 * @param $session
	 * @return array 成功返回 openId 和 wx_session_key
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function checkSession($session)
	{
		$result = $this->name('session')->where('session', '=', $session)->select()->toArray();
		if (!empty($result)) {
			return ['uid' => $result['uid'], 'openId' => $result['openId'], 'wx_session_key' => $result['wx_session_key']];
		} else return ['errCode' => '4021'];//session不对
		
	}
	
	/**
	 * 在模型中读取教务信息时凭openid获取对应学号
	 * @param $uid
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function checkUserBind($uid)
	{
		$result = $this->name('user_bind')->where('uid', '=', $uid)->select()->toArray();
		if (!empty($result)) {
			return ['xh' => $result['bind_xh']];
		} else return ['errCode' => 324];//没有绑定
	}
	
	/**
	 * 获取空教室信息
	 * @param null $date
	 * @return array $class
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getEduFreeClass($date = null)
	{
		if ($date == null)
			$date = date('Y-m-d',time());
		
		$edu = new EduSys();
		$class = $edu->getFreeClass($date);
		return $class;
	}
	
	/**
	 * 读取数据库中用户自定义的课程和教务系统数据库中的课程，两者相加
	 * @param $uid
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserAllCourse($uid)
	{
		$re_info = $this->checkUserBind($uid);
		
		
		//TODO::获取所有课程叠加
	}
	
	public function addUserCustomCourse($uid, $openId, $course)
	{
	
	}
	
	public function delUserCustomCourse($uid, $course)
	{
	
	}
	
	public function getUserCustomCourse($uid)
	{
	
	}
	
	/**
	 * @param $uid
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduCourse($uid, $xn = null, $xq = null)
	{
		//TODO::增加学年学期选项
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return false;
		
		$edu = new EduSys();
		$edu->updateCourse($re_info['xh']);
		return true;
	}
	
	static function getCurrentTerm()
	{
		return EduSys::getCurrentTerm();
	}
	
	/**
	 * @param $uid
	 * @param null $xn
	 * @param null $xq
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserEduScore($uid, $xn = null, $xq = null)
	{
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return false;
		
		if ($xn == null) {
			$term = EduSys::getCurrentTerm();
			$xn = $term['xh'];
			$xq = $term['xq'];
		}
		
		$edu = new EduSys();
		return $edu->getScore($re_info['xh'], $xn, $xq);
	}
	
	/**
	 * @param $uid
	 * @param $xn
	 * @param $xq
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserEduCourse($uid, $xn = null, $xq = null)
	{
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return false;
		
		if ($xn == null) {
			$term = EduSys::getCurrentTerm();
			$xn = $term['xh'];
			$xq = $term['xq'];
		}
		
		$edu = new EduSys();
		return $edu->getCourse($re_info['xh'], $xn, $xq);
	}
	
	/**
	 * @param $uid
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserEduInfo($uid)
	{
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return false;
		
		$edu = new EduSys();
		return $edu->getInfo($re_info['xh']);
	}
	
	/**
	 * @param $uid
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduScore($uid, $xn = null, $xq = null)
	{
		//TODO::增加学年学期选项
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return false;
		
		$edu = new EduSys();
		$edu->updateScore($re_info['xh']);
		return true;
	}
	
	/**
	 * @param $uid
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduInfo($uid)
	{
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return false;
		
		$edu = new EduSys();
		$edu->updateInfo($re_info['xh']);
		return true;
	}
	
	/**
	 * 更新当前学期的所有
	 * @param $uid
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduAll($uid)
	{
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return false;
		
		$edu = new EduSys();
		$edu->updateAll($re_info['xh']);
		return true;
	}
	
	/**
	 * 获取用户study_score的详细信息
	 * @param $uid
	 */
	public function getUserStudyScore($uid)
	{
	
	}
	
	/**
	 * 增加study_score
	 * @param $openId
	 * @param $type
	 * @param $study_socre
	 * @param $remakes
	 */
	public function addUserStudyScore($uid, $openId, $type, $study_socre, $remakes)
	{
	
	}
	
	
}