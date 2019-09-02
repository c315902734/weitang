<?php
class feedbackAction extends backendAction {
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('feedback');
    }

    public function _search(){
        ($keyword = $this->_get('keyword', 'trim')) && $map['title|info'] = array('like', '%'.$keyword.'%');
        ($type = $this->_get('type', 'trim')) && $map['type'] = array('like', '%'.$type.'%');
        ($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
        ($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
        $reply_status = $this->_request('reply_status', 'intval', '-1');
        if($reply_status >= 0){
            $map['reply_status'] = $reply_status;
        }
        $this->assign('search', array(
            'keyword' => $keyword,
            'stime' => $stime,
            'etime' => $etime,
            'reply_status' => $reply_status,
            'type'  => $type,
        ));
        return $map;
    }

    public function _af_index($list) {
        $user_mod = D('user');
        foreach($list as $key=>$val){
            $list[$key]['uname'] = $user_mod->where(['id'=>$val['uid']])->getField('username');
        }
        
        return $list;
    }

    public function _before_edit(){
        $type = $this->_request('type', 'trim', 'index');
        $this->assign(compact('type'));
    }

    public function _before_update($data){
        if($data['reply_info']){
            $adm_sess = session('admin');
            $data['reply_time'] = date('Y-m-d H:i:s');
            $data['reply_status'] = 1;
            $data['reply_uid'] = $adm_sess['id'];
            $data['reply_name'] = $adm_sess['username'];
        }
        return $data;
    }

    public function query(){
        $map = $this->_search();
        $result = $this->_list($this->_mod, $map);
        $this->assign('page', $result['page']);
        $this->assign('list', $result['list']);
        $this->display();
    }
}