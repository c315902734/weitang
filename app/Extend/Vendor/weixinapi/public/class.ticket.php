<?php
class weixintoolticket {
	
	public $token;
	public $url;

	public function __construct($token=false){
		$this->token = $token;
		//https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=TOKEN
		$this->url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';
	}

	//设置用户授权
	public function create($scene=1){
		global $Curl;
		$scene = intval($scene);
		$url = $this->url.'?access_token='.$this->token;
		//{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
		$data = array(
			'action_name' => 'QR_LIMIT_SCENE',
			'action_info' => array(
				'scene' => array(
					'scene_id' => $scene
				),
			),
		);
		$result = $Curl->doPost($url,json_encode($data,JSON_UNESCAPED_UNICODE));
		return $this->_formatStr($result);
	}
	
	protected function _formatStr($result){
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