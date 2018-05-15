<?php

namespace app\xaufe\controller;

use app\xaufe\model\StudyApp as StudyAppModel;
use think\Controller;

class StudyApp extends Controller
{
	/**
	 * 校验微信签名函数
	 * @param $signature
	 * @param $timestamp
	 * @param $nonce
	 * @return bool
	 */
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
	
	/**
	 * 用小程序发送来的一次性code换取openid，并返回自定登入态session
	 * @param $code
	 * @return string
	 */
	public function loginStudy($code)
	{
		$model = new StudyAppModel();
		
		return $model->loginStudy($code);
	}
	
	/**
	 * 绑定教务系统
	 * @param $session
	 * @param $xh
	 * @param $psd
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function bindEduSys($session, $xh, $psd)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re = $model->bindEduSys($re_info['uid'], $re_info['openId'], $xh, $psd);
		return $re;
	}
	
	/**
	 * 获取用户基本信息
	 * @param $session
	 * @param null $other_uid 如果$other_uid被赋值，则获取他人的信息，用于文章中个人信息的获取
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserBasicInfo($session, $other_uid = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		if ($other_uid == null)
			$re_info = $model->getUserBasicInfo($re_info['uid']);
		else $re_info = $model->getUserBasicInfo($other_uid);
		return $re_info;
	}
	
	/**
	 * $rawData,$encryData,$vi,$singure 必须在更新时附加
	 * 更新用户的基本信息，需要rawData
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
			
			$re_data = $model->updateUserBasicInfo($result['uid'], $result['openId'], $result['wx_session_key'], $rawData, $encryData, $vi, $singure);
			return $re_data;
		}
		
	}
	
	/**
	 * 更新session（暂时没用）
	 */
	public function updateSession($session)
	{
	
	}
	
	/**
	 * 获取用户的考试成绩
	 * @param $session
	 * @param null $xn
	 * @param null $xq
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserScore($session, $xn = null, $xq = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_score = $model->getUserEduScore($re_info['uid'], $xn, $xq);
		return $re_score;
	}
	
	public function getCurrentTerm(){
		return StudyAppModel::getCurrentTerm();
	}
	
	/**
	 * 获取用户的课表
	 * @param $session
	 * @param null $xn 学年
	 * @param null $xq 学期
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserCourse($session, $xn = null, $xq = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_course = $model->getUserEduCourse($re_info['uid'], $xn, $xq);
		return $re_course;
		
	}
	
	/**
	 * 获取用户教务中的个人信息
	 * @param $session
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getUserEduInfo($session)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_info = $model->getUserEduInfo($re_info['uid']);
		return $re_info;
	}
	
	/**
	 * 获取空课表
	 * @param $session
	 * @param null $date 格式：2018-05-20
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getEduFreeClass($session, $date = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$free_class = $model->getEduFreeClass($date);
		return $free_class;
	}
	
	/**
	 * 更新用户教务中的本学期的所有信息
	 * @param $session
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduAll($session)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_data = $model->updateUserEduAll($re_info['uid']);
		return $re_data;
	}
	
	/**
	 * 更新用户教务中的本学年的成绩信息
	 * @param $session
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduScore($session)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$EduScore = $model->updateUserEduScore($re_info['uid']);
		return $EduScore;
	}
	
	/**
	 * 更新用户教务中的本学期的课程信息
	 * @param $session
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduCourse($session)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$EduCourse = $model->updateUserEduCourse($re_info['uid']);
		return $EduCourse;
	}
	
	/**
	 * 更新用户教务中的学生的基本信息
	 * @param $session
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserEduInfo($session)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$EduInfo = $model->updateUserEduInfo($re_info['uid']);
		return $EduInfo;
	}
	
	//TODO::增加自定义课表部分
	//TODO::用户自定义信息部分，个性签名，切换头像，（用户认证） 展示已经学习xx次
	//TODO::增加用户StudyScore积分部分，增加积分，查看积分信息
	//TODO::增加用户设置部分，，隐私问题（是否展示成绩（80分以上的成绩），是否能让别人看到自己所在的班级，及个人信息）、是否接受学习邀请
	//TODO::增加匹配模型，匹配算法，发起预约请求，接受预约请求，成功预约提示
	//TODO::增加社区问答部分，拉取消息，对比消息是否更新，发表问答，回复问答，查看已发布主题，删除主题，删除回复
	//TODO::增加时间胶囊部分 添加胶囊，删除胶囊，胶囊倒计时提醒
	//TODO::增加番茄计划部分 添加计划，减少计划
	
}