<?php
class item_favsAction extends backendAction{
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('item_favs');
	}
	
	public function _af_index($list){
		$item_mod = D('item');
		$user_mod = D('user');
		foreach($list as $key=>$val){
			$list[$key]['item_title'] = $item_mod->where(array('id'=>$val['item_id']))->getField('title');
			$list[$key]['uname'] = $user_mod->where(array('id'=>$val['uid']))->getField('username');
		}
		return $list;
	}

	public function _search(){
		$map = array();
		($item_name = $this->_request('item_name', 'trim')) && $map['item_id'] =  array('in',$this->_get_iids($item_name));
		($user_name = $this->_request('user_name', 'trim')) && $map['uid'] =  array('in',$this->_get_uids($user_name));

		$this->assign('search', array(
				'item_name' =>$item_name,
				'user_name' => $user_name
		));
		return $map;
	}


}

?>