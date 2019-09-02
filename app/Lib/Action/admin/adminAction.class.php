<?php
class adminAction extends backendAction
{
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('admin');
    }

    public function _before_index() {
        $big_menu = array(
            'title' => '添加管理员',
            'iframe' => U('admin/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '210'
        );
        $this->assign('big_menu', $big_menu);
        $this->list_relation = true;
    }

    public function _before_add() {
        $role_list = M('admin_role')->where('status=1')->select();
        $this->assign('role_list', $role_list);
    }

    public function _before_insert($data='') {
        if( ($data['password']=='')||(trim($data['password']=='')) ){
            unset($data['password']);
        }else{
            $data['password'] = md5($data['password']);
        }
        $adm_sess = session('admin');
        $data['add_time'] = $data['update_time'] = date('Y-m-d H:i:s');
        $data['add_uid'] = $data['update_uid'] = $adm_sess['id'];
        $data['add_uname'] = $data['update_uname'] = $adm_sess['username'];

        return $data;
    }

    public function _before_edit() {
        $this->_before_add();
    }

    public function _before_update($data=''){
        if( ($data['password']=='')||(trim($data['password']=='')) ){
            unset($data['password']);
        }else{
            $data['password'] = md5($data['password']);
        }
        $adm_sess = session('admin');
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['update_uid'] = $adm_sess['id'];
        $data['update_uname'] = $adm_sess['username'];
        return $data;
    }

    public function ajax_check_name() {
        $name = $this->_get('J_username', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name, $id)) {
            echo 0;
        } else {
            echo 1;
        }
    }

	public function password() {
        if(IS_POST){
			$admin_password = $this->_mod->where(array('id'=>$_SESSION['admin']['id']))->getField('password');
			$old_password = $this->_post('old_password', 'trim');
			$password = $this->_post('password', 'trim');
			$rpassword = $this->_post('rpassword', 'trim');
			if(md5($old_password) != $admin_password){
				$this->error('旧密码不正确');
			}
			if($password != $rpassword){
				$this->error('两次密码输入不一致');
			}
			$data['password'] = md5($password);
			$this->_mod->where(array('id'=>$_SESSION['admin']['id']))->save($data);
			session('admin', null);
			$this->success('修改密码成功', U('index/login'));
		}else{
			$this->display();
		}
    }

    public function verify(){
        $pk = $this->_mod->getPk();
        if (IS_POST) {
            $id = $this->_request($pk, 'trim');
            $status = $this->_request('status', 'intval');
            if($id != ''){
                $data['status'] = $status;
                $this->_mod->where(array('id'=>array('in',$id)))->save($data);
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'verify');
                $this->success(L('operation_success'));
            }else{
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'));
            }
        } else {
            $id = $this->_request($pk, 'trim');
            $this->assign('id', $id);
            $this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
        }
    }
}