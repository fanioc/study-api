<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
	// 数据库类型
	'type'            => 'mysql',
	// 服务器地址
	'hostname'        => '127.0.0.1',
	// 数据库名
	'database'        => 'xaufe',
	// 用户名
	'username'        => 'root',
	// 密码
	'password'        => '',
	// 端口
	'hostport'        => '3306',
	// 数据库连接参数
	'params'          => [],
	// 数据库编码默认采用utf8
	'charset'         => 'utf8',
	// 数据库表前缀
	'prefix'          => '',
	// 数据库调试模式
	'debug'           => true,
	
	// 是否严格检查字段是否存在
	'fields_strict'   => false,
	// 数据集返回类型（重要，可以对结果集使用toArray() ）
	'resultset_type'  => '\think\Collection',
];
