<?php

namespace app\xaufe\controller;

use app\xaufe\model\StudyApp as StudyAppModel;
use think\Controller;

class StudyApp extends Controller
{
	private function afterDatafomat($data)
	{
		if (!empty($data['errCode']))
			return $data;
		else return ['errCode' => 0, 'data' => $data];
	}
	
	public function fileUpload()
	{
		//判断文件上传是否出错
		if ($_FILES["file"]["error"]) {
			return ['errCode' => 3401, 'errMsg' => $_FILES["file"]["erroe"]];
		} else {
			//控制上传的文件类型，大小
			if ($_FILES["file"]["size"] < 5242880)//5mb内
			{
				//找到文件存放位置，注意tp5框架的相对路径前面不用/
				//这里的filename进行了拼接，前面是路径，后面从date开始是文件名
				//我在static文件下新建了一个file文件用来存放文件，要注意自己建一个文件才能存放传过来的文件
				$filename = "static/upLoadFile/" . date("YmdHis") . '_' . $_FILES["file"]["name"];
				//判断文件是否存在
				if (file_exists($filename)) {
					return ['errCode' => 3403];
				} else {
					//保存文件
					//move_uploaded_file是php自带的函数，前面是旧的路径，后面是新的路径
					move_uploaded_file($_FILES["file"]["tmp_name"], $filename);
					return ['errCode' => 0, 'filesUrl' => 'study.xietan.xin/' . $filename];
				}
			} else {
				return ['errCode' => 3402];
			}
		}
	}
	
	/**
	 * 校验微信签名函数
	 * @param $signature
	 * @param $timestamp
	 * @param $nonce
	 * @return bool
	 */
	private function checkSignature($signature, $timestamp, $nonce)
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
		
		return $this->afterDatafomat($model->loginStudy($code));
	}
	
	/**
	 * 绑定教务系统
	 * @param $session
	 * @param $xh
	 * @param $psd
	 * @param $check_code
	 * @return array|bool
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function bindEduSys($session, $xh, $psd, $check_code)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re = $model->bindEduSys($re_info['uid'], $re_info['openId'], $xh, $psd, $check_code);
		return $this->afterDatafomat($re);
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
		else {
			if (!is_array($other_uid))
				$re_info = $model->getUserBasicInfo($other_uid);
			else {
				foreach ($other_uid as $value) {
					$re_info += $model->getUserBasicInfo($value);
				}
			}
		}
		
		return $this->afterDatafomat($re_info);
	}
	
	/**
	 * $rawData,$encryData,$vi,$singure 必须在更新时附加
	 * 更新用户的基本信息，需要rawData
	 * @param $session
	 * @param $rawData
	 * @param null $encryData
	 * @param null $vi
	 * @param null $singure
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function updateUserBasicInfo($session, $rawData, $encryData = null, $vi = null, $singure = null)
	{
		$model = new StudyAppModel();
		$result = $model->checkSession($session);
		if (!empty($result['errCode'])) {
			return $result;
		} else {
			$re_data = $model->updateUserBasicInfo($result['uid'], $result['openId'], $result['wx_session_key'], $rawData, $encryData, $vi, $singure);
			return $this->afterDatafomat($re_data);
		}
		
	}
	
	/**
	 * 更新session（暂时没用）
	 */
	public function updateSession($session)
	{
	
	
	}
	
	/**
	 * getCheckCode 获得登入验证码
	 * @param $session
	 * @return array
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getCheckCode($session)
	{
		$model = new StudyAppModel();
		$result = $model->checkSession($session);
		if (!empty($result['errCode'])) {
			return $result;
		} else {
			$cookies = $model->getCheckCode($result['uid']);
			return $this->afterDatafomat($cookies);
		}
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
	public function getUserScore($session, $other_uid = null, $xn = null, $xq = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_score = $model->getUserEduScore($re_info['uid'], $xn, $xq);
		return $this->afterDatafomat($re_score);
	}
	
	public function getCurrentTerm()
	{
		return $this->afterDatafomat(StudyAppModel::getCurrentTerm());
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
	public function getUserCourse($session, $other_uid = null, $xn = null, $xq = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_course = $model->getUserEduCourse($re_info['uid'], $xn, $xq);
		return $this->afterDatafomat($re_course);
		
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
	public function getUserEduInfo($session, $other_uid = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_info = $model->getUserEduInfo($re_info['uid']);
		return $this->afterDatafomat($re_info);
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
		return $this->afterDatafomat($free_class);
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
	public function updateUserEduAll($session, $other_uid = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$re_data = $model->updateUserEduAll($re_info['uid']);
		return $this->afterDatafomat($re_data);
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
	public function updateUserEduScore($session, $other_uid = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$EduScore = $model->updateUserEduScore($re_info['uid']);
		return $this->afterDatafomat($EduScore);
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
	public function updateUserEduCourse($session, $other_uid = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$EduCourse = $model->updateUserEduCourse($re_info['uid']);
		return $this->afterDatafomat($EduCourse);
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
	public function updateUserEduInfo($session, $other_uid = null)
	{
		$model = new StudyAppModel();
		$re_info = $model->checkSession($session);
		if (!empty($re_info['errCode']))
			return $re_info;
		
		$EduInfo = $model->updateUserEduInfo($re_info['uid']);
		return $this->afterDatafomat($EduInfo);
	}
	
	//TODO::增加自定义课表部分
	//TODO::用户自定义信息部分，个性签名，用户认证， 展示已经学习xx次  （关注人，被关注人...）
	//TODO::增加用户StudyScore积分部分，增加积分，查看积分信息
	//TODO::增加用户设置部分，隐私问题（是否展示成绩（80分以上的成绩），是否能让别人看到自己所在的班级，及个人信息）、是否接受学习邀请
	//TODO::群排行，获取群id，群id内的所有好友
	//TODO::增加匹配模型，匹配算法，发起预约请求，接受预约请求，成功预约提示 ，学习记录    可以约一个人，也可也约多个人   （定位验证地点，结束验证地点，完成本次自习）
	//TODO::增加社区问答部分，拉取消息，对比消息是否更新，发表问答，回复问答，查看已发布主题，删除主题，删除回复
	//TODO::增加时间胶囊部分 添加胶囊，删除胶囊，胶囊倒计时提醒
	//TODO::增加番茄计划部分 添加计划，减少计划
	
}