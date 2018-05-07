<?php

namespace org\util;

/**
 * Class Curl 封装Curl类
 */
class Curl
{
	/**
	 *
	 * .参数 网址, 文本型, , 完整的网页地址,必须包含http://或者https://
	 * .参数 访问方式, 整数型, 可空 , 0=GET 1=POST 2=HEAD
	 * .参数 提交信息, 文本型, 可空 , "POST"专用
	 * .参数 提交Cookies, 文本型, 参考 可空 , 本参数传递变量时会自动回传返回的Cookie
	 * .参数 返回Cookies, 文本型, 参考 可空 , 返回的Cookie
	 * .参数 附加协议头, 文本型, 可空 , 一行一个请用换行符隔开
	 * .参数 返回协议头, 文本型, 参考 可空 , 返回的协议头
	 * .参数 返回状态代码, 整数型, 参考 可空 , 网页返回的状态代码，例如：200；302；404等
	 * .参数 禁止重定向, 逻辑型, 可空 , 默认不禁止网页重定向
	 * .参数 字节集提交, 字节集, 可空 , 提交字节集数据 //在php中即为string类型
	 * .参数 代理地址, 文本型, 可空 , 代理地址，格式为 8.8.8.8:88
	 * .参数 超时, 整数型, 可空 , 秒|默认为15秒,-1为无限等待
	 * .参数 用户名, 文本型, 可空 , 用户名
	 * .参数 密码, 文本型, 可空 , 密码
	 * .参数 代理标识, 整数型, 可空 , 代理标识，默认为1，0为路由器
	 * .参数 对象继承, 对象, 可空 , 此处可自行提供对象，不再主动创建
	 */
	
	/**
	 * @param $url
	 * @param string $method 访问方式
	 * @param null $post_data array|string 文件上传使用 new CURLFile 建立
	 * @param null $r_code int 返回的状态码
	 * @param int $time_out int 允许的连接持续的时间/秒
	 * @param null $cookies string cookie1:qq;cookie2:ww //用分号隔开代表多个cookies
	 * @param null $r_cookies string 返回的 cookies
	 * @param null $header string|array 设置header
	 * @param null $r_header string 返回的header
	 * @param null $proxy 代理地址
	 * @param null $proxy_user 代理用户名密码
	 * @return string
	 */
	public static function visit($url, $method = "GET", $post_data = null, $cookies = null, &$r_cookies = null, &$r_code = null,
	                             $header = null, &$r_header = null, $time_out = 60, $proxy = null, $proxy_user = null)
	{
		$ch = curl_init();
		
		$set_opt = array(
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => $method, //访问方式
			CURLOPT_CONNECTTIMEOUT => $time_out, //设置连接时长
			CURLOPT_HEADER => true, //输出文件头
			CURLOPT_RETURNTRANSFER => true, //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。 获取raw信息
			CURLOPT_AUTOREFERER => true, //TRUE 时将根据 Location: 重定向时，自动设置 header 中的Referer:信息。
		);
		
		if ($proxy != null) {
			$set_opt+= array(
				CURLOPT_PROXY => $proxy, //代理服务器 ip:port
				CURLOPT_PROXYAUTH => CURLAUTH_BASIC, //代理认证模式
			);
			if ($proxy != null) {
				$set_opt+= array(
					CURLOPT_PROXYUSERPWD => $proxy_user, //代理用户名密码 username:password
				);
			}
		}
		
		if ($method == "GET") {
//            $set_opt += array();
		} else if ($method == "POST") {
			$set_opt += array(CURLOPT_POSTFIELDS => $post_data); //设置post提交的数据
		} else if ($method == "HEAD") {
			$set_opt += array(CURLOPT_NOBODY => true);//设置无body，只有header
		}
		
		if ($header != null) {
			$set_opt += array(CURLOPT_HTTPHEADER => $header); //设置header请求头，数组
		}
		
		if ($cookies != null) {
			$set_opt += array(CURLOPT_COOKIE => $cookies); //设置提交的cookies，以分号分界
		}
		
		curl_setopt_array($ch, $set_opt);
		
		$out_put = curl_exec($ch);//执行
		
		$r_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  //获取状态码
		
		list($r_header, $body) = explode("\r\n\r\n", $out_put, 2);
		
		if (preg_match("/set\-cookie:\s*([^\r\n]*)/i", $r_header, $matches))
			$r_cookies = $matches[1];
		
		return $body;
	}
	
	
	/**
	 * @param $url
	 * @param null $post_data array|string 文件上传使用 new CURLFile 建立
	 * @param null $r_code int 返回的状态码
	 * @param int $time_out int 允许的连接持续的时间/秒
	 * @param null $cookies string cookie1:qq;cookie2:ww //用分号隔开代表多个cookies
	 * @param null $r_cookies string 返回的 cookies
	 * @param null $header string|array 设置header
	 * @param null $r_header string 返回的header
	 * @param null $proxy 代理地址
	 * @param null $proxy_user 代理用户名密码
	 * @return string
	 */
	public static function post($url, $post_data = null, $cookies = null, &$r_cookies = null, &$r_code = null,
	                            $header = null, &$r_header = null, $time_out = 60, $proxy = null, $proxy_user = null)
	{
		$ch = curl_init();
		
		$set_opt = array(
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => 'POST', //访问方式
			CURLOPT_POSTFIELDS => $post_data,//设置post提交的数据
			CURLOPT_CONNECTTIMEOUT => $time_out, //设置连接时长
			CURLOPT_HEADER => true, //输出文件头
			CURLOPT_RETURNTRANSFER => true, //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。 获取raw信息
			CURLOPT_AUTOREFERER => true, //TRUE 时将根据 Location: 重定向时，自动设置 header 中的Referer:信息。
		);
		
		if ($proxy != null) {
			$set_opt += array(
				CURLOPT_PROXY => $proxy, //代理服务器 ip:port
				CURLOPT_PROXYAUTH => CURLAUTH_BASIC, //代理认证模式
			);
			if ($proxy != null) {
				$set_opt += array(
					CURLOPT_PROXYUSERPWD => $proxy_user, //代理用户名密码 username:password
				);
			}
		}
		
		if ($header != null) {
			$set_opt += array(CURLOPT_HTTPHEADER => $header); //设置header请求头，数组
		}
		
		if ($cookies != null) {
			$set_opt += array(CURLOPT_COOKIE => $cookies); //设置提交的cookies，以分号分界
		}
		
		curl_setopt_array($ch, $set_opt);
		
		$out_put = curl_exec($ch);//执行
		
		$r_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  //获取状态码
		
		list($r_header, $body) = explode("\r\n\r\n", $out_put, 2);
		
		if (preg_match("/set\-cookie:\s*([^\r\n]*)/i", $r_header, $matches))
			$r_cookies = $matches[1];
		
		return $body;
	}
	
	/**
	 * @param $url
	 * @param null $r_code int 返回的状态码
	 * @param int $time_out int 允许的连接持续的时间/秒
	 * @param null $cookies string cookie1:qq;cookie2:ww //用分号隔开代表多个cookies
	 * @param null $r_cookies string 返回的 cookies
	 * @param null $header string|array 设置header
	 * @param null $r_header string 返回的header
	 * @param null $proxy 代理地址
	 * @param null $proxy_user 代理用户名密码
	 * @return string
	 */
	public static function get($url, $cookies = null, &$r_cookies = null, &$r_code = null,
	                           $header = null, &$r_header = null, $time_out = 60, $proxy = null, $proxy_user = null)
	{
		$ch = curl_init();
		
		$set_opt = array(
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => 'GET', //访问方式
			CURLOPT_CONNECTTIMEOUT => $time_out, //设置连接时长
			CURLOPT_HEADER => true, //输出文件头
			CURLOPT_RETURNTRANSFER => true, //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。 获取raw信息
			CURLOPT_AUTOREFERER => true //TRUE 时将根据 Location: 重定向时，自动设置 header 中的Referer:信息。
		);
		
		if ($proxy != null) {
			$set_opt += array(
				CURLOPT_PROXY => $proxy, //代理服务器 ip:port
				CURLOPT_PROXYAUTH => CURLAUTH_BASIC, //代理认证模式
			);
			if ($proxy != null) {
				$set_opt+= array(
					CURLOPT_PROXYUSERPWD => $proxy_user, //代理用户名密码 username:password
				);
			}
		}
		
		if ($header != null) {
			$set_opt+= array(CURLOPT_HTTPHEADER => $header); //设置header请求头，数组
		}
		
		if ($cookies != null) {
			$set_opt+= array(CURLOPT_COOKIE => $cookies); //设置提交的cookies，以分号分界
		}
		
		
		curl_setopt_array($ch, $set_opt);
		
		$out_put = curl_exec($ch);//执行
		
		$r_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  //获取状态码
		
		list($r_header, $body) = explode("\r\n\r\n", $out_put, 2);
		
		if (preg_match("/set\-cookie:\s*([^\r\n]*)/i", $r_header, $matches))
			$r_cookies = $matches[1];
		
		return $body;
	}
	
}