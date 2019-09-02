<?php

class wapbaseAction extends frontendAction
{
    var $WEIXIN_CONFIG;

    public function _initialize()
    {
        parent::_initialize();
        $this->WEIXIN_CONFIG = C("WAP_WX_CONFIG");
        //购物车数量
        $cart_num = D('cart')->where(array('uid' => $this->visitor->info['id']))->count();
        $this->assign('cart_num', $cart_num);
        if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
			session('openid','local_development'); 
        }
		//$this->requireWXBrowser();
    }

    protected function requireWXBrowser()
    {
        $openid=session('openid');
        if ($openid) {
            $uid = D('user')->where(compact('openid'))->getField('id');
            $username = time() . rand(10000, 99999);
            $tele = time() . rand(10000, 99999);
            $password = md5(time());
            $reg_time = $last_time = date('Y-m-d H:i:s', time());
            $reg_ip = $last_ip = $_SERVER["REMOTE_ADDR"];
            $status = 1;

            if (empty($uid)) {
                $uid = D('user')->add(compact('username', 'tele', 'password', 'reg_time', 'last_time', 'reg_ip', 'last_ip', 'status', 'openid'));
            }
            $this->visitor->login($uid, true);
        } else {
            $params = array(
                'appid'         => $this->WEIXIN_CONFIG['app_id'],
                'redirect_uri'  => U('wap/public/snsapiBaseResponse', array(), true, false, true),
                'response_type' => 'code',
                'scope'         => 'snsapi_base',
                'state'         => base64_encode(_json_encode(array('redirect_uri' => rtrim(get_siteroot(), '/') . $_SERVER['REQUEST_URI']))),
            );
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?" . http_build_query($params) . '#wechat_redirect';
            redirect($url);
        }
    }

}