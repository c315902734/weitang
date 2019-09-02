<?php
//微信配置类
class weixinopenapi {
	
	public $config = array();
	public $token;

	public function __construct($config=array()){
		$data['appid'] = $config['openappid'];
		$data['appsecret'] = $config['openappsecret'];
		$data['unionid'] = $config['unionid'] ? true : false;
		$data['redirect_uri'] = $config['redirect_uri'] ? $config['redirect_uri'] : 'http://zx.wmlife.net/index/index/fanslogin/';
		$data['state'] = time();
		$data['scope'] = 'snsapi_login';
		$this->config = $data;
	}
	
	//获取微信登陆跳转连接
	public function loginHref($type=false){
		include_once 'public/class.open.login.php';
		$tool = new weixintoolopenlogin($this->config);
		if($type=='href'){
			return $tool->createHref();
		}else{
			return $tool->createJs();
		}
	}

	//获取token
	public function getToken(){
		include_once 'public/class.open.login.php';
		$tool = new weixintoolopenlogin($this->config);
		$this->token = $tool->getToken();
		return $this->token;
	}
	
	//获取用户信息
	public function getFans($openid,$token=false){
		$result = false;
		if($token){
			include_once 'public/class.open.fans.php';
			$tool = new weixintoolopenfans($token);
			$result = $tool->getFans($openid);
		}
		return $result;
	}
}