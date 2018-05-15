<?php

namespace app\xaufe\controller;

/**
 * 本接口用来查询教务信息，一般不对外开放
 * Class EduSys
 * @package app\xaufe\controller
 */
class EduSys
{
	function test(){
	
	}
	
	public function updateAll($xh){
		$ss = new \app\xaufe\model\EduSys();
		
		return $ss->updateAll($xh);
	}
	
	function updateFreeClass($cookies,$xh)
	{
		$ss = new \app\xaufe\model\EduSys();
		return $ss->updateFreeClass($cookies,$xh);
	}
	
	function getCurrentTerm(){
		return \app\xaufe\model\EduSys::getCurrentTerm();
	}
	
	/**
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	function getFreeClass(){
		$ss = new \app\xaufe\model\EduSys();
		
		print_r($ss->getFreeClass('2018-05-20')->toArray());
	}
}