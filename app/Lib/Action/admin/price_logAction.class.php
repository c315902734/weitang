<?php
class price_logAction extends backendAction
{
	protected $pk = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('price_log');
		$this->list_relation = true;
    }

    protected function _search() {
        $map = array();
        ($uid = $this->_request('uid', 'trim')) && $map['uid'] = $uid;
        ($keyword = $this->_request('keyword', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($keyword,'username'));
        ($tele = $this->_request('tele', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($tele,'tele'));
        ($realname = $this->_request('realname', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($realname,'realname'));

        if ($_GET['status'] == null) {
            $status = -1;
        }
        else {
            $status = intval($_GET['status']);
        }
        $status >= 0 && $map['status'] = array('eq', $status);
		$type = intval($_GET['type']);
		$type = $type ? array('eq', $type) : array('eq', 1);
		$map['type'] = $type;

        $this->assign('search', array(
			'keyword' => $keyword,
            'tele' => $tele,
            'realname' => $realname,
            'status' => $status,
			'type' => $type[1],
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


}