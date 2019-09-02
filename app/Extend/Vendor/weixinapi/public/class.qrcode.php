<?php
//后台管理类
class qrcode extends weixinapi{
	
	public $expire_seconds = 604800;	//二维码过期时间(7天)
	public $action_name = 'QR_SCENE';           //临时二维码
	public $appid; //appid
	public $appsecret;

	public function __construct($config=false){
		$this->appid = $config['appid'];
		$this->appsecret = $config['appsecret'];
		$this->url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';
	}
	
	//创建微信二维码
	public function create($token,$value){
		$data = '{"expire_seconds": "'.$this->expire_seconds.'", "action_name": "'.$this->action_name.'", "action_info": {"scene": {"scene_id":'.$value.'}}}';
		$this->url = $this->url.'?access_token='.$token;
		$result = $this->doPost($this->url,$data);
		$data = $this->_formatStr($result); //序列表$resul
		return $data;
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