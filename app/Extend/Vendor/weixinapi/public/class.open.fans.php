<?php
class weixintoolopenfans {
	
	public $token;
	public $url;
	public $table = 'wx_fans';
	public $tablesub = 'wx_groups';

	public function __construct($token=false){
		$this->token = $token;
		//https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID
		$this->url = 'https://api.weixin.qq.com/sns/userinfo';
	}

	//获取用户信息
	public function getFans($openid){
		global $Curl;
		$target = 'access_token='.$this->token.'&openid='.$openid;
		$result = $Curl->doGet($this->url,$target);
		return $this->_stdClassToArray(json_decode($result,JSON_UNESCAPED_UNICODE));
	}

	//格式化stdClass数组
	protected function _stdClassToArray($arr){
		$result = array();
		if(empty($arr)) return false;
		foreach($arr AS $key =>$value){
			if(gettype($value) == 'array' || gettype($value) == 'object'){
				$result[$key] = $this->_stdClassToArray($value);
			}else{
				$result[$key] = $value;
			}
		}
		return $result;
	}
}