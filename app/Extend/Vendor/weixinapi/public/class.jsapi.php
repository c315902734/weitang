<?php
class weixintooljsapi {

	public $config = array();
	public $ticket;
	public $response; //微信返回的响应
	public $parameters = array(); //请求参数，类型为关联数组
	public $prepay_id;
	public $url;
	public $result; //返回参数，类型为关联数组

	public function __construct($config=false){
		$this->config = $config;
		$this->ticket = $config['ticket'];
		$this->url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
	}

	public function getConfigData(){
		$data['appid'] = $this->config['appid'];
		$data['timestamp'] = time();
		$data['ticket'] = $this->ticket;
		$data['noncestr'] = $this->createNoncestr();
		$data['url'] = $this->getUrl();
		$signature = 'jsapi_ticket='.$this->ticket.'&noncestr='.$data['noncestr'].'&timestamp='.$data['timestamp'].'&url='.$data['url'];
		$data['signature'] = sha1($signature);
		return $data;
	}
	
	//生成JS API全局参数
	public function getPayConfigData(){
		$data['appid'] = $this->config['appid'];
		$data['timestamp'] = time();
		$data['ticket'] = $this->ticket;
		$data['noncestr'] = $this->createNoncestr();
		$data['url'] = $this->getUrl();
		$signature = 'jsapi_ticket='.$this->ticket.'&noncestr='.$data['noncestr'].'&timestamp='.$data['timestamp'].'&url='.$data['url'];
		$data['signature'] = sha1($signature);
		return $data;
	}
	
	/** 生成JS支付JSON配置
	 * 支付生成流程
	 * 1> 创建支付XML文件
	 * 2> post到接口获取prepay_id
	 * 3> 准备调用API js配置
	 * 4> 输出配置
	 */
	public function getPayData($data=false){
		if($data['total_fee'] <= 0){
			die('金额错误,请返回重试');
		}
		//创建XML文件
		$this->parameters["body"] = $data['body']; //商品名称
		$this->parameters["out_trade_no"] = $data['out_trade_no']; //订单编号
		$this->parameters["total_fee"] = $data['total_fee']; //订单金额
		$this->parameters["spbill_create_ip"] = $this->getIp(); //用户IP
		$this->parameters["openid"] = $data['openid'];
		$this->parameters["notify_url"] = $this->config['nurl'];
		$xml = $this->createXml();
		//post到微信接口获取prepay_id
		$response = $this->postXmlCurl($this->url,$xml,30);
		$result = $this->xmlToArray($response);
		if($result['return_code'] == 'FAIL'){
			die($result['return_msg']);
		}
		$prepay_id = $result["prepay_id"];
		//准备JS内容数组
		$objarr = array();
		$objarr["appId"]     = $this->parameters['appid'];
	    $objarr["timeStamp"] = strval(time());
	    $objarr["nonceStr"]  = $this->parameters['nonce_str'];
		$objarr["package"]   = "prepay_id=".$prepay_id;
	    $objarr["signType"]  = "MD5";
	    $objarr["paySign"]   = $this->getSign($objarr);
		return $objarr;
	}

	//作用：设置标配的请求参数，生成签名，生成接口参数xml
	public function createXml(){
		$this->parameters["appid"] = $this->config['appid'];//公众账号ID
		$this->parameters["mch_id"] = $this->config['mchid'];//商户号
		$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
		$this->parameters["trade_type"] = 'JSAPI';
		$this->parameters["sign"] = $this->getSign($this->parameters);//签名
		return $this->arrayToXml($this->parameters);
	}

	//作用：array转xml
	function arrayToXml($arr){
		$xml = "<xml>";
		foreach($arr AS $key=>$val){
        	 if(is_numeric($val)){
        	 	$xml.="<".$key.">".$val."</".$key.">";
        	 }else{
        	 	$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml.="</xml>";
		return $xml;
    }

	//作用：将xml转为array
	public function xmlToArray($xml){
        //将XML转为array        
        $array_data = json_decode(json_encode(simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA)),true);
		return $array_data;
	}

	//作用：生成签名
	public function getSign($obj){
		foreach($obj AS $k=>$v){
			$v != '' && $parameters[$k] = $v;
		}
		ksort($parameters);
		$s1 = $this->formatBizQueryParaMap($parameters,false);
		$s2 = $s1."&key=".$this->config['paykey'];
		$s3 = md5($s2);
		$result = strtoupper($s3);
		return $result;
	}

	//作用：格式化参数，签名过程需要使用
	public function formatBizQueryParaMap($paraMap,$urlencode){
		$buff = "";
		ksort($paraMap);
		foreach($paraMap AS $k=>$v){
		    if($urlencode) $v = urlencode($v);
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if(strlen($buff) > 0) $reqPar = substr($buff, 0, strlen($buff)-1);
		return $reqPar;
	}

	/*产生随机字符串，不长于32位*/
	public function createNoncestr($length=32){
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for($i=0;$i<$length;$i++){
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		}  
		return $str;
	}

	/*获取当前页面完整URL地址*/
	public function getUrl(){
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		return $url;
	}
	
	//作用：以post方式提交xml到对应的接口url
	public function postXmlCurl($url,$xml,$second=30){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		// curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		// curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml); // Post提交的数据包x
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
}