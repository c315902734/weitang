<?php
//后台管理类
class weixintooltoken {
	
	public $appid; //appid
	public $appsecret;
	public $tool;

	public function __construct($config=false){
		$this->appid     = $config['appid'];
		$this->appsecret = $config['appsecret'];
		//https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx0dc19a58c356ec25&secret=a92f1f51355fa375ee8a7815e74c1b22 
		$this->url   = 'https://api.weixin.qq.com/cgi-bin/token';
		include_once 'wxHttpCurl.php';$this->tool = new wxhttpcurl();
	}
	
	//获取微信token
	public function getToken(){
		$target = 'grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
		$result = $this->tool->doGet($this->url,$target);
		if($result){
			$data = $this->tool->stdToArray(json_decode($result));
			if(isset($data['access_token'])) return $data['access_token'];
		}
		return false;
	}
}