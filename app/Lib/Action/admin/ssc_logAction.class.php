<?php
class ssc_logAction extends backendAction
{
	protected $pk = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('ssc_log');
		$this->list_relation = true;
		$guess = array('0'=>'偶数','1'=>'奇数');
		$this->assign('guess',$guess);
    }

    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($keyword,'username'));
        ($tele = $this->_request('tele', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($tele,'tele'));
        ($orderid = $this->_request('orderid', 'trim')) && $map['order_id'] = array('IN',$this->_get_oids($orderid,'orderid'));
       
        $this->assign('search', array(
			'keyword' => $keyword,
            'tele' => $tele,
            'orderid' => $orderid,
        ));
        return $map;
    }

	public function _get_uids($keyword,$field){
		$where[$field] = array('like', '%'.$keyword.'%');
		$uidarr = D('user')->where($where)->field('id')->select();
		$_idarr = array();
		foreach($uidarr as $v){
			$_idarr[] = $v['id'];
		}
		return implode(',',$_idarr);
	}

	public function _get_oids($keyword,$field){
		$where[$field] = array('like', '%'.$keyword.'%');
		$oidarr = D('order')->where($where)->field('id')->select();
		$_idarr = array();
		foreach($oidarr as $v){
			$_idarr[] = $v['id'];
		}
		return implode(',',$_idarr);
	}


}