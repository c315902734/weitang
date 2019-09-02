<?php
/*****************************************************************-/
Designed by Huangshi Baipre Co., Ltd.
If you have any comments or suggestions please send your E-mail to 8205524@qq.com
由百葩科技信息有限公司设计制作
如果您有任何意见或建议请电邮8205524@qq.com
* ==================================================================
* Baipre 百葩科技 制作
* 版权所有 2013-2015 黄石百葩信息科技有限公司，并保留所有权利。
* http://www.baipre.com
* TEL:18571076265
* ==================================================================
* $Author: 李里 & 黄希
* $time 2013-11-11 
* 使用；请不要删除此备注信息，尊重制作者。
/-*****************************************************************/
class wxhttpcurl{

	public function doPost($url,$data=array(),$json=false){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		// curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		// curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包x
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
		if($json) curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($data)));
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

	public function doGet($url,$data=''){
		if(is_array($data)){
			foreach($data AS $k=>$v){
				$grr[]=$k.'='.$v;
			}
			$txt = implode($grr,'&');
		}else{
			$txt = $data;
		}
		$target = $url.'?'.$txt;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $target); // 要访问的地址
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		//curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

	public function stdToArray($stdobj){
		$result = array();
		if(empty($stdobj)) return false;
		foreach($stdobj AS $key =>$value){
			if(gettype($value) == 'array' || gettype($value) == 'object'){
				$result[$key] = $this->stdToArray($value);
			}else{
				$result[$key] = $value;
			}
		}
		return $result;
	}
}
