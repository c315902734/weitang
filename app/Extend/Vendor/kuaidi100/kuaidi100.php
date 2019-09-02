<?php

class kuaidi100 {
	public function setConfig($data=array(),$type='web'){
		$config = array(
			'key'     => isset($data['key']) ? $data['key'] : 'XRTDyTdh9033', //商家申请的key
			'param'   => array('callbackurl'=>u("wap/express/response",'',true,false,true)),
		);
		
		/******************/
		/*以下设置请勿修改 - 此配置仅支持即时到账*/
		/******************/
		//格式化配置
		$this->config = $config;
	}

	//订阅请求
	public function subscription($company = '',$number = '',$to = ''){
		//if(!$this->express_delivery($company)) return false;
		if($number == '') return false;
		if($to == '') return false;

		$post_data["schema"] = 'json' ;//$this->express_delivery($company)
		$post_data["param"] = '{"company":"'.$company.'", "number":"'.$number.'","from":"", "to":"'.$to.'", "key":"'.$this->config['key'].'", "parameters":{"callbackurl":"'.$this->config['param']['callbackurl'].'"}}';
		$o=""; 
		foreach ($post_data as $k=>$v)
		{
			$o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
		}
		$post_data=substr($o,0,-1);
		$result = $this->doPost('http://www.kuaidi100.com/poll',$post_data);
	}

	//主动拉取信息
	public function get_express($com,$num){
		//参数设置
		$post_data = array();
		$post_data["customer"] = '*****';
		$key= $this->config['key'] ;
		$post_data["param"] = '{"com":'.$com.',"num":'.$num.'}';

		$url='http://poll.kuaidi100.com/poll/query.do';
		$post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
		$post_data["sign"] = strtoupper($post_data["sign"]);
		$o=""; 
		foreach ($post_data as $k=>$v)
		{
		    $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
		}
		$post_data=substr($o,0,-1);
		$ch = curl_init();
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			$result = curl_exec($ch);
			$data = str_replace("\&quot;",'"',$result );
			$data = json_decode($data,true);
		//print_r($data);die;
	}

	//常用快递
	public function express_delivery($name){
		$data = array(
			'包裹/平邮' => 'youzhengguonei',
			'EMS' => 'ems',
			'EMS-英文结果' => 'emsinten',
			'北京EMS' => 'bjemstckj',
			'顺丰' => 'shunfeng',
			'申通' => 'shentong',
			'圆通' => 'yuantong',
			'中通' => 'zhongtong',
			'汇通' => 'huitongkuaidi',
			'韵达' => 'yunda',
			'宅急送' => 'zhaijisong',
			'天天' => 'tiantian',
			'德邦' => 'debangwuliu',
			'国通' => 'guotongkuaidi',
			'增益' => 'zengyisudi',
			'速尔' => 'suer',
			'中铁物流' => 'ztky',
			'中铁快运' => 'zhongtiewuliu',
			'能达' => 'ganzhongnengda',
			'优速' => 'youshuwuliu',
			'全峰' => 'quanfengkuaidi',
			'京东' => 'jd',
			);
		return isset($data[$name]) ? $data[$name] : false;
	}

	//常用快递
	public function delivery_express($name){
		$data = array(
			'youzhengguonei' => '包裹/平邮',
			'ems' => 'EMS',
			'emsinten' => 'EMS-英文结果',
			'bjemstckj' => '北京EMS',
			'shunfeng' => '顺丰',
			'shentong' => '申通',
			'yuantong' => '圆通',
			'zhongtong' => '中通',
			'huitongkuaidi' => '汇通',
			'yunda' => '韵达',
			'zhaijisong' => '宅急送',
			'tiantian' => '天天',
			'debangwuliu' => '德邦',
			'guotongkuaidi' => '国通',
			'zengyisudi' => '增益',
			'suer' => '速尔',
			'ztky' => '中铁物流',
			'zhongtiewuliu' => '中铁快运',
			'ganzhongnengda' => '能达',
			'youshuwuliu' => '优速',
			'quanfengkuaidi' => '全峰',
			'jd' => '京东',
			);
		return isset($data[$name]) ? $data[$name] : false;
	}

	//请求远端post提取返回数据
	protected function doPost($url,$data=array(),$header=false){
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
		if($header=='json') curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($data)));
		if($header=='xml') curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept:application/xml','Content-type: application/xml'));
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}
}
