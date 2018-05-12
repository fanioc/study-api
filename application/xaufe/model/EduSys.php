<?php
namespace app\xaufe\model;

use app\xaufe\model\EduSysWeb;
use app\xaufe\model\EduSysMobile;
use think\Model;
/**
 * EdySys用来处理教务数据库分发，整合网页教务和手机移动教务信息
 */
class EduSys extends Model
{
	public function loginWeb ($xh,$pwd,$checkCode,$cookies){
	
	}
	
	public function getCheckCode (){
	
	}
	
	
	public function getFreeClass($time){
	
	}
	
	/**
	 * 更新数据库中2周的空课表
	 * @param $cookies
	 * @param $xh
	 */
	public function updateFreeClass($cookies,$xh){
	
	}
	
	public function getCourse($xh){
	
	}
	
	public function getInfo($xh){
	
	}
	
	public function getScore($xh){
	
	}
	
	public function updateCourse($xh){
	
	}
	
	public function updateInfo($xh){
	
	}
	
	public function updateScore($xh){
	
	}
	
	
}