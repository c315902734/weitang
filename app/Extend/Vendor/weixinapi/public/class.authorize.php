<?php
class weixintoolauthorize extends weixinapi{
	
	public $token;
	public $appid;
	public $appsecret;
	public $state;
	public $url = array();
	public $table = 'wx_authorize';
	public $callback;

	public function __construct($data=array()){
		$this->appid = $data['appid'] ? $data['appid'] : false;
		$this->appsecret = $data['appsecret'] ? $data['appsecret'] : false;
		$this->state = $data['state'] ? $data['state'] : time(); //回调参数
		$this->callback = $data['callback'] ? $data['callback'] : false;
		//https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect
		$this->url['location'] = 'https://open.weixin.qq.com/connect/oauth2/authorize';
		//https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
		$this->url['token'] = 'https://api.weixin.qq.com/sns/oauth2/access_token';
		//https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
		$this->url['userinfo'] = 'https://api.weixin.qq.com/sns/userinfo';
		//https://api.weixin.qq.com/sns/auth?access_token=ACCESS_TOKEN&openid=OPENID
		$this->url['check'] = 'https://api.weixin.qq.com/sns/auth';
	}

	//设置用户授权
	public function target(){
		$url = $this->_formatLocation($this->callback);
		header('location: '.$url);
		exit;
	}

	//获取access_token
	public function getToken(){
		if($_GET['code']){
			$code = $_GET['code'];
		}else{
			die('禁止授权.');
		}
		$url = $this->url['token'].'?appid='.$this->appid.'&secret='.$this->appsecret.'&code='.$code.'&grant_type=authorization_code';
		$result = $this->doPost($url,array());
		$data = $this->_formatStr($result);
		$_SESSION['wx_authorize'] = $data;
		return $data;
	}

	//获取用户信息
	public function getAuthorize(){
		$data = $this->getToken();
		//获取信息
		$url = $this->url['userinfo'].'?access_token='.$data['access_token'].'&openid='.$data['openid'].'&lang=zh_CN';
		$result = $this->doGet($url);
		return $this->_formatStr($result);
	}
	
	//验证token有效性
	public function check(){
		$status = false;
		//https://api.weixin.qq.com/sns/auth?access_token=ACCESS_TOKEN&openid=OPENID
		if(isset($_SESSION['wx_authorize']['access_token']) && isset($_SESSION['wx_authorize']['openid'])){
			$url = $this->url['check'].'?access_token='.$_SESSION['wx_authorize']['access_token'].'&openid='.$_SESSION['wx_authorize']['openid'];
			$result = $this->doGet($url);
			$data = $this->_formatStr($result);
			if($data['errcode'] == 0 && $data['errmsg'] == 'ok') $status = true;
		}
		return $status;
	}

	//格式化请求授权地址
	protected function _formatLocation($redirect_uri){
		$callback = urlencode($redirect_uri);
		$url = $this->url['location'];
		$url .= '?appid='.$this->appid.'&redirect_uri='.$callback.'&response_type=code&scope=snsapi_userinfo&state='.$this->state.'#wechat_redirect';
		return $url;
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