<?php

class weixinAction extends frontendAction
{   
	public function index() {
		$echoStr = $_GET["echostr"];
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
        $data = $this->getMessage();
	}

	private function checkSignature(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
		$token = C('ins_weixin_token');
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

	public function getMessage(){
		$data = $GLOBALS["HTTP_RAW_POST_DATA"];
		$xml = false;
		if(!empty($data)) $xml = (array)simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		return $xml;
	}

	public function login(){
		if(!C('ins_weixin_login')){
			$this->_404();
		}
		/* 登陆后返回地址 */
		if($this->_get('ret_url','trim')){
			session('weixin_login_ret_url', $this->_get('ret_url','trim'));
		}else{
			session('weixin_login_ret_url', $_SERVER['HTTP_REFERER']);
		}
		/*$wx_config = C('WAP_WX_CONFIG');
		$config['appid'] = $wx_config['app_id'];
		$config['appsecret'] = $wx_config['app_secret'];
		$config['callback'] = U('weixin/login_response','',true,false,true);*/
		$config['appid'] = C('ins_weixin_appid');
		$config['appsecret'] = C('ins_weixin_appsecret');
		$config['callback'] = U('weixin/login_response','',true,false,true);
		Vendor('weixinapi.weixinapi');
		$weixinapi = new weixinapi($config);
		$weixinapi->locationAuthorize();
	}


	public function login_response(){
		$wx_config = C('WAP_WX_CONFIG');
		$config['appid'] = $wx_config['app_id'];
		$config['appsecret'] = $wx_config['app_secret'];
		$config['callback'] = U('weixin/login_response','',true,false,true);
		Vendor('weixinapi.weixinapi');
		$weixinapi = new weixinapi($config);
		$result = $weixinapi->getAuthorize();

		 /* 推荐人 */
		$invite_uid = session('invite_uid');  

		if(!$this->visitor->is_login){
			//查询用户是否存在
			$uid = D('user')->where(array('openid'=>$result['openid']))->getField('id');
			if(!$uid){
				$uid = D('user')->add(array(
					'openid' => $result['openid'],
					'username' => $result['nickname'],
					'img' => $result['headimgurl'],
					'reg_time' => date('Y-m-d H:i:s'),
					'reg_ip' => get_client_ip(),
					'tele' => 'P'.time(),
				));
			}
			$this->visitor->login($uid);
			$this_user_invite = $this->visitor->get('invite_uid');
			$iuser = D('user')->where(array('id'=>$invite_uid))->field('id,topkey,tgroup,tgroup_1,tgroup_2,tgroup_3,tgroup_4')->find();
			if ($iuser && !$this_user_invite) {
				$save_data        = [
					'topkey' => $iuser['topkey'],
					'invite_uid' => $invite_uid,
				];
				($iuser['tgroup_1'] > 0) && $save_data['tgroup_1'] = $iuser['tgroup_1'];
				($iuser['tgroup_2'] > 0) && $save_data['tgroup_2'] = $iuser['tgroup_2'];
				($iuser['tgroup_3'] > 0) && $save_data['tgroup_3'] = $iuser['tgroup_3'];
				($iuser['tgroup_4'] > 0) && $save_data['tgroup_4'] = $iuser['tgroup_4'];
				if(in_array($iuser['tgroup'],[1,2,3,4])){
					$save_data['tgroup_'.$iuser['tgroup']] = $iuser['id'];
				}
				D('user')->where(['id' => $uid])->save($save_data);
			}elseif($invite_uid > 0){
				$this->redirect('passport/binding_invite',array('invite_uid'=>$invite_uid));exit;
			}
		}else{
			$save['openid'] = $result['openid'];
			if(!$this->visitor->info['img']){
				$save['img'] = $result['headimgurl'];
			}
			/* 绑定推荐人 */
			$this_user_invite = $this->visitor->get('invite_uid');
			$iuser = D('user')->where(array('id'=>$invite_uid))->field('id,topkey,tgroup,tgroup_1,tgroup_2,tgroup_3,tgroup_4')->find();
			if ($iuser && !$this_user_invite) {
				$save['topkey']     = $iuser['topkey'];
				$save['invite_uid'] = $invite_uid;
				($iuser['tgroup_1'] > 0) && $save['tgroup_1'] = $iuser['tgroup_1'];
				($iuser['tgroup_2'] > 0) && $save['tgroup_2'] = $iuser['tgroup_2'];
				($iuser['tgroup_3'] > 0) && $save['tgroup_3'] = $iuser['tgroup_3'];
				($iuser['tgroup_4'] > 0) && $save['tgroup_4'] = $iuser['tgroup_4'];
				if(in_array($iuser['tgroup'],[1,2,3,4])){
					$save['tgroup_'.$iuser['tgroup']] = $iuser['id'];
				}
			}
			D('user')->where(array('id'=>$this->uid))->save($save);
		}
		$ret_url = session('weixin_login_ret_url');
		session('weixin_login_ret_url',null);
		session('invite_uid', null);
		if($ret_url){
			header("Location:".$ret_url);
		}else{
			$this->redirect('user/index');
		}
	}

}