<?php

class settingAction extends backendAction {

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('setting');
    }

    public function index() {
        $type = $this->_get('type', 'trim', 'index');
        $this->display($type);
    }
    
    public function user() {
        $this->display();
    }
    public function follow() {
        $this->display();
    }
    public function edit() {
        $setting = $this->_post('setting', ',');
        foreach ($setting as $key => $val) {
            $val = is_array($val) ? serialize($val) : $val;
			if($this->_mod->where(array('name' => $key))->count()){
                $this->_mod->where(array('name' => $key))->save(array('data' => $val));
            }else{
                $this->_mod->add(array('name'=>$key,'data'=>$val));
            }
        }
        if (!empty($_FILES['site_logo']['name'])) {
        	@unlink(C('ins_attach_path').'logo.png');
            $this->_upload($_FILES['site_logo'], '/', '', 'logo');
        }
		if (!empty($_FILES['weixin_logo']['name'])) {
        	@unlink(C('ins_attach_path').'weixin.png');
            $res = $this->_upload($_FILES['weixin_logo'], '/', '', 'weixin');
        }
		if (!empty($_FILES['app_logo']['name'])) {
        	@unlink(C('ins_attach_path').'app.png');
            $this->_upload($_FILES['app_logo'], '/', '', 'app');
        }
		if (!empty($_FILES['yao_logo']['name'])) {
        	@unlink(C('ins_attach_path').'yao.png');
            $this->_upload($_FILES['yao_logo'], '/', '', 'yao');
        }
		if (!empty($_FILES['upload_app_img']['name'])) {
        	@unlink(C('ins_attach_path').'upload_app_img.png');
            $this->_upload($_FILES['upload_app_img'], '/', '', 'upload_app_img');
        }
		if (!empty($_FILES['pop_app_img']['name'])) {
        	@unlink(C('ins_attach_path').'pop_app_img.png');
            $this->_upload($_FILES['pop_app_img'], '/', '', 'pop_app_img');
        }
		if (!empty($_FILES['kefu']['name'])) {
        	@unlink(C('ins_attach_path').'kefu.png');
            $this->_upload($_FILES['kefu'], '/', '', 'kefu');
        }
        //$type = $this->_post('type', 'trim', 'index');
        $this->success(L('operation_success'));
    }

    public function freight() {
		if(IS_POST){
			$setting = $this->_post('setting', ',');                
			$this->_mod->update($setting); 
			$this->success(L('operation_success'));
		}else{
			$this->display();
		}
    }

	 public function password() {
		if(IS_POST){
			$setting = $this->_post('setting', ',');                
			$this->_mod->update($setting); 
			$this->success(L('operation_success'));
		}else{
			$this->display();
		}
    }


	public function weixin() {
		if(IS_POST){
			$setting = $this->_post('setting', ',');                
			$this->_mod->update($setting); 
			$this->success(L('operation_success'));
		}else{
			$this->display();
		}
    }

    public function ajax_mail_test() {
        $email = $this->_get('email', 'trim');
        !$email && $this->ajaxReturn(0);
        //发送
        $mailer = mailer::get_instance();
        if ($mailer->send($email, L('send_test_email_subject'), L('send_test_email_body'))) {
            $this->ajaxReturn(1);
        } else {
            $this->ajaxReturn(0);
        }
    }

}