<?php

namespace app\xaufe\model;

use think\Model;
use QL\QueryList;
use org\util\Curl;

class EduSysMobile extends Model
{
	private $cookies;
	
	private function getUrl($choice,$xh){
		$url = config('EduSysMobile_url')
			. '/login_sso.aspx?procode=' . config('EduSysMobil_url_procode')
			. '&type=' . config('EduSysMobil_url_type')
			. '&choice=' . config('EduSysMobil_url_choice.'.$choice)
			. '&uid=' . $xh . '&role=XS';
		$time = time();
		$key = md5(config('EduSysMobil_url_procode').config('EduSysMobil_url_choice.'.$choice).$xh.config('EduSysMobile_encrypt').$time);
		$url .= '&key='.$key.'&time='.$time;
		return $url;
	}
	
	function login($xh, $psw)
	{
	
	}
	
	function getCourse($xh)
	{
		$url = $this->getUrl('course',$xh);
		
		return $url;
	
	}
	
	public function getScore($xh)
	{
		$url = $this->getUrl('score',$xh);
		
		return $url;
	}
	
	public function getInfo($xh)
	{
		$url = $this->getUrl('info',$xh);
		
		return $url;
	}
	
	
	function getExam($xh)
	{
		$url = $this->getUrl('exam',$xh);
		
		return $url;
	}
	
	
}