<?php
class publicChinapay {
	public $config = array();

	public function __construct(){
		$config = array(
			'merid'         => "591211602290001",
			'url'           => 'https://payment.chinapay.com/CTITS/service/rest/page/nref/000000000017/0/0/0/0/0',
			'input_charset' => 'utf-8',
		);
		$this->config = $config;
	}

	public function pays($oid,$orderid,$prices,$bgurl,$pageurl){
		$parameter = array(
				"Version"        => '20140728',
				"MerId"          => $this->config['merid'], //商户号
				"MerOrderNo"     => $oid, //商户订单号
				"TranDate"       => date("Ymd"), //商户交易日期
				"TranTime"       => date("His"), //商户交易时间
				"OrderAmt"       => $prices*100, //订单金额
				"BankInstNo"     => "",
				"BusiType"       => "0001", //业务类型
				"CommodityMsg"   => '订单'.$orderid,
				"CurryNo"        => "CNY",
				"AccessType"     => "0",
				"MerBgUrl"       => $bgurl, //商户后台通知地址
				"MerPageUrl"     => $pageurl, //商户前台通知地址
				"MerSplitMsg"    => ""
		);
		$signature = $this->getSign($parameter);
		$parameter['Signature'] = $signature;
		$html_text = $this->buildRequestForm($parameter,"post", "确认");
		echo $html_text;exit;
	}

	public function getSign($data){
		require_once("SecssUtil.class.php");
		$securityPropFile="./app/Extend/Vendor/chinapay/security.properties";
		$secssUtil = new SecssUtil();
		$secssUtil->init($securityPropFile); //初始化安全控件：
		$secssUtil->sign($data);
		if("00"!==$secssUtil->getErrCode()){
			echo"签名过程发生错误，错误信息为-->".$secssUtil->getErrMsg();
			return false;
		}
		$signature = $secssUtil->getSign();
		return $signature;
	}

	public function verifyData($data){
		require_once("SecssUtil.class.php");
		$securityPropFile="./app/Extend/Vendor/chinapay/security.properties";
		$secssUtil = new SecssUtil();
		$secssUtil->init($securityPropFile); //初始化安全控件：
		$secssUtil->verify($data);
		if("00"!==$secssUtil->getErrCode()){
			echo"验签过程发生错误，错误信息为-->".$secssUtil->getErrMsg();
			return;
		}
		return $data;
	}



	function buildRequestForm($para_temp, $method, $button_name) {
		
		$sHtml = "<form id='chinapaysubmit' name='chinapaysubmit' action='".$this->config['url']."_input_charset=".trim(strtolower($this->config['input_charset']))."' method='".$method."'>";
		foreach ($para_temp as $key=>$val) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='".$button_name."' style='display:none'></form>";
		
		$sHtml = $sHtml."<script>document.forms['chinapaysubmit'].submit();</script>";
		
		return $sHtml;
	}
}