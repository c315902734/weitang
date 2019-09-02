<?php
class user_signAction extends backendAction
{

	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('user_sign');
	}
    
	protected function _search() {
		$map = array();
		if( $uname = $this->_request('uname', 'trim') ){
			$map['_string'] = "uname like '%".$uname."%'";
		}
		$this->assign('search', array(
				'uname' => $uname,
		));
		return $map;
	}	
	public function _before_insert($data){
		$date = $this->_request('date', 'trim');
		if($date){
			$ex = explode('-', $date);
			$data['year'] = $ex[0];
			$data['month'] = $ex[1];
			$data['day'] = $ex[2];
		}
		if($data['uid']){
			$data['uname'] = D('user')->where(array('id'=>$data['uid']))->getField('username');
		}
		return $data;
	}

	public function _before_update($data){
		$this->_before_insert($data);
	}
}

?>