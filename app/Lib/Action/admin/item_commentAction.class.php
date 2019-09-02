<?php
class item_commentAction extends backendAction{
    public function _initialize()
    {
    	parent::_initialize();
    	$this->_mod = D('item_comment');
    	$this->_cate_mod = D('item_cate');
    }
    
    protected function _search(){
    	$map = array();
    	($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
    	($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
    	($item_id = $this->_request('item_id', 'trim')) && $map['item_id'][] = array('eq', $item_id);
        
    	if( $_GET['reply_status']==null ){
    		$reply_status = -1;
    	}else{
    		$reply_status = intval($_GET['reply_status']);
    	}
    	$reply_status>=0 && $map['reply_status'] = array('eq',$reply_status);
    	($uname = $this->_request('uname', 'trim')) && $map['uname'] = array('like', '%'.$uname.'%');
    	($reply_info = $this->_request('reply_info', 'trim')) && $map['reply_info'] = array('like', '%'.$reply_info.'%');
    	($keyword = $this->_request('keyword', 'trim')) && $map['info'] = array('like', '%'.$keyword.'%');
    	$this->assign('search', array(
    			'stime' => $stime,
    			'etime' => $etime,
    			'reply_status' =>$reply_status,
    			'reply_info' => $reply_info,
    			'uname'=>$uname,
    			'keyword' => $keyword,
    	));
    
    	return $map;
    }
    
    public function _before_index() {
    	$big_menu = array(
    			'title' => L('添加评论'),
    			'iframe' => U('item_comment/add'),
    			'id' => 'add',
    			'width' => '500',
    			'height' => '220'
    	);
    	$this->assign('big_menu', $big_menu);
		$item_list = D('item')->select();
		foreach($item_list as $v){
			$item_list[$v['id']] = $v;
		}
		$this->assign('item_list',$item_list);
    }

    public function _af_index($list){
    	$item_mod = D('item');
		$user_mod = D('user');
		foreach($list as $key=>$val){
			$list[$key]['item_title'] = $item_mod->where(array('id'=>$val['item_id']))->getField('title');
			if(!$list[$key]['uname']){
				$list[$key]['uname'] = $user_mod->where(array('id'=>$val['uid']))->getField('username');
			}
		}
		return $list;
    }
	/**
     * 入库数据整理
     */
    protected function _before_insert($data = '') {
		$order_uid = $this->_request('order_uid', 'intval');
		if($order_uid){
			$user = D('user')->where('id='.intval($order_uid))->field('id,username')->find();
			$data['uid'] = intval($user['id']);
			$data['uname'] = strval($user['username']);
		}
		$reply_uid = $this->_request('reply_uid', 'intval');
		if($reply_uid){
			$reply_user = D('user')->where('id='.intval($reply_uid))->field('id,username')->find();
			$data['reply_id'] = intval($reply_user['id']);
			$data['reply_name'] = strval($reply_user['username']);
		}
		$auto_user = $this->_request('auto_user', 'intval');
		if($auto_user){
			$auto_user_info = D('auto_user')->where('id='.intval($auto_user))->field('id,name')->find();

		}

    	return $data;
    }

	/**
     * 修改提交数据
     */
    protected function _before_update($data = '') {       
		$order_uid = $this->_request('order_uid', 'intval');
		if($order_uid){
			$user = D('user')->where('id='.intval($order_uid))->field('id,username')->find();
			$data['uid'] = intval($user['id']);
			$data['uname'] = strval($user['username']);
		}
		$reply_uid = $this->_request('reply_uid', 'intval');
		if($reply_uid){
			$reply_user = D('user')->where('id='.intval($reply_uid))->field('id,username')->find();
			$data['reply_id'] = intval($reply_user['id']);
			$data['reply_name'] = strval($reply_user['username']);
		}
		$auto_user = $this->_request('auto_user', 'intval');
		if($auto_user){
			$auto_user_info = D('auto_user')->where('id='.intval($auto_user))->field('id,name')->find();

		}
    	return $data;
    }


	public function _before_edit(){
		$id = $this->_get('id','intval');
		$item_info = D('item_comment')->where(array('id'=>$id))->find();
	    $item_name = D('item')->where(array('id'=>$item_info['item_id']))->getField('title');
	    $this->assign('item_name',$item_name);
	}

	public function _before_add(){

	}

	public function search_auto_user(){
		$auto_username = $this->_request('auto_username', 'trim');
		$user_list = D('auto_user')->where('name like "%'.$auto_username.'%"')->field('id,name')->select();
		$str = '';
		if($user_list){
			foreach($user_list as $k=>$v){
				$str .= '<option value="'.$v['id'].'">'.$v['name'].'</option>';
			}
		}
		echo $str;
	}


    public function reply(){
        $mod = D('item_comment');
        $pk = $mod->getPk();
        if (IS_POST) {
            if (false === $data = $mod->create()) {
                IS_AJAX && $this->ajaxReturn(0, $mod->getError());
                $this->error($mod->getError());
            }
            if (method_exists($this, '_before_update')) {
                $data = $this->_before_update($data);
            }
            if (false !== $mod->save($data)) {
                if (method_exists($this, '_after_update')) {
                    $id = $data['id'];
                    $this->_after_update($id);
                }
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'edit');
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'));
            }
        } else {
            $id = $this->_get($pk, 'intval');
            $info = $mod->find($id);
            $this->assign('info', $info);
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
?>