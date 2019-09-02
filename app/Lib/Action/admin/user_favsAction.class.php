<?php
class user_favsAction extends backendAction
{
	protected $pk = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('user_favs');
        $this->pk = '*';

    }

    protected function _search() {
        $map = array();
		$uname = $this->_request('uname', 'trim');
		if($uname){
			$map['uid'] = array('in',$this->_get_uids($uname),'OR');
		}
		($uid = $this->_request('uid', 'trim')) && $map['uid'] = array('eq', $uid);
        $this->assign('search', array(
            'uname' => $uname,
            'uid'   => $uid,
        ));
        return $map;
    }

    public function _af_index($list) {
		foreach($list as $key=>$val){
            $list[$key]['username'] = D('user')->where(array('id'=>$val['uid']))->getField('username');
            $list[$key]['item_title'] = D('item')->where(array('id'=>$val['item_id']))->getField('title');
        }
        return $list;
    }

    public function _get_uids($uname){
        $where['username'] = array('like', '%'.$uname.'%');
        $uidarr = D('user')->where($where)->field('id')->select();
        $_idarr = array();
        foreach($uidarr as $v){
            $_idarr[] = $v['id'];
        }
        return implode(',',$_idarr);
    }
}