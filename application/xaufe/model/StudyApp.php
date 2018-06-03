<?php

namespace app\xaufe\model;

use think\db;
use think\Model;

class StudyApp extends Model
{
	protected $connection = [
		// 数据库编码默认采用utf8
		'charset' => 'utf8',
		// 数据库表前缀
		'prefix' => 'study_',
	];
	
	/**
	 * @param $encryptedData
	 * @param $iv
	 * @param $sessionKey
	 * @param $appid
	 * @return array|string
	 */
	private function decryptData($encryptedData, $iv, $sessionKey, $appid)
	{
		if (strlen($sessionKey) != 24) {
			return ['errCode' => 2101];
		}
		$aesKey = base64_decode($sessionKey);
		
		if (strlen($iv) != 24) {
			return ['errCode' => 2102];
		}
		
		$aesIV = base64_decode($iv);
		$aesCipher = base64_decode($encryptedData);
		$result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
		
		$dataObj = json_decode($result, true);
		if ($dataObj == NULL) {
			return ['errCode' => 2103];
		}
		if ($dataObj['watermark']['appid'] != $appid) {
			return ['errCode' => 2104];
		}
		
		$data = $result;
		return $data;
	}
	
	public function loginStudy($code)
	{
		$url = config('StudyApp_sessionkey_url') . "&appid=" . config('StudyApp_appid') . '&secret=' . config('StudyApp_secret') . '&js_code=' . $code;
		$re_data = json_decode(file_get_contents($url), true);
		
		if (!empty($re_data['errcode'])) { //此errcode是微信后台发送过来的
			return ['errCode' => '2500' . $re_data['errcode']];
		} else {
			$openId = $re_data['openid'];
			$wx_session_key = $re_data['session_key'];
			
			$rand = '';
			for ($i = 0; $i < 8; $i++)
				$rand .= chr(mt_rand(33, 126));
			
			$session = md5($rand);
			$time = getCurrentTime();
			
			$sql = "INSERT INTO `study_session` (openId,session,wx_session_key)
					VALUES(?,?,?)
					  ON DUPLICATE KEY
					  UPDATE `session`=?,`wx_session_key`=?,`update_time`=?";
			Db::execute($sql, [$openId, $session, $wx_session_key, $session, $wx_session_key, $time]);
			
			return ['session' => $session];
		}
	}
	
	/**
	 * @param $uid
	 * @param $openId
	 * @param $xh
	 * @param $psd
	 * @param $check_code
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\exception\DbException
	 * @throws db\exception\DataNotFoundException
	 * @throws db\exception\ModelNotFoundException
	 */
	public function bindEduSys($uid, $openId, $xh, $psd, $check_code)
	{
		$cookies = $this->name('user_bind_cookies')->where('uid', '=', $uid)->find();
		if ($cookies == false)
			return ['errCode' => 3202];
		$cookies = $cookies->toArray()['web_cookies'];
		
		$re = EduSys::loginWeb($xh, $psd, $check_code, $cookies);
		if (!empty($re['errCode']))
			return $re;
		
		$this->name('user_bind')->insert(['uid' => $uid, 'openId' => $openId, 'bind_xh' => $xh, 'bind_psd' => $psd, 'bind_time' => getCurrentTime()], true);
		return true;
	}
	
	/**
	 * @param $uid
	 * @return array
	 * @throws \think\exception\DbException
	 * @throws db\exception\DataNotFoundException
	 * @throws db\exception\ModelNotFoundException
	 */
	public function getUserBasicInfo($uid)
	{
		$info = $this->name('user_info_basic')->where('uid', '=', $uid)->select()->toArray();
		if (empty($info))
			return ['errCode' => 3101];//没有信息，请求更新
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
				if (!empty($re_data['errCode']))
					return $re_data;
			} else return ['errCode', 3201];//非微信数据
		}
		
		$re_data['uid'] = $uid;
		$re_data['openId'] = $openId;
		
		$this->name('user_info_basic')->insert($re_data, true);
		
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
		$result = $this->name('session')->where('session', '=', $session)->find();
		if (!empty($result)) {
			$result = $result->toArray();
			return ['uid' => $result['uid'], 'openId' => $result['openId'], 'wx_session_key' => $result['wx_session_key']];
		} else return ['errCode' => 3102];//session不对
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
		$result = $this->name('user_bind')->where('uid', '=', $uid)->find()->toArray();
		if (!empty($result)) {
			return ['xh' => $result['bind_xh']];
		} else return ['errCode' => 3103];//没有绑定
	}
	
	/**
	 * ########################################
	 * 连接教务系统模块
	 * ########################################
	 */
	
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
		$edu = new EduSys();
		$class = $edu->getFreeClass($date);
		return $class;
	}
	
	
	/**
	 * @param $uid
	 * @return array|bool
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
			return $re_info;
		
		$edu = new EduSys();
		$edu->updateCourse($re_info['xh']);
		return true;
	}
	
	public function getCheckCode($uid)
	{
		$res = EduSys::getCheckCode();
		
		if (!empty($res['errCode']))
			return $res;
		
		$this->name('user_bind_cookies')->insert(['uid' => $uid, 'web_cookies' => $res['cookies'], 'web_uptime' => getCurrentTime()], true);
		return ['check_code' => $res['image']];
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
			return $re_info;
		
		if ($xn == null) {
			$term = EduSys::getCurrentTerm();
//			print_r($term);
			$xn = $term['xn'];
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
			return $re_info;
		
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
			return $re_info;
		
		$edu = new EduSys();
		return $edu->getInfo($re_info['xh']);
	}
	
	/**
	 * @param $uid
	 * @return bool|array
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
			return $re_info;
		
		$edu = new EduSys();
		$edu->updateScore($re_info['xh']);
		return true;
	}
	
	/**
	 * @param $uid
	 * @return bool|array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduInfo($uid)
	{
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$edu = new EduSys();
		$edu->updateInfo($re_info['xh']);
		return true;
	}
	
	/**
	 * 更新当前学期的所有
	 * @param $uid
	 * @return bool|array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduAll($uid)
	{
		$re_info = $this->checkUserBind($uid);
		
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$edu = new EduSys();
		$edu->updateAll($re_info['xh']);
		return true;
	}
	
	
	/**
	 * ########################################
	 * 自定义课表模块
	 * ########################################
	 */
	
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
	
	public function delUserCustomCourse($uid, $course_id)
	{
	
	}
	
	public function getUserCustomCourse($uid)
	{
	
	}
	
	
	/**
	 * ########################################
	 * 预约自习模块
	 * ########################################
	 */
	
	/**
	 * 获取用户study_score的详细信息
	 * @param $uid
	 */
	public function getUserStudyScore($uid)
	{
	
	}
	
	/**
	 * 增加study_score
	 * @param $uid
	 * @param $type
	 * @param $study_score
	 * @param $remakes
	 */
	public function addUserStudyScore($uid, $type, $study_score, $remakes)
	{
	
	}
	
	public function sendMsg($uid, $to_uid, $content)
	{
	
	}
	
	public function getMsg($uid, $other_uid)
	{
	
	}
	
	public function leaveMessage($uid, $content)
	{
	
	}
	
	//发送用户邀请
	public function sendInvite($uid, $openId, $other_uid, $time, $msg = null)
	{
	
	}
	
	
	/**
	 * ########################################
	 * 社区模块
	 * ########################################
	 */
	
	/**
	 * @param $uid
	 * @param $title
	 * @param $img_url
	 * @param $content
	 * @param $type
	 * @param null $sort
	 * @return array
	 */
	public function publishDynamic($uid, $title, $img_url, $content, $type, $sort = null)
	{
		$result = $this->name('dynamic_Q')->insert(['publish_uid' => $uid, 'title' => $title, 'content' => $content, 'sort' => $sort, 'img_url' => $img_url, 'time' => getCurrentTime(), 'type' => $type]);
		if ($result == 1)
			return ['errMsg' => '发表成功'];
		else return ['errCode' => 3501];
	}
	
	/**
	 * @param null $last_id
	 * @return array|mixed
	 * @throws \think\exception\PDOException
	 * @throws db\exception\BindParamException
	 */
	public function getDynamicList($last_id = null)
	{
		if ($last_id == null)
			$add = 'AND dynamic_id<' . $last_id;
		else $add = '';
		
		$sql = "SELECT
					dynamic_id,publish_uid,title,sort,time,img_url,type,
					SUBSTRING(study_dynamic_Q.content,1,20)content,
					(SELECT count(dynamic_id) FROM study_dynamic_A WHERE study_dynamic_A.dynamic_id = study_dynamic_Q.dynamic_id)ans_num
				FROM
					study_dynamic_Q
				WHERE
				type >= 1
				$add
				ORDER BY
					dynamic_id
				LIMIT 10";
		
		$result = $this->query($sql);
		
		if ($result != false)
			return $result;
		else return ['errCode' => 3504];
	}
	
	public function AnswerDynamic($uid, $dynamic_id, $content, $type)
	{
		$result = $this->name('dynamic_A')->insert(['dynamic_id' => $dynamic_id, 'answer' => $uid, 'content' => $content, 'time' => getCurrentTime(), 'type' => $type]);
		if ($result == 1)
			return ['errMsg' => '回答成功'];
		else return ['errCode' => 3501];
	}
	
	public function delDynamicAnswer($uid, $dynamic_id, $answer_id)
	{
		$result = $this->name('dynamic_A')->where('dynamic_id', '=', $dynamic_id)->where('answer_id', '=', $answer_id)->where('answer', '=', $uid)->update(['type' => 0]);
		if ($result == 1)
			return ['errMsg' => '删除成功'];
		else return ['errCode' => 3502];
	}
	
	public function setDynamicAgree($uid, $dynamic_id, $answer_id, $agree)
	{
		$result = $this->name('dynamic_agree')->insert(['dynamic_id' => $dynamic_id, 'answer_id' => $answer_id, 'agree' => $agree, 'user_uid' => $uid, 'time' => getCurrentTime()], true);
		if ($result == 1) {
			if ($agree == 0)
				return ['errMsg' => '不赞同'];
			else return ['errMsg' => '赞同'];
		} else return ['errCode' => 3503];
	}
	
	/**
	 * 返回一个单独动态的内容，回答列表
	 * @param $dynamic_id
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 * @throws db\exception\BindParamException
	 * @throws db\exception\DataNotFoundException
	 * @throws db\exception\ModelNotFoundException
	 */
	public function getDynamicContent($dynamic_id)
	{
		$quest = $this->name('dynamic_Q')->where('dynamic_id', '=', $dynamic_id)->where('type>=1')->find();
		if ($quest==false)
			return ['errCode'=>3505];
		$sql = "SELECT
					dynamic_id,answer_id,time,
					SUBSTRING( content, 1, 20 )content,
					( SELECT count( * ) FROM study_dynamic_agree WHERE Q_id = A.dynamic_id AND A_id = A.answer_id AND agree = 1 ) agree_num
				FROM
					study_dynamic_A A
				WHERE
					type >= 1
					AND dynamic_id = ?
				ORDER BY
					dynamic_id";
		$ans = $this->query($sql,[$dynamic_id]);
		if ($ans == false){
			return['dynamic'=>$quest,'ans_errCode'=>3506];
		} return['dynamic'=>$quest->toArray(),'ans_list'=>$ans];
	}
	
	/**
	 * 获取问题内容，以及是否赞同和赞同数量
	 * @param $uid
	 * @param $dynamic_id
	 * @param $answer_id
	 * @return array|mixed
	 * @throws \think\exception\PDOException
	 * @throws db\exception\BindParamException
	 */
	public function getDynamicAns($uid,$dynamic_id,$answer_id){
		$sql="SELECT
					*,
					(SELECT count(*)FROM study_dynamic_agree WHERE Q_id=A.dynamic_id AND A_id = A.answer_id AND agree = 1)agree_num,
					(SELECT agree FROM study_dynamic_agree WHERE Q_id=A.dynamic_id AND A_id = A.answer_id AND user_uid=?)is_agree
				FROM
					study_dynamic_A A
				WHERE
					dynamic_id = ?
					AND answer_id =?";
		$result = $this->query($sql,[$uid,$dynamic_id,$answer_id]);
		if ($result == false)
			return  ['errCode' => 3507];
		else return $result;
	}
	
	public function delDynamic($uid, $dynamic_id)
	{
		$result = $this->name('dynamic_Q')->where('dynamic_id', '=', $dynamic_id)->where('publish_uid', '=', $uid)->update(['type' => 0]);
		if ($result == 1)
			return ['errMsg' => '删除成功'];
		else return ['errCode' => 3508];
	}
	
	/**
	 * ########################################
	 * 社区模块
	 * ########################################
	 */
	
}