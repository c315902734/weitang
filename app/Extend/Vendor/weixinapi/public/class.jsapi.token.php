<?php
//后台管理类
class weixintooljsapitoken extends weixinapi{
	
	public $tokentime = 3600;	//token过期时间
	public $token;
	public $ticket;
	public $table = 'wx_js_token';
	public $tool;

	public function __construct($token){
		$this->token = $token;
		//https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi
		$this->url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';
		include_once 'wxHttpCurl.php';$this->tool = new wxhttpcurl();
	}
	
	//获取微信token并存入数据库
	public function getTicket(){
		$target = 'access_token='.$this->token.'&type=jsapi';
		$result = $this->tool->doGet($this->url,$target);
		if($result){
			$data = $this->tool->stdToArray(json_decode($result,JSON_UNESCAPED_UNICODE));
			if(isset($data['ticket'])) return $data['ticket'];
		}
		return false;
	}
}