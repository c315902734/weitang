<?php
//后台管理类
class formaturl {

	public $url = array();

	public function __construct($config=false){
		$this->appid = $config['appid'];
		$this->appsecret = $config['appsecret'];
		$this->apptoken = $config['token'];
		$this->encodingaeskey = $config['encodingaeskey'];
		$this->_formatApiUrl();
	}
	
	//获取微信token
	public function getToken(){
		global $DB;
		$time = time();
		//获取数据库token
		$sql = 'SELECT * FROM '.$this->table.' ORDER BY id DESC';
		$data = $DB->getRow($sql);
		$thistokentime = $data ? intval($data['intime'])+$this->tokentime : $this->tokentime;
		if($thistokentime>$time && $data){
			$thistoken = $data['token'];
		}else{
			$result = $this->_dotoken();
			if(isset($result['access_token'])){
				$thistoken = $result['access_token'];
			}else{
				$thistoken = false;
			}
		}
		define('WEIXINTOKEN', $thistoken);
		$this->token = $thistoken;
	}
	
	protected function _dotoken(){
		global $DB,$Curl,$J;
		$text = 'grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
		$result = $Curl->doGet($this->url['token'],$text);
		$data = false;
		if($result){
			$DB->autoExecute('log',array('log'=>$result));
			$data = $this->_stdClassToArray($J->decode($result));
			if(isset($data['access_token'])){
				$arr = array(
					'token' => $data['access_token'],
					'intime' => time()
				);
				$DB->autoExecute($this->table,$arr);
			}
		}
		return $data;
	}

	//格式化配置微信接口地址
	protected function _formatApiUrl(){
		$this->url['token'] = 'https://api.weixin.qq.com/cgi-bin/token';
		$this->url['sendmessage_customservice'] = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
		$this->url['getuserinfo'] = 'https://api.weixin.qq.com/cgi-bin/user/info';
	}

	protected function _stdClassToArray($arr){
		$ret = array();
		if(empty($arr)) return false;
		foreach($arr AS $key =>$value){
			if(gettype($value) == 'array' || gettype($value) == 'object'){
				$ret[$key] = $this->stdClassToArray($value);
			}else{
				$ret[$key] = $value;
			}
		}
		return $ret;
	}
}