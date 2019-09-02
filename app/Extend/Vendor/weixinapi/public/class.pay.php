<?php
class weixintoolpay {
	
	public $config;
	public $parameters;
	public $url = array();

	public function __construct($config=false){
		$this->config = $config;
		//统一下单
		$this->url['unifiedorder'] = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
		//查询订单
		$this->url['orderquery'] = 'https://api.mch.weixin.qq.com/pay/orderquery';
		//退款
		$this->url['refund'] = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
		//查询退款
		$this->url['refundquery'] = 'https://api.mch.weixin.qq.com/pay/refundquery';
		//下载对账单
		$this->url['downloadbill'] = 'https://api.mch.weixin.qq.com/pay/downloadbill';
	}

	//扫码支付
	public function bizpay($data){
		$url['appid'] = $this->config['appid'];
		$url['mch_id'] = $this->config['mchid'];
		$url['product_id'] = $data['order_id'];
		$url['time_stamp'] = time();
		$url['nonce_str'] = $this->createNoncestr();//随机字符串
		$url['sign'] = $this->getSign($url);//签名
		if($url['appid'] == '' || $url['mch_id'] == '' || $url['product_id'] == ''){
			return false;
		}
		foreach($url as $key=>$val){
			$urldata[] = $key.'='.$val;
		}
		$urlstr = implode('&',$urldata);
		return 'weixin://wxpay/bizpayurl?'.$urlstr;
	}

	//扫码支付
	public function returnOrder($data){
		$data['sign'] = $this->getSign($data);//签名
		return $data;
	}
	
	//获取统一支付码
	public function pay($data){
		//创建XML文件
		$this->parameters["body"]             = $data['body']; //商品名称
		$this->parameters["out_trade_no"]     = $data['out_trade_no']; //订单编号
		$this->parameters["total_fee"]        = $data['total_fee']; //订单金额
		$this->parameters["spbill_create_ip"] = $this->getIp(); //用户IP
		$this->parameters["openid"]           = $data['openid'];
		//--------------------------------------------------------------
		$this->parameters["notify_url"] = $this->config['nurl'];
		$this->parameters["appid"]      = $this->config['appid'];//公众账号ID
		$this->parameters["mch_id"]     = $this->config['mchid'];//商户号
		$this->parameters["nonce_str"]  = $this->createNoncestr();//随机字符串
		$this->parameters["trade_type"] = 'JSAPI';
		//$this->parameters["trade_type"] = 'NATIVE';
		$this->parameters["sign"]       = $this->getSign($this->parameters);//签名
		$xml = $this->createXml();
		//post到微信接口获取prepay_id
		$response = $this->postXmlCurl($xml,$this->url['unifiedorder'],30);
		$result = $this->xmlToArray($response);
		$result['paysign'] = $this->parameters["sign"];
		return $result;
	}
	
	/** 申请退款
	 * $selfid 本地订单id
	 * $payid 微信支付平台对应id
	 * $price 退款金额
	 */
	public function refund($selfid,$payid,$price){
		$this->parameters["appid"]      = $this->config['appid'];//公众账号ID
		$this->parameters["mch_id"]     = $this->config['mchid'];//商户号
		$this->parameters["nonce_str"]  = $this->createNoncestr();//随机字符串
		$this->parameters["transaction_id"] = $payid;//微信订单号
		$this->parameters["out_refund_no"]  = $selfid;//商户退款单号
		$this->parameters["refund_fee"]     = intval($price);//退款金额
		$this->parameters["total_fee"]      = intval($price);//总金额
		$this->parameters["op_user_id"]     = $this->config['mchid'];//操作员帐号
		$this->parameters["sign"]           = $this->getSign($this->parameters);//签名
		$xml = $this->createXml();
		//post到微信接口获取prepay_id
		$response = $this->postXmlCurl($xml,$this->url['refund'],30);
		$result = $this->xmlToArray($response);
		return $result;
	}
	
	/** 订单查询
	 * $selfid 本地订单id
	 * $payid 微信支付平台对应id
	 */
	public function selectPay($selfid,$payid){
		$this->parameters["appid"]      = $this->config['appid'];//公众账号ID
		$this->parameters["mch_id"]     = $this->config['mchid'];//商户号
		$this->parameters["nonce_str"]  = $this->createNoncestr();//随机字符串
		$this->parameters["transaction_id"] = $payid;//微信订单号
		$this->parameters["out_refund_no"]  = $selfid;//商户退款单号
		$this->parameters["sign"]           = $this->getSign($this->parameters);//签名
		$xml = $this->createXml();
		//post到微信接口获取prepay_id
		$response = $this->postXmlCurl($xml,$this->url['orderquery'],30);
		$result = $this->xmlToArray($response);
		return $result;
	}
	
	/** 退款单查询
	 * $selfid 本地订单id
	 * $payid 微信支付平台对应id
	 */
	public function selectRefund($selfid,$payid){
		$this->parameters["appid"]      = $this->config['appid'];//公众账号ID
		$this->parameters["mch_id"]     = $this->config['mchid'];//商户号
		$this->parameters["nonce_str"]  = $this->createNoncestr();//随机字符串
		$this->parameters["transaction_id"] = $payid;//微信订单号
		$this->parameters["out_refund_no"]  = $selfid;//商户退款单号
		$this->parameters["sign"]           = $this->getSign($this->parameters);//签名
		$xml = $this->createXml();
		//post到微信接口获取prepay_id
		$response = $this->postXmlCurl($xml,$this->url['refundquery'],30);
		$result = $this->xmlToArray($response);
		return $result;
	}
	
	/** 下载对账单
	 * $bill_date 对账单日期 20140603
	 * $bill_type 账单类型
		ALL，返回当日所有订单信息，默认值
		SUCCESS，返回当日成功支付的订单
		REFUND，返回当日退款订单
		REVOKED，已撤销的订单 
	 */
	public function downPay($bill_date,$bill_type='ALL'){
		$this->parameters["appid"]     = $this->config['appid'];//公众账号ID
		$this->parameters["mch_id"]    = $this->config['mchid'];//商户号
		$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
		$this->parameters["bill_date"] = $bill_date;//微信订单号
		$this->parameters["bill_type"] = $bill_type;//商户退款单号
		$this->parameters["sign"]      = $this->getSign($this->parameters);//签名
		$xml = $this->createXml();
		//post到微信接口获取prepay_id
		$response = $this->postXmlCurl($xml,$this->url['downloadbill'],30);
		$result = $this->xmlToArray($response);
		return $result;
	}

	//作用：设置标配的请求参数，生成签名，生成接口参数xml
	public function createXml(){
		return $this->arrayToXml($this->parameters);
	}

	//作用：array转xml
	public function arrayToXml($arr){
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
			$parameters[$k] = $v;
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
		    if($urlencode){
			   $v = urlencode($v);
			}
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if(strlen($buff) > 0){
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
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

	//作用：以post方式提交xml到对应的接口url
	public function postXmlCurl($xml,$url,$second=30){
		$ch = curl_init();
		curl_setopt($ch, CURLOP_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
		curl_close($ch);
		if($data){
			curl_close($ch);
			return $data;
		}else{ 
			$error = curl_errno($ch);
			echo "系统出错,请稍后再试"; 
			curl_close($ch);
			return false;
		}
	}

	/*获取当前页面完整URL地址*/
	public function getUrl(){
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		return $url;
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