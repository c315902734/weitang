<?php
class coin_applyAction extends backendAction {
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('coin_apply');
    }

    public function _search(){
        $map = array();
        $uid = $this->_request('uid', 'intval', 0);
        if($uid > 0){
            $map['uid'] = $uid;
        }
        $status = $this->_request('status', 'intval', '-1');
        if($status >= 0){
            $map['status'] = $status;
        }
        $this->assign('search', array(
            'status'       => $status,
            'uid'          => $uid,
        ));
        return $map;
    }

    public function _before_index() {
    }

    public function _before_edit()
    {
        $id = $this->_get('id', 'intval');
        
    }

    public function _after_update($id){
    }
}