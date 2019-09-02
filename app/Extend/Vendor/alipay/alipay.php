<?php

class publicAlipay {
	public $confic = array();

	public function __construct(){

	}

	public function setConfig($data=array(),$type='web'){
		$config = array(
			'email'   => isset($data['email']) ? $data['email'] : 'drt_service@sina.com', //收款商家帐号
			'partner' => isset($data['partner']) ? $data['partner'] : '2088121688432074', //商家申请的partner
			'key'     => isset($data['key']) ? $data['key'] : 'a1ov253jya896tczcw6vncl3d11wr35u', //商家申请的key
		);
		
		/******************/
		/*以下设置请勿修改 - 此配置仅支持即时到账*/
		/******************/
		//格式化配置
		$this->config = $config;
		//支付类型 1=>即时到帐
		$this->config['type'] = '1';
		//主机IP地址
		$this->config['ip'] = $_SERVER['REMOTE_ADDR'];
		//商户的私钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		$this->config['private_key_path']	= 'key/rsa_private_key.pem';
		//支付宝公钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		$this->config['ali_public_key_path']= 'key/alipay_public_key.pem';
		//签名方式 不需修改
		$this->config['sign_type'] = 'MD5';
		//字符编码格式 目前支持 gbk 或 utf-8
		$this->config['input_charset'] = 'utf-8';
		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		$this->config['cacert'] = $type=='wap' ? getcwd().'\\wap_cacert.pem' : getcwd().'\\web_cacert.pem';
		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		$this->config['transport'] = 'http';
	}
	
	/** 支付主操作函数
	 * $oid 订单id(唯一)
	 * $oname 订单名称
	 * $price 订单金额
	 * $nurl 异步通知url 
	 * $rurl 跳转同步页面url
	 * $body 订单描述[可不填]
	 * $gurl 商品展示地址[可不填]
	 */
	public function doPayForWeb($oid,$oname,$price,$nurl,$rurl,$body='',$gurl=''){
		if($price<=0) return false;
		$parameter = array(
				"service" => "create_direct_pay_by_user",
				"partner" => trim($this->config['partner']),
				"payment_type"  => $this->config['type'], //加密方式
				"notify_url"    => $nurl,
				"return_url"    => $rurl,
				"seller_email"  => $this->config['email'],
				"out_trade_no"  => $oid,
				"subject"       => $oname,
				"total_fee"     => $price,
				"body"          => $body,
				"show_url"      => $gurl,
				"anti_phishing_key" => '',
				"exter_invoke_ip"   => $this->config['ip'],
				"_input_charset"    => trim(strtolower($this->config['input_charset']))
		);
		//引入alipay支撑文件
		require_once("web/lib/alipay_submit.class.php");
		//建立请求
		$alipaySubmit = new AlipaySubmit($this->config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
		echo $html_text;
	}

	public function doPayForBank($oid,$oname,$bank,$price,$nurl,$rurl,$body='',$gurl=''){
		if($price<=0) return false;
		$defaultbank = $this->getBank($bank);
		$parameter = array(
				"service" => "create_direct_pay_by_user",
				"partner" => trim($this->config['partner']),
				"payment_type"  => $this->config['type'], //加密方式
				"notify_url"    => $nurl,
				"return_url"    => $rurl,
				"seller_email"  => $this->config['email'],
				"out_trade_no"  => $oid,
				"subject"       => $oname,
				"total_fee"     => $price,
				"body"          => $body,
				"paymethod"     => 'bankPay',
				"defaultbank"   => $defaultbank,
				"show_url"      => $gurl,
				"anti_phishing_key" => '',
				"exter_invoke_ip"   => $this->config['ip'],
				"_input_charset"    => trim(strtolower($this->config['input_charset']))
		);
		//引入alipay支撑文件
		require_once("web/lib/alipay_submit.class.php");
		//建立请求
		$alipaySubmit = new AlipaySubmit($this->config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
		echo $html_text;
	}
	
	//异步回调
	public function doReturnWeb(){
		require_once("web/lib/alipay_notify.class.php");
		$alipayNotify = new AlipayNotify($this->config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result){
			return $_POST;
		}else{
			return false;
		}
	}

	//同步回调
	public function doBackWeb(){
		if(isset($_GET['TRADE_SUCCESS']) && $_GET['TRADE_SUCCESS']=='TRADE_SUCCESS'){
			return $_GET;
		}else{
			return false;
		}
	}

	/** 支付主操作函数
	 * $oid 订单id(唯一)
	 * $oname 订单名称
	 * $price 订单金额
	 * $nurl 异步通知url 
	 * $rurl 跳转同步页面url
	 * $murl 操作中断返回地址
	 * $body 订单描述[可不填]
	 * $gurl 商品展示地址[可不填]
	 */
	public function doPayForWap($oid,$oname,$price,$nurl,$rurl,$murl,$body='',$gurl=''){
		//请求号须保证每次请求都是唯一
		$req_id = date('Ymdhis');
		//请求业务参数详细
		$req_data = '<direct_trade_create_req><notify_url>' . $nurl . '</notify_url><call_back_url>' . $rurl . '</call_back_url><seller_account_name>' . $this->config['email'] . '</seller_account_name><out_trade_no>' . $oid . '</out_trade_no><subject>' . $oname . '</subject><total_fee>' . $price . '</total_fee><merchant_url>' . $murl . '</merchant_url></direct_trade_create_req>';
		//构造要请求的参数数组
		$para_token = array(
				"service"  => "alipay.wap.trade.create.direct",
				"partner"  => trim($this->config['partner']),
				"sec_id"   => trim($this->config['sign_type']),
				"format"   => "xml",
				"v"        => "2.0",
				"req_id"   => $req_id, 
				"req_data" => $req_data,
				"_input_charset" => trim(strtolower($this->config['input_charset']))
		);
		//引入alipay支撑文件并实例化
		require_once("wap/lib/alipay_submit.class.php");
		$alipaySubmit = new AlipaySubmit($this->config);

		//建立请求
		$html_text = $alipaySubmit->buildRequestHttp($para_token);
		//URLDECODE返回的信息
		$html_text = urldecode($html_text);
		//解析远程模拟提交后返回的信息
		$para_html_text = $alipaySubmit->parseResponse($html_text);
		//获取request_token
		$request_token = $para_html_text['request_token'];
		//业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service" => "alipay.wap.auth.authAndExecute",
				"partner" => trim($this->config['partner']),
				"sec_id"  => trim($this->config['sign_type']),
				"format"  => "xml",
				"v"       => "2.0",
				"req_id"   => $req_id,
				"req_data" => $req_data,
				"_input_charset" => trim(strtolower($this->config['input_charset']))
		);
		$html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
		echo $html_text;
	}

	//异步回调
	public function doReturnWap(){
		require_once("wap/lib/alipay_notify.class.php");
		$alipayNotify = new AlipayNotify($this->config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result){//验证成功
			$status = false;
			$doc = new DOMDocument();
			if ($this->config['sign_type'] == 'MD5') {
				$doc->loadXML($_POST['notify_data']);
			}
			if ($this->config['sign_type'] == '0001') {
				$doc->loadXML($alipayNotify->decrypt($_POST['notify_data']));
			}
			if(!empty($doc->getElementsByTagName( "notify" )->item(0)->nodeValue)){
				//商户订单号
				$status['out_trade_no'] = $doc->getElementsByTagName( "out_trade_no" )->item(0)->nodeValue;
				//支付宝交易号
				$status['trade_no']     = $doc->getElementsByTagName( "trade_no" )->item(0)->nodeValue;
				//交易状态
				$status['trade_status'] = $doc->getElementsByTagName( "trade_status" )->item(0)->nodeValue;
				//支付金额
				$status['total_fee'] = $doc->getElementsByTagName( "total_fee" )->item(0)->nodeValue * 100; //线上分为单位
			}
			return $status;
		}else{
			echo false;
		}
	}

	//同步回调
	public function doBackWap(){
		if(isset($_GET['TRADE_SUCCESS']) && $_GET['TRADE_SUCCESS']=='TRADE_SUCCESS'){
			return $_GET;
		}else{
			return false;
		}
	}

	public function getBank($t){
		$result = array(
			'BOC'     => 'BOCB2C',   //中国银行
			'ICBC'    => 'ICBCB2C',  //中国工商银行
			'CMB'     => 'CMB',      //招商银行
			'CCB'     => 'CCB',      //中国建设银行
			'ABC'     => 'ABC',      //中国农业银行
			'SPDB'    => 'SPDB',     //上海浦东发展银行
			'CIB'     => 'CIB',      //兴业银行
			'GDB'     => 'GDB',      //广发银行
			'FDB'     => 'FDB',      //富滇银行
			'CITIC'   => 'CITIC',    //中信银行
			'HZCB'    => 'HZCBB2C',  //杭州银行
			'SHBANK'  => 'SHBANK',   //上海银行
			'NBBANK'  => 'NBBANK',   //宁波银行
			'SPABANK' => 'SPABANK',  //平安银行
			'PSBC'    => 'POSTGC',   //中国邮政储蓄银行
		);
		return $result[$t];
	}
}