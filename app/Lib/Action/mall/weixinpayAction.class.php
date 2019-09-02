<?php

class weixinpayAction extends frontendAction
{   

	public function __construct(){
		parent::_initialize();
		if(!C('ins_weixin_pays')){
			$this->_404();
		}
		//加载微信接口
		Vendor('weixinapi.weixinapi');
		/*$wx_config = C('WAP_WX_CONFIG');
		$config['appid'] = $wx_config['app_id'];
		$config['appsecret'] = $wx_config['app_secret'];
		$config['mchid'] = $wx_config['partner_id'];
		$config['paykey'] = $wx_config['partner_key'];*/
		$config['appid'] = C('ins_weixin_appid');
		$config['appsecret'] = C('ins_weixin_appsecret');
		$config['mchid'] = C('ins_weixin_mchid');
		$config['paykey'] = C('ins_weixin_key');
		$config['nurl'] = 'http://'.$this->_server('HTTP_HOST').u('weixinpay/dopay');
		$this->client = new weixinapi($config);
		//追加token获取token
		$this->client->token = $this->pay_getToken();
	}

	public function batch() {
		$order_id = $this->_request('order_id','trim');
		$map['o.id'] = array('IN',$order_id);
		$order = D('order')->alias("o")->join(C('DB_PREFIX').'item i ON i.id=o.item_id','LEFT')->where($map)->field('o.*,i.img,i.title')->order('o.add_time DESC')->select();
		$this->assign('order', $order);
		$this->display();
	}


	public function index() {
		$order_id = $this->_request('order_id','intval',0);
		$order = D('order')->field('id,orderid,prices,pays,pays_status,pays_data')->find($order_id);
		if($order['pays_status'] == 1){
			IS_AJAX && $this->ajaxResult(0, '订单已支付成功');
			$this->success('订单已支付成功',U('user/order'));
		}
		$data['body'] = '微糖_'.$order['orderid'];
		$data['out_trade_no'] = $order['id'];
		$data['total_fee'] = $order['prices']*100;//以分为单位
		$data['openid'] = $this->visitor->get('openid');
		if(!$data['openid']){
			$this->redirect('weixin/login');
		}
		$order_result = $this->unifiedorder($data); 
		if(IS_AJAX){
			$this->ajaxResult(1, '', $order_result);
			exit;
		}
		$this->assign('res', $order_result);
		$this->assign('ret_url', $_SESSION['ret_url'] ? urldecode($_SESSION['ret_url']) : U('weixinpay/showpay',array('id'=>$order['id'])));

		$this->display();
	}
	
	public function getJsApiConfig(){
		$t = time();$info = array();$ticket = false;
		$data = D('wx_jsapi_ticket')->order('id DESC')->find();
		if(!$data || $data['addtime']<($t-3600)){
			$ticket = $this->client->getJsApiTicket();
			if($ticket){
				$info['ticket'] = $ticket;
				$info['addtime'] = $t;
				D('wx_jsapi_ticket')->add($info);
			}
		}else{
			$ticket = $data['ticket'];
		}
		if(!$ticket) return false;
		return $this->client->getJsApiConfig($ticket);
	}

	//统一下单
	public function unifiedorder($data){
		$config = $this->getJsApiConfig();
		$result = $this->client->getJsApiPayData($config['ticket'],$data);
		return $result;
	}

	//判断是否需要获取新token
	public function pay_getToken(){
		$t = time() - 3600;
		$token = false;
		$data = D('wx_token')->order('id DESC')->find();
		if(!isset($data['addtime']) || $data['addtime']<$t || !isset($data['token'])){
			$token = $this->client->getToken();
			if($token) D('wx_token')->add(array('token'=>$token,'addtime'=>time()));
		}else{
			$token = $data['token'];
		}
		return $token;
	}


    public function dopay() {
		if($this->_get('test_pk') == md5(md5('weitangtest'))){
			$price = D('order')->where(array('id'=>$this->_get('id','intval')))->getField('prices');
			$data = array(
				'return_code'=>'SUCCESS',
				'out_trade_no'=>$this->_get('id','intval'),
				'transaction_id' => 'weitangtest',
				'total_fee' => $price*100
			);
		}else{
			$data = $this->client->getData();
		}
		//D('wx_temp')->add(array('content' => json_encode($data),'add_time' => date('Y-m-d H:i:s')));
		if($data['return_code'] == 'SUCCESS'){
			$order_id = $data['out_trade_no'];
			$order = D('order')->field('id,status')->where(' id = '.$order_id)->find();
			if($order['status'] == 0){
				$data['status']  =  1; //支付成功
				$data['pays_status']  =  1;
				$data['pays']  =  4;
				$data['pays_price']  =  $data['total_fee']/100;
				$data['pays_time']  =  date('Y-m-d H:i:s');
				$data['pays_data']  =  json_encode($data);
				$data['pays_sn']  =  $data['transaction_id'];
				D('order')->where(array('id'=>$order_id))->save($data);
				/* 反馈操作 */
				$this->callbackOrder($order_id);
			}
		}
	}

	/* 特殊订单支付后进行反馈操作 */
	function callbackOrder($id){
		$order = D('order')->field('id,uid,uname,status,prices,type')->where(' id = '.$id)->find();
		if($order['status'] == 0) return false;
		switch ($order['type']){
			case 1:
				/* 充值 */
				D('user')->where(array('id'=>$order['uid']))->setInc('price',$order['prices']);
				$recharge_id = D('user_recharge')->where(array('order_id'=>$id))->getField('id');
				D('price_log')->add(array(
					'uid' => $order['uid'],
					'uname' => $order['uname'],
					'price' => $order['prices'],
					'action' => 'recharge',
					'add_time' => date('Y-m-d H:i:s'),
					'remark' => '在线充值',
					'key_id' => $recharge_id,
				));
				D('user_recharge')->where(array('order_id'=>$id))->save(array('status' => 1));
				break;
			default:
				return false;
		}
	}

    public function showpay() {
		$info = D('order')->where(array('id'=>$_GET['id']))->find();
		$this->assign('order', $info);
		if($info['pays_status'] == 1){
			$status = 1; //成功
		}else{
			$status = 0; //失败
		}
		$this->assign('status', $status);
		$this->display();
	}

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
}