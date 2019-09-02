<?php
//后台管理类
class weixintoolopenlogin{
	
	public $appid;//appid
	public $appsecret;
	public $redirect_uri; //回调地址
	public $response_type;
	public $scope = 'snsapi_login';
	public $state;
	public $access_token;
	public $refresh_token;
	public $url;

	public function __construct($config=false){
		$this->appid = $config['appid'];
		$this->appsecret = $config['appsecret'];
		$this->state = $config['state'] ? $config['state'] : time();
		$this->redirect_uri = urlencode($config['redirect_uri']);
		//https://open.weixin.qq.com/connect/qrconnect?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect
		$this->url['login']   = 'https://open.weixin.qq.com/connect/qrconnect';
		//https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
		$this->url['access']  = 'https://api.weixin.qq.com/sns/oauth2/access_token';
		//https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=APPID&grant_type=refresh_token&refresh_token=REFRESH_TOKEN 
		$this->url['refresh'] = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
		$this->table = 'wx_token';
	}
	
	//创建页面JS登陆方式
	public function createJs(){
		$str = array();
		$str['js'] = '<script src="http://res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js"></script>';
		$str['thumb'] = '<div id="login_container"><script type="text/javascript">';
		$str['thumb'] .='var obj = new WxLogin({id:"login_container",
						appid: "'.$this->appid.'",
						scope: "'.$this->scope.'",
						redirect_uri: "'.$this->redirect_uri.'",
						state: "'.$this->state.'",
						style: "black",
						href: ""});</script></div>';
		return $str;
	}

	//创建A连接点击跳转微信登陆
	public function createHref(){
		$url = $this->url['login'];
		$url .= '?appid='.$this->appid.'&redirect_uri='.$this->redirect_uri.'&response_type='.
				$this->response_type.'&scope='.$this->scope.'&state='.$this->state.'#wechat_redirect';
		return $url;
	}

	//curl微信接口获取并存入数据库
	public function getToken(){
		global $Curl;
		$code = $_GET['code'];
		$target = $this->url['access'].'?appid='.$this->appid.'&secret='.$this->appsecret.'&code='.$code.'&grant_type=authorization_code';
		$result = $Curl->doPost($target);
		$data = false;
		if($result) $data = $this->_stdClassToArray(json_decode($result,JSON_UNESCAPED_UNICODE));
		return $data;
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