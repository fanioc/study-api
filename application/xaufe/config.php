<?php
//配置文件
return [
	//----------------------------------------
	//-------------模块自定义配置--------------
	//----------------------------------------
	
	// 取消url大小写转换
	'url_convert' => false,
	
	'default_return_type' => 'json',
	
	//----------------------------------------
	//-----------模块自定义扩展配置------------
	//----------------------------------------
	'EduSysWeb_url' => "http://jwgl.xaufe.edu.cn",
	
	
	'EduSysMobile_url' => "http://218.195.32.59",
	'EduSysMobile_encrypt' => "DAFF8EA19E6BAC86E040007F01004EA",
	'EduSysMobil_url_choice' => ['course' => "XS0202",
		'exam' => "XS0204",
		'score' => "XS0205",
		'info' => "XS0208"],
	'EduSysMobil_url_procode' => "002",
	'EduSysMobil_url_type' => "1",

];