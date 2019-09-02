<?php

class oauthAction extends frontendAction
{
    public function index()
    {
        $mod  = $this->_get('mod', 'trim');
        $type = $this->_get('type', 'trim', 'login');
        !$mod && $this->_404();
        if ('unbind' == $type) {
            !$this->visitor->is_login && $this->redirect('user/login');
            D('user_bind')->where(array('uid' => $this->visitor->info['id'], 'type' => $mod))->delete();
            $this->redirect('user/bind');
        }
        if ($mod == 'wx') {
            $ret_url = $this->_get('ret_url', 'trim');
            if ($ret_url == '') {
                $ret_url = $_SERVER["HTTP_REFERER"];
            }
            // 关于网页授权的两种scope的区别说明:
            //以snsapi_base为scope发起的网页授权，是用来获取进入页面的用户的openid的，并且是静默授权并自动跳转到回调页的。
            //用户感知的就是直接进入了回调页（往往是业务页面）
            //  以snsapi_userinfo为scope发起的网页授权，是用来获取用户的基本信息的。但这种授权需要用户手动同意，
            //  并且由于用户同意过，所以无须关注，就可在授权后获取该用户的基本信息。
            $this->assign('ret_url', $ret_url);
            // $callback_url 为回调页url
            $callback_url = U('mall/oauth/wx_callback', array('ret_url' => $ret_url));
            $config       = C('WAP_WX_CONFIG');
            $params       = array(
                //  公众号的唯一标识
                'appid'         => $config['app_id'],
                // 授权后重定向的回调链接地址，请使用urlEncode对链接进行处理  TP中U方法参数：U('地址','参数','伪静态','是否跳转','显示域名');
                'redirect_uri'  => U('mall/public/snsapiBaseResponse', array('re_uri' => $callback_url), true, false, true),
                // 返回类型，请填写code
                'response_type' => 'code',
                // 应用授权作用域:
                // snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），
                // snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。
                // 并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
                'scope'         => 'snsapi_userinfo',//'snsapi_login',
            );
            /*
             * 第一步：用户同意授权，获取code
             *     在确保微信公众账号拥有授权作用域（scope参数）的权限的前提下（服务号获得高级接口后，
             *     默认拥有scope参数中的snsapi_base和snsapi_userinfo），引导关注者打开如下页面：
            https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI
            &response_type=code&scope=SCOPE&state=STATE#wechat_redirect
            若提示“该链接无法访问”，请检查参数是否填写错误，是否拥有scope参数对应的授权作用域权限。
            参数说明: appid,redirect_uri,response_type,scope 四个参数上面已做说明，都是必须要的参数
                    state 重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节 ，但不是必须的参数，可以不要
                    #wechat_redirect 无论直接打开还是做页面302重定向时候，必须带此参数   是必须的参数
            特别注意：由于授权操作安全等级较高，所以在发起授权请求时，微信会对授权链接做正则强匹配校验，如果链接的参数顺序不对，授权页面将无法正常访问
            */
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?" . http_build_query($params) . '#wechat_redirect';
            // 特别注意：跳转回调redirect_uri，应当使用https链接来确保授权code的安全性
            redirect($url);
        }
        else {
            $oauth = new oauth($mod);
            cookie('callback_type', $type);
            return $oauth->authorize();
        }
    }

    function wx_callback()
    {
        $wx_user = session('wx_user');
        $openid  = md5($wx_user['unionid']);

        if ($openid) {
            $opeids    = [$openid];
            $where     = [
                'openid' => ['in', $opeids],
            ];
            $uid       = D('user')->where($where)->getField('id');
            $username  = $weixin = $wx_user['nickname'];
            $weixin    = $wx_user['nickname'];
            $img       = $wx_user['headimgurl'];
            $last_ip   = $_SERVER["REMOTE_ADDR"];
            $last_time = date('Y-m-d H:i:s', time());
            $tele      = 'wx' . time() . rand(100, 999);
            $password  = md5(time());
            $reg_time  = $last_time = date('Y-m-d H:i:s', time());
            $reg_ip    = $last_ip = $_SERVER["REMOTE_ADDR"];
            $status    = 1;

            if (empty($uid)) {
                $uid = D('user')->add(compact('username', 'weixin', 'tele', 'password', 'reg_time', 'last_time', 'reg_ip', 'last_ip', 'status', 'openid', 'img'));
            }
            else {
                D('user')->where(array('id' => $uid))->save(compact('weixin', 'img', 'last_ip', 'last_time', 'openid'));
            }
            //没有绑定跳转到绑定页面
            $bind_count = D('user_bind')->where(array('uid' => $uid, 'keyid' => ['in', $opeids]))->count();
            if (!$bind_count) {
                D('user_bind')->add(array('uid' => $uid, 'type' => 'wx', 'keyid' => $openid, 'add_time' => date('Y-m-d H:i:s')));
            }
            else {
                $keyid = $openid;
                D('user_bind')->where(['uid' => $uid, 'type' => 'wx'])->save(compact('keyid'));
            }
            $this->visitor->login($uid, true);
            $ret_url = $this->_get('ret_url', 'trim');
            if ($ret_url) {
                if (strpos($ret_url, 'passport') > 0) {
                    redirect(U('user/index'));
                }
                else {
                    redirect($ret_url);
                }
            }
            else {
                redirect(U('index/index'));
            }
        }
        else {
            $this->error('出错了');
        }
    }

    function callback()
    {
        $mod = $this->_get('mod', 'trim');
        !$mod && $this->_404();
        $callback_type = cookie('callback_type');
        $oauth         = new oauth($mod);
        $rk            = $oauth->NeedRequest();
        $request_args  = array();
        foreach ($rk as $v) {
            $request_args[$v] = $this->_get($v);
        }
        switch ($callback_type) {
            case 'login':
                $url = $oauth->callbackLogin($request_args);
                break;
            case 'bind':
                $url = $oauth->callbackbind($request_args);
                break;
            default:
                $url = U('index/index');
                break;
        }
        cookie('callback_type', null);
        redirect($url);
    }
}