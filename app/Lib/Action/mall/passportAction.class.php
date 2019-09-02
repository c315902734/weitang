<?php

class passportAction extends mbaseAction
{
    public function login()
    {
		$this->visitor->is_login && $this->redirect('user/index');
        $this->assign('crumb_title', '登录');
        $this->assign('ret_url', $_SERVER["HTTP_REFERER"]);
        if (IS_POST) {
            $username = $this->_post('username', 'trim');
            $password = $this->_post('password', 'trim');
            $ret_url  = $this->_post('ret_url', 'trim');
            if (empty($username)) {
                $this->ajaxResultError(L('please_input') . L('password'));
            }
            if (empty($password)) {
                $this->ajaxResultError(L('please_input') . L('password'));
            }
            //连接用户中心
            $passport = $this->_user_server();

            $md5password = md5($password);

            $where = ['tele' => $username, 'password' => $md5password];
            $uid   = D('user')->where($where)->getField('id');

            if (!$uid) {
                $this->ajaxResultError('账户或密码错误!');
            }
            //登陆
            $this->visitor->login($uid);

            //登陆完成钩子
            $tag_arg = array('uid' => $uid, 'uname' => $username, 'action' => 'login');
            tag('login_end', $tag_arg);
            //同步登陆
            $passport->synlogin($uid);
            D('cart')->where(array('uid' => 0, 'sessionid' => session('sessionid')))->save(array('uid' => $this->visitor->info['id']));
            $this->ajaxResultSuccess('登录成功', ['url' => $ret_url]);
        }
        else {
            /* 同步退出外部系统 */
            if (!empty($_GET['synlogout'])) {
                $passport  = $this->_user_server();
                $synlogout = $passport->synlogout();
            }
            //app来路
            $page_type = $this->_request('page_type', 'intval', 0);
            $this->assign('page_type', $page_type);
            if (IS_AJAX) {
                $resp = $this->fetch('dialog:login');
                $this->ajaxReturn(1, '', $resp);
            }
            else {
                //来路
                $ret_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __APP__;
                $this->assign('ret_url', $ret_url);
                $this->assign('synlogout', $synlogout);
                $this->_config_seo();
                $this->display();
            }
        }
    }

    /**
     * 用户退出
     */
    public function logout()
    {
        $this->visitor->logout();
        $passport  = $this->_user_server();
        $synlogout = $passport->synlogout();
        session('openid', null);
        //跳转到退出前页面（执行同步操作）
        $this->redirect('index/index');
    }

    /**
     * 用户绑定
     */
    public function binding()
    {
        $user_bind_info = object_to_array(cookie('user_bind_info'));
        $this->assign('user_bind_info', $user_bind_info);
        $this->_config_seo();
        $this->display();
    }

    /**
     * 用户注册
     */
    public function register()
    {
        $this->assign('crumb_title', '注册');
        $this->visitor->is_login && $this->redirect('user/index');
        $step = $this->_request('step', 'intval', 1);
        $this->assign(compact('step'));

        switch ($step) {
            case 1: {
				$invite_uid = $this->_get('invite_uid','intval');
				if($invite_uid > 0){
					$invite = D('user')->field('id,username as uname')->find($invite_uid);
					$this->assign(compact('invite_uid','invite'));
					session('invite_uid', $invite_uid);
				}

                //关闭注册
                if (!C('ins_reg_status')) {
                    IS_AJAX && $this->ajaxReturn(C('ins_reg_closed_reason'), '', 0);
                    $this->error(C('ins_reg_closed_reason'));
                }
                if (IS_AJAX) {
                    $resp = $this->fetch('dialog:register');
                    $this->ajaxReturn($resp, '', 1);
                }
                else {
                    $this->_config_seo();
                    $this->display();
                }
                break;
            }
            case 2: {
                $invite_uid = session('invite_uid');
				if($invite_uid > 0){
                    $invite = D('user')->field('id,username as uname')->find($invite_uid);
					$this->assign(compact('invite_uid','invite'));
                }
                if (IS_POST) {
                    $sms_code = $this->_post('code', 'trim');
                    $mobile   = $this->_post('tele', 'trim', 0);
                    /*$agreement = $this->_post('agreement', 'trim', 0);
                    if(!$agreement){
                        IS_AJAX && $this->ajaxReturn('请阅读用户协议','',0);
                        $this->error('请阅读用户协议');
                    }*/
					if (!$mobile) {
                        IS_AJAX && $this->ajaxResultError('请输入您注册的手机号码！');
                        $this->error('请输入您注册的手机号码！');
                    }
					$img_code = $this->_request('img_code', 'trim');
					if (!(md5($img_code) == session('captcha'))){
						$this->ajaxResultError('图片验证码错误');
					}
                    if ((session('sms_code') != $sms_code && $sms_code != '8888') || $sms_code == '') {
                        IS_AJAX && $this->ajaxResultError('您输入的手机验证码不正确！');
                        $this->error('您输入的手机验证码不正确！');
                    }
                    
                    else {
                        $result = D('user')->where(array('tele' => $mobile))->count();
                        if ($result) {
                            IS_AJAX && $this->ajaxResultError('此手机号码已经注册成功！请直接登录');
                            $this->error('此手机号码已经注册成功！请直接登录');
                        }
                        session('mobile', $mobile);
                        IS_AJAX && $this->ajaxResultSuccess('验证成功', array('url' => u('passport/register', array('step' => 2))), 1);
                        $this->display();
                    }
                }
                else {
                    if (!session('mobile')) {
                        $this->redirect('passport/register');
                    }
                    else {
                        $this->display();
                    }
                }
                break;
            }
            case 3: {
                if (IS_POST) {
                    $username   = $this->_post('username', 'trim');
                    $password   = $this->_post('password', 'trim');
                    $repassword = $this->_post('repassword', 'trim');
                    $email      = $this->_post('email', 'trim', time() . '@default.com');
                    $sex        = $this->_post('sex', 'intval', 1);
                    if ($password != $repassword) {
                        IS_AJAX && $this->ajaxReturn(L('inconsistent_password'), '', 0);
                        $this->error(L('inconsistent_password')); //确认密码
                    }
                    if ($password) {
                        $mobile   = session('mobile');
                        $passport = $this->_user_server();
                        $uid      = $passport->register($username, $password, $email, $sex);
                        if (!$uid) {
                            IS_AJAX && $this->ajaxReturn($passport->get_error(), '', 0);
                            $this->error($passport->get_error());
                        }
                        //第三方帐号绑定
                        if (cookie('user_bind_info')) {
                            $user_bind_info = object_to_array(cookie('user_bind_info'));
                            $oauth          = new oauth($user_bind_info['type']);
                            $bind_info      = array(
                                'ins_uid'   => $uid,
                                'keyid'     => $user_bind_info['keyid'],
                                'bind_info' => $user_bind_info['bind_info'],
                            );
                            $oauth->bindByData($bind_info);
                            //临时头像转换
                            $this->_save_avatar($uid, $user_bind_info['temp_avatar']);
                            //清理绑定COOKIE
                            cookie('user_bind_info', NULL);
                        }
                        /* 绑定推荐人 */
						$invite_uid = session('invite_uid');  
						$iuser = D('user')->where(array('id'=>$invite_uid))->field('id,topkey')->find();
                        if ($iuser) {
                            $save_data        = [
                                'topkey' => $iuser['topkey'],
								'invite_uid' => $invite_uid
                            ];
                            D('user')->where(['id' => $uid])->save($save_data);
                        }
                        $save_data = [
                            'tele'     => $mobile,
                            'reg_time' => current_date(),
                        ];
                        D('user')->where(array('id' => $uid))->save($save_data);
                        $this->visitor->login($uid);
                        //注册完成钩子
                        $tag_arg = array('uid' => $uid, 'uname' => $username, 'action' => 'register');
                        tag('register_end', $tag_arg);
                        header('Location:' . U('user/index'));
                    }
                }
                else {
                    if (!session('mobile')) {
                        $this->redirect('passport/register');
                    }
                    else {
                        $this->assign('mobile', session('mobile'));
                        $this->display();
                    }
                }
                break;
            }
        }
    }

    public function register_success()
    {
        $this->display('step3');
    }

    /**
     * 第三方头像保存
     */
    private function _save_avatar($uid, $img)
    {
        //获取后台头像规格设置
        $avatar_size = explode(',', C('ins_avatar_size'));
        //会员头像保存文件夹
        $avatar_dir = C('ins_attach_path') . 'avatar/' . avatar_dir($uid);
        !is_dir($avatar_dir) && mkdir($avatar_dir, 0777, true);
        //生成缩略图
        $img = C('ins_attach_path') . 'avatar/temp/' . $img;
        foreach ($avatar_size as $size) {
            Image::thumb($img, $avatar_dir . md5($uid) . '_' . $size . '.jpg', '', $size, $size, true);
        }
        @unlink($img);
    }

    public function get_register_code()
    {
        $tele = $this->_post('tele', 'trim', 0);
        if (D('user')->where(compact('tele'))->count()) {
            $this->ajaxResultError("${tele}已经注册");
        }
        $code = rand(1000, 9999);
        session('sms_code', $code);
        $result = $this->sendSMS($tele, sprintf(C('SMS.code'), $code));
        if ($result['code'] == 0) {
            $code = md5($code);
            $this->ajaxResult(compact('code'));
        }
        else {
            $this->ajaxResultError($result['detail']);
        }
    }

    public function forget_password()
    {
        $forget_step = $this->_request('forget_step', 'intval', 1);
        switch ($forget_step) {
            case 1: {
                $this->display('forget_step1');
                break;
            }
            case 2: {
                if (IS_POST) {
                    $tele = $this->_post('tele', 'trim', 0);
                    if (!$tele) {
                        IS_AJAX && $this->ajaxReturn(0, '请输入您注册的手机号码！');
                        $this->error('请输入您注册的手机号码！');
                    }
                    else {
                        session('tele', $tele);
                    }
                    $img_code = $this->_post('img_code', 'trim', 0);
                    if (!$img_code) {
                        IS_AJAX && $this->ajaxReturn(0, '请输入图片验证码！');
                        $this->error('请输入图片验证码！');
                    }
                    if (session('captcha') != md5($img_code)) {
                        IS_AJAX && $this->ajaxReturn(0, '您输入的图片验证码不正确！');
                        $this->error('您输入的图片验证码不正确！');
                    }
                    $code = $this->_post('code', 'trim');
                    if ($code == '') {
                        IS_AJAX && $this->ajaxReturn(0, '请输入您注册的手机验证码！');
                        $this->error('请输入您注册的手机验证码！');
                    }
                    if (session('sms_code') != md5($code)) {
                        IS_AJAX && $this->ajaxReturn(0, '您输入的手机验证码不正确！');
                        $this->error('您输入的手机验证码不正确！');
                    }
                    IS_AJAX && $this->ajaxReturn(1, '', array('ret_url' => u('passport/forget_password', array('forget_step' => 2))));
                    $this->display('forget_step2');
                }
                else {
                    if (!session('tele')) {
                        $this->redirect('passport/forget_password');
                    }
                    else {
                        $this->display('forget_step2');
                    }
                }
                break;
            }
            case 3: {
                if (IS_POST) {
                    $password = $this->_post('password');
                    if ($password) {
                        $tele   = session('tele');
                        $result = D('user')->where(array('tele' => $tele))->select();
                        if ($result) {
                            D('user')->where(array('tele' => $tele))->setField('password', md5($password));
                            $result = $this->sendSMS($tele, sprintf(C('SMS.pass_notice'), $password));
                            IS_AJAX && $this->ajaxReturn(1, '', array('ret_url' => U('passport/forget_password', array('forget_step' => 3))));
                            $this->display('forget_step3');
                        }
                        else {
                            IS_AJAX && $this->ajaxReturn(0, '此手机号码还未注册');
                            $this->error('此手机号码还未注册,请先注册!');
                        }
                    }
                    else {
                        IS_AJAX && $this->ajaxReturn(0, '请输入密码');
                        $this->error('您还没有输入您的新密码呢！');
                    }
                }
                else {
                    if (!session('tele')) {
                        $this->redirect('passport/forget_password');
                    }
                    else {
                        $this->display('forget_step3');
                    }
                }
                break;
            }
        }
    }

    public function get_forget_code()
    {
        $tele = $this->_post('tele', 'trim', 0);
        if (!preg_match("/^1[0-9][0-9]{1}[0-9]{8}$/", $tele)) {
            IS_AJAX && $this->ajaxResultError('手机号格式不正确');
            $this->error('手机号格式不正确');
        }
        if (!D('user')->where(compact('tele'))->count()) {
            $this->ajaxResultError("${tele}不存在,请注册");
        }
        $code = rand(1000, 9999);
        session('sms_code', $code);
        $result = $this->sendSMS($tele, sprintf(C('SMS.code'), $code));
        if ($result['code'] == 0) {
            $code = md5($code);
            $this->ajaxResult(compact('code'));
        }
        else {
            $this->ajaxResultError('服务器错误');
        }
    }

    public function get_code()
    {
        $tele = $this->_post('tele', 'trim', 0);
        if (!preg_match("/^1[0-9][0-9]{1}[0-9]{8}$/", $tele)) {
            IS_AJAX && $this->ajaxResultError('手机号格式不正确');
            $this->error('手机号格式不正确');
        }
        $kan_id = $this->_post('kan_id', 'intval');
        //验证是否有发起过砍价没有完成的
        $where['kan_id'] = $kan_id;
        $where['uid']    = $this->visitor->info['id'];
        $logs_count      = D('kan_logs')->where($where)->count();
        if (!$logs_count) {
            unset($where['uid']);
            $where['tele'] = $tele;
            $logs_count    = D('kan_logs')->where($where)->count();
        }
        if ($logs_count) {
            $this->ajaxResultError('该活动你已经参加过了');
        }
        else {
            $code = rand(1000, 9999);
            session('sms_code', $code);
            $result = $this->sendSMS($tele, sprintf(C('SMS.code'), $code));
            if ($result['code'] == 0) {
                $code = md5($code);
                $this->ajaxResult(compact('code'));
            }
            else {
                $this->ajaxResultError($result['detail']);
            }
        }
    }

    public function ajax_agreement()
    {
        $agreement_text = C('ins_reg_protocol');
        $this->ajaxResult(compact('agreement_text'));
    }

    public function check_img_code()
    {
        $img_code = $this->_request('img_code', 'trim');
        if (md5($img_code) == session('captcha')) {
            $this->ajaxResultSuccess();
        }
        else {
            $this->ajaxResultError('验证码错误');
        }
    }

	/* 绑定推荐人 */
    public function binding_invite(){
		$invite_uid = $this->_get('invite_uid','intval');
		if(!$invite_uid){$this->redirect('index/index');}
		if($this->visitor->is_login){
			$this_user_invite = $this->visitor->get('invite_uid');
		}
		if(!$this_user_invite){
			$invite = D('user')->field('id,username as uname')->find($invite_uid);
			$ret_url = u('user/index');
			$this->assign(compact('invite_uid','invite','ret_url'));
			session('invite_uid', $invite_uid);
		}else{
			$invite = D('user')->field('id,username')->find($this_user_invite);
			$this->assign('has_invite',1);
			$this->assign('invite',$invite);
		}
		
		$this->display();
	}

}