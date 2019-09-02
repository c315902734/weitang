<?php
class cateAction extends backendAction
{
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('cate');
	}
    
	protected function _search() {
		$map = array();
		if( $title = $this->_request('title', 'trim') ){
			$map['_string'] = "title like '%".$title."%'";
		}
		$this->assign('search', array(
				'title' => $title,
		));
		return $map;
	}
    //
	public function _before_insert($data){
        //
		$adm_sess = session('admin');
		$data['uid'] = $adm_sess['id'];
		$data['uname'] = $adm_sess['username'];
		return $data;
	}

	public function _after_insert($id){
		$status = D('cate')->where(compact('id'))->getField('status');
		if($status){
			$adm_sess = session('admin');
			//修改审核人员
			$data['check_uid'] = $adm_sess['id'];
			$data['check_uname'] = $adm_sess['username'];
			$data['check_time'] = date('Y-m-d H:i:s');
			D('cate')->where(compact('id'))->save($data);
		}
	}

	public function _after_update($id){
		$this->_after_insert($id);
	}
}

?>