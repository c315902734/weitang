<?php

class yunpian {
	public function __construct(){
		$config = array(
			'apikey'   => '52571b647711df2d8f31b4c446429cc8', //APIKEY
			'url'   => 'http://yunpian.com/v1/sms/send.json ', //访问地址
		);
		$this->config = $config;
	}

	public function sendMsg($mobile,$text){
		$url = $this->config['url'];
		$data['apikey'] = $this->config['apikey'];
		$data['mobile'] = $mobile;
		$data['text'] = $text;
		$result = $this->doPost($url,$data);
		$data = false;
		if($result) $data = $this->stdClassToArray(json_decode($result));
		return $data;
	}


	/**
	 * 请求远端post提取返回数据
	 * $url 远端请求地址
	 * $data 需要post的数据以数组方式
	 */
	public function doPost($url,$data=array(),$json=false){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if($json!=false) curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($data)));
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

	/**
	 * 请求远端get提取返回数据
	 * $url 远端请求地址
	 * $data 需要get的数据以数组方式
	 */
	public function doGet($url,$data=false){
		if(is_array($data)){
			foreach($data AS $k=>$v){
				$grr[]=$k.'='.$v;
			}
			$txt = implode($grr,'&');
		}else{
			$txt = $data;
		}
		$target = $txt ? $url.'?'.$txt : $url;
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

	protected function stdClassToArray($arr){
		$result = array();
		if(empty($arr)) return false;
		foreach($arr AS $key =>$value){
			if(gettype($value) == 'array' || gettype($value) == 'object'){
				$result[$key] = $this->stdClassToArray($value);
			}else{
				$result[$key] = $value;
			}
		}
		return $result;
	}
}