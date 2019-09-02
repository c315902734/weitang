<?php

class mbaseAction extends frontendAction
{
    var $WEIXIN_CONFIG;

    public function _initialize()
    {
        parent::_initialize();
        $this->WEIXIN_CONFIG = C("WAP_WX_CONFIG");
        if (MODULE_NAME == 'user') {
            //验证是否登录
            $this->checkLogin();
        }
    }

    protected function checkLogin()
    {
        if (intval($this->visitor->info['id']) <= 0 && !in_array(ACTION_NAME, array('index', 'login', 'register', 'binding', 'ajax_check', 'forget_password', 'getSMSCode', 'other_set', 'disclaimer', 'about'))) {
            redirect(U('user/login'));
        }
    }

    protected function check_user_subscribe_weixin()
    {
        $openid = session('openid');
        if (empty($openid)) {
            $params = array(
                'appid'         => $this->WEIXIN_CONFIG['app_id'],
                'redirect_uri'  => U('mall/public/snsapiBaseResponse', array('redirect_uri' => rtrim(get_siteroot(), '/') . $_SERVER['REQUEST_URI']),
                    true, false, true
                ),
                'response_type' => 'code',
                'scope'         => 'snsapi_base',
            );

            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?" . http_build_query($params) . '#wechat_redirect';
//            print_r($url);exit();
            redirect($url);
        }
    }

    protected function requireWXBrowser()
    {
        $openid = session('openid');
        if ($openid) {
            $uid       = D('user')->where(compact('openid'))->getField('id');
            $wx_user   = session('wx_user');
            $username  = $weixin = $wx_user['nickname'];
            $weixin    = $wx_user['nickname'];
            $img       = $wx_user['headimgurl'];
            $last_ip   = $_SERVER["REMOTE_ADDR"];
            $last_time = date('Y-m-d H:i:s', time());
            $tele      = time() . rand(10000, 99999);
            $password  = md5(time());
            $reg_time  = $last_time = date('Y-m-d H:i:s', time());
            $reg_ip    = $last_ip = $_SERVER["REMOTE_ADDR"];
            $status    = 1;

            if (empty($uid)) {
                //跳转到注册页面
                // redirect(U('default/passport/register'));
                $uid = D('user')->add(compact('username', 'weixin', 'tele', 'password', 'reg_time', 'last_time', 'reg_ip', 'last_ip', 'status', 'openid', 'img'));
            }
            else {
                D('user')->where(array('id' => $uid))->save(compact('weixin', 'img', 'last_ip', 'last_time'));
                $where['uid']   = $uid;
                $where['type']  = 'wx';
                $where['keyid'] = md5($openid);
                $bind_count     = D('user_bind')->where($where)->count();
                if (!$bind_count) {
                    $data             = $where;
                    $data['add_time'] = date('Y-m-d H:i:s');
                    D('user_bind')->add($data);
                }
            }
            $this->visitor->login($uid, true);
        }
        else {
            /**/
            $params = array(
                'appid'         => $this->WEIXIN_CONFIG['app_id'],
                'redirect_uri'  => U('m/public/snsapiBaseResponse', array('redirect_uri' => rtrim(get_siteroot(), '/') . $_SERVER['REQUEST_URI']),
                    true, false, true
                ),
                'response_type' => 'code',
                'scope'         => 'snsapi_userinfo',
            );

            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?" . http_build_query($params) . '#wechat_redirect';
            redirect($url);
        }
    }
    // 加载wap端微信信息
    public function load_wx_info()
    {
        // 获取微信配置信息
        $config  = C('WAP_WX_CONFIG');
        // 缓存微信信息
        $wx_info = F('wx_info');
        // access_token是公众号的全局唯一票据，公众号调用各接口时都需使用access_token
        // 若access_token 的值为 空   或者 access_token 的值已过期
        if (empty($wx_info['access_token']['value']) || $wx_info['access_token']['expire'] < time() - 7200) {
            $url  = 'https://api.weixin.qq.com/cgi-bin/token';
            $data = array(
                'grant_type' => 'client_credential',
                'appid'      => $config['app_id'],
                'secret'     => $config['app_secret'],
            );
            // 获取access_token
            $res  = json_decode(Http::get($url, $data), true);
            if (isset($res['access_token'])) {
                $wx_info['access_token'] = array(
                    'value'  => $res['access_token'],
                    'expire' => time() + $res['expires_in'],
                );
                F('wx_info', $wx_info);
            }
        }
        $wx_info = F('wx_info');
        if (empty($wx_info['access_token']['value'])) {
            return;
        }

        if (empty($wx_info['jsapi_ticket']['value']) || $wx_info['jsapi_ticket']['expire'] < time() - 7200) {
            $url  = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';
            $data = array(
                'access_token' => $wx_info['access_token']['value'],
                'type'         => 'jsapi',
            );
            $res  = json_decode(Http::get($url, $data), true);
            if (isset($res['ticket'])) {
                // 这个ticket就是jsapi_ticket
                $wx_info['jsapi_ticket'] = array(
                    'value'  => $res['ticket'],
                    'expire' => time() + $res['expires_in'],
                );
                // 将获取的jsapi_ticket缓存起来，防止触发频率上限
                F('wx_info', $wx_info);
            }
        }
        // 由于获取jsapi_ticket的API调用次数有限，频繁刷新jsapi_ticket会导致调用API受限
        // 影响自身业务，开发者必须在自己的服务号全局缓存jsapi_ticket
        $wx_info = F('wx_info');
        // 缓存的access_token 和 jsapi_ticket 的值和有效时间
        $this->assign('wx_info', $wx_info);
        //生成签名
        $package = array(
            'noncestr'     => strval(md5(time())),
            'jsapi_ticket' => $wx_info['jsapi_ticket']['value'],
            'timestamp'    => time(),
            'url'          => get_current_url(),
        );
        // 字典序排序生成签名
        ksort($package);
        $temp = array();
        foreach ($package as $key => $val) {
            $temp[] = $key . '=' . $val;
        }
        $temp                 = implode('&', $temp);
        $signature            = sha1($temp);
        $wx_config            = array(
            'debug'     => $config['debug'],
            'appId'     => $config['app_id'],
            'timestamp' => $package['timestamp'],
            'nonceStr'  => $package['noncestr'],
            'signature' => $signature,
            'jsApiList' => array(
                'checkJsApi',
                'onMenuShareTimeline',
                'onMenuShareAppMessage',
                'onMenuShareQQ',
                'onMenuShareWeibo',
                'onMenuShareQZone',
                'hideMenuItems',
                'showMenuItems',
                'hideAllNonBaseMenuItem',
                'showAllNonBaseMenuItem',
                'translateVoice',
                'startRecord',
                'stopRecord',
                'onVoiceRecordEnd',
                'playVoice',
                'onVoicePlayEnd',
                'pauseVoice',
                'stopVoice',
                'uploadVoice',
                'downloadVoice',
                'chooseImage',
                'previewImage',
                'uploadImage',
                'downloadImage',
                'getNetworkType',
                'openLocation',
                'getLocation',
                'hideOptionMenu',
                'showOptionMenu',
                'closeWindow',
                'scanQRCode',
                'chooseWXPay',
                'openProductSpecificView',
                'addCard',
                'chooseCard',
                'openCard'
            ),
        );
        $GLOBALS['wx_config'] = $wx_config;
        $this->assign('wx_config', json_encode($wx_config));
    }

    public function show_footer()
    {
        $this->assign('is_show_footer', true);
    }

    //是否有未支付订单
    protected function hasNoPayOrder($uid = 0)
    {
        if (!$uid) {
            $uid = $this->get_visitor_id();
        }
        $where = [
            'uid'    => $uid,
            'status' => 0,
        ];
        return D('order')->where($where)->count() > 0;
    }

    //是否有未支付相同商品订单
    protected function hasNoPayOrderItem($id = 0)
    {
        $uid = $this->get_visitor_id();
        $sql = 'SELECT count(*) AS c FROM ins_order_item AS i LEFT JOIN ins_order AS o ON o.id=i.order_id WHERE o.status=0 AND o.uid=' . $uid . ' AND i.item_id=' . $id;
        $res = (new Model())->query($sql);
        return $res[0]['c'];
    }

    //是否有限制期限内的商品
    protected function hasOrderScoreTimeItem($id)
    {
        $item = D('item')->where(compact('id'))->find();
        if ($item['score_times'] == 0) { //如果限购时间为0 则返回是,无法购买
            return false;
        }

        $uid        = $this->get_visitor_id();
        $start_time = date('Y-m-d H:i:s', time() - 3600 * 24 * $item['score_times']);
        $sql        = "select count(i.id) as total from %DB_PREFIX%order_item as i
         left join %DB_PREFIX%order as o on o.id=i.order_id
         where o.status!=9 and i.uid=$uid and i.add_time>'$start_time' and i.item_id=$id";

        $res = (new Model())->query($sql);
        return $res[0]['total'] > 0;
    }


    //限购
    protected function hasOrderLimitedTimeItem($id)
    {
        $item = D('item')->where(compact('id'))->find();
        if ($item['score_times'] == 0) { //如果限购时间为0 不限制
            return false;
        }
		
        $start_time = date('Y-m-d H:i:s', time() - 3600 * 24 * $item['score_times']);

        $nums = D('order_item')->where(['add_time'=>['egt',$start_time],'item_id'=>$id])->sum('nums');

		if($nums >= $item['score_maxs']){
			return true;
		}
		return false;

    }
    protected function require_login()
    {
        if (!$this->is_visitor_login()) {
            $url = full_url('passport/login', ['ret_url' => $_SERVER['HTTP_REFERER']]);
            if (IS_AJAX) {
                $this->ajaxResultError('请登录', [
                    'url' => $url
                ]);
            }
            else {
                header("Location:$url");
            }
        }
    }

    protected function is_auth_name($id = 0)
    {
        if (!$id) {
            $id = $this->get_visitor_id();
        }

        $user = D('user')->get($id, 'is_auth');
        return $user['is_auth'] == 1;
    }

    protected function ia_auth_pay($id = 0)
    {
        if (!$id) {
            $id = $this->get_visitor_id();
        }

        $user = D('user')->get($id, 'alipay_status');
        return $user['alipay_status'] == 1;
    }
}