<?php
class typeAction extends backendAction{
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('type');
	}
	public function _before_index(){
		$big_menu = array(
            'title' => '添加类别',
            'iframe' => U('type/add'),
            'id' => 'add',
            'width' => '400',
            'height' => '50',
        );
        $this->assign('big_menu', $big_menu);
	}
	
	protected function _search(){
		$map = array();
		($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
		$this->assign('search', array(
			'keyword' => $keyword,
		));
		return $map;
	}
}