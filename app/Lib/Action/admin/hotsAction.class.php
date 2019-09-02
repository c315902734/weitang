<?php
class hotsAction extends backendAction{
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('hots');
		$this->_cate_mod = D('item_cate');
	}
	
	
	protected function _search(){
		$map = array();
		($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
		($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
		($last_stime = $this->_request('last_stime', 'trim')) && $map['last_time'][] = array('egt', $last_stime);
		($last_etime = $this->_request('last_etime', 'trim')) && $map['last_time'][] = array('elt', $last_etime);
		($shop_id = $this->_request('shop_id', 'trim')) && $map['shop_id'] = array('eq', $shop_id);
		if( $_GET['status']==null ){
			$status = -1;
		}else{
			$status = intval($_GET['status']);
		}
		$status>=0 && $map['status'] = array('eq',$status);
		($uname = $this->_request('uname', 'trim')) && $map['uname'] = array('like', '%'.$uname.'%');
		($keyword = $this->_request('keyword', 'trim')) && $map['title'] = array('like', '%'.$keyword.'%');
		$this->assign('search', array(
				'stime' => $stime,
				'etime' => $etime,
				'last_stime' => $last_stime,
				'last_etime' => $last_etime
		));
	
		return $map;
	}
	
	public function _before_index() {
		$big_menu = array(
            'title' => '添加热搜索词',
            'iframe' => U('hots/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '220',
        );
        $this->assign('big_menu', $big_menu);
	}

	public function _before_edit(){
	}
	
}

?>