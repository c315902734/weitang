<?php
//微信配置类
class weixinapi {
	
	public $config = array();
	public $token; //weixin返回的token

	public function __construct($config=false){
		$this->config = $config;
		//获取token
		$this->token = $this->getToken();
	}

	public function getToken(){
		//获取 ACCESS_TOKEN
		include_once 'public/class.token.php';
		$tool = new weixintooltoken($this->config);
		return $tool->getToken();
	}

/**
 * 授权登陆
 * locationAuthorize 微信内置浏览器授权
 * getAuthorize 微信内置浏览器获取用户信息
*/
	//跳转授权页面
	public function locationAuthorize(){
		include_once 'public/class.authorize.php';
		$tool = new weixintoolauthorize($this->config);
		$tool->target();
		exit;
	}
	
	//获取授权
	public function getAuthorize(){
		include_once 'public/class.authorize.php';
		$tool = new weixintoolauthorize($this->config);
		$result = $tool->getAuthorize();
		return $result;
	}

/**
 * 菜单操作
 * getMenu 获取菜单 synchronization 是否强制同步
 * deleteMenu 清除菜单
 * applyMeny 同步菜单到微信
 */
	public function getMenu($synchronization=false){
		$result = false;
		include_once 'public/class.menu.php';
		$tool = new weixintoolmenu($this->token);
		if($synchronization){
			$result = $tool->synchronization();
		}else{
			$result = $tool->getMenu();
		}
		return $result;
	}

	public function applyMenu(){
		$result = false;
		include_once 'public/class.menu.php';
		$tool = new weixintoolmenu($this->token);
		$result = $tool->applyMenu();
		return $result;
	}

	public function getFans($openid){
		$result = false;
		if($this->token){
			include_once 'public/class.fans.php';
			$tool = new weixintoolfans($this->token);
			$result = $tool->getFansInfo($openid);
			if($result){
				$group = $tool->getFansGroups($openid);
				$result['groupid'] = $group['groupid'];
			}
		}
		return $result;
	}

/**
 * 消息管理
 * formatMessage 格式化微信post过来的信息
 * sendMessage 发送信息
*/
	public function formatMessage(){
		include_once 'public/class.message.php';
		$tool = new weixintoolmessage($this->token);
		$result = $tool->getMessage();
		return $result;
	}

	/**
	 * 发送消息
	 * type 类型 text文本,imgtext图文,imgtextall多条图文
	 * content 内容
	 * passive 是否被动消息
	 * to 接收用户openid
	 */
	public function sendMessage($type,$content,$passive=true,$openid=false){
		$result = false;
		include_once 'public/class.message.php';
		$tool = new weixintoolmessage($this->token);
		$result = $tool->sendMessage($type,$content,$passive,$openid);
		return $result;
	}

/**
 * 微信支付
 * pay 获取统一支付码
 * refund 申请退款
 * selectPay 订单查询
 * selectRefund 退款单查询
 * downPay 下载对账单
*/
	
	//扫码支付
	public function bizpay($data){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->bizpay($data);
		return $result;
	}

	public function pays($data){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->pay($data);
		return $result;
	}

	public function refund($selfid,$payid,$price){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->refund($selfid,$payid,$price);
		return $result;
	}

	/*public function refund($selfid,$payid,$price){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->refund($selfid,$payid,$price);
		return $result;
	}*/

	public function selectPay($selfid,$payid){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->selectPay($selfid,$payid);
		return $result;
	}
	
	public function selectRefund($selfid,$payid){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->selectRefund($selfid,$payid);
		return $result;
	}

	public function downPay($bill_date,$bill_type='ALL'){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->downPay($bill_date,$bill_type='ALL');
		return $result;
	}

	public function returnOrder($result,$post){
		include_once 'public/class.pay.php';
		$tool = new weixintoolpay($this->config);
		$result = $tool->returnOrder($result,$post);
		return $result;
	}

/**
 * 微信JS API
 * getTicket 获取JS API ticket
 * getJsApiConfig 设置wx.config配置基本值
 * deliver 发货通知
 * warning 告警接口
*/
	public function getJsApiTicket(){
		include_once 'public/class.jsapi.token.php';
		$tool = new weixintooljsapitoken($this->token);
		return $tool->getTicket();
	}
	
	//获取weixin JSAPI 基础配置内容
	public function getJsApiConfig($ticket){
		$this->config['ticket'] = $ticket;
		include_once 'public/class.jsapi.php';
		$tool = new weixintooljsapi($this->config);
		return $tool->getConfigData();
	}

	//获取欲支付订单号
	public function getJsApiPayData($ticket,$data){
		$this->config['ticket'] = $ticket;
		include_once 'public/class.jsapi.php';
		$tool = new weixintooljsapi($this->config);
		$config = $tool->getPayConfigData();
		$paydata = $tool->getPayData($data);
		return array('config'=>$config,'paydata'=>$paydata);
	}

/**
 * 请求远端post提取返回数据
 * $url 远端请求地址
 * $data 需要post的数据以数组方式
 */
	public function doPost($url,$data='',$json=false){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		// curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		// curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包x
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
		if($json) curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($data)));
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

/**
 * 请求远端get提取返回数据
 * $url 远端请求地址
 * $data 需要get的数据以数组方式
 */
	public function doGet($url,$data=''){
		if(is_array($data)){
			foreach($data AS $k=>$v){
				$grr[]=$k.'='.$v;
			}
			$txt = implode($grr,'&');
		}else{
			$txt = $data;
		}
		$target = $url.'?'.$txt;
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
/*获取用户IP地址*/
	public function getIp(){
		static $realip = NULL;
		if ($realip !== NULL){
			return $realip;
		}
		if (isset($_SERVER)){
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
				$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				/* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
				foreach ($arr AS $ip){
					$ip = trim($ip);
					if ($ip != 'unknown'){
						$realip = $ip;
						break;
					}
				}
			}
			elseif (isset($_SERVER['HTTP_CLIENT_IP'])){
				$realip = $_SERVER['HTTP_CLIENT_IP'];
			}else{
				if (isset($_SERVER['REMOTE_ADDR'])){
					$realip = $_SERVER['REMOTE_ADDR'];
				}else{
					$realip = '0.0.0.0';
				}
			}
		}else{
			if (getenv('HTTP_X_FORWARDED_FOR')){
				$realip = getenv('HTTP_X_FORWARDED_FOR');
			}elseif (getenv('HTTP_CLIENT_IP')){
				$realip = getenv('HTTP_CLIENT_IP');
			}else{
				$realip = getenv('REMOTE_ADDR');
			}
		}
		preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
		$realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
		return $realip;
	}

	//获取微信post过来的信息
	public function getData(){
		$data = $GLOBALS["HTTP_RAW_POST_DATA"];
		$xml = false;
		if(!empty($data)) $xml = (array)simplexml_load_string($data,'SimpleXMLElement',LIBXML_NOCDATA);
		return $xml;
	}
}