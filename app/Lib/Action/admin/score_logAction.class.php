<?php
class score_logAction extends backendAction
{

	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('score_log');
		$this->list_relation = true;
	}
    
	public function _search(){
	    $map = array();
	    ($time_start = $this->_request('time_start', 'trim')) && $map['add_time'][] = array('egt', $time_start);
	    ($time_end = $this->_request('time_end', 'trim')) && $map['add_time'][] = array('elt', $time_end);

        ($action = $this->_request('action', 'trim')) && $map['action'] = array('like', '%'.$action.'%');
	    ($keyword = $this->_request('keyword', 'trim')) && $map['uid'] = array('in', $this->getUids($keyword));
	    ($admin_uname = $this->_request('admin_uname', 'trim')) && $map['admin_uname'] = array('like', '%'.$admin_uname.'%');
	    ($uid = $this->_request('uid', 'intval')) && $map['uid'] = $uid;

	    $this->assign('search', array(
	    		'time_start' 	=> $time_start,
	    		'time_end' 		=> $time_end,
	            'action'		=>$action,
	    		'keyword' 		=> $keyword,
	    		'uid' 			=> $uid,
	    		'admin_uname'	=> $admin_uname,
	    ));
	    return $map;
	}
	
	public function _before_index(){
	    $big_menu = array(
	    		'title' => '增减积分',
	    		'iframe' => U('score_log/add'),
	    		'id' => 'add',
	    		'width' => '500',
	    		'height' => '240',
	    );
	    $this->assign('big_menu', $big_menu);
	}

	public function _search_list(){
	    $map = array();
	    ($keyword = $this->_request('keyword', 'trim')) && $map['id'] = array('in', $this->getUids($keyword));
	    ($uid = $this->_request('uid', 'intval')) && $map['id'] = $uid;

	    $this->assign('search', array(
	    		'keyword' => $keyword,
	    		'uid' => $uid,
	    ));
	    return $map;
	}

	public function lists(){
		$map = $this->_search_list();
		$count = D('user')->where($map)->count();
        $pager = new Page($count, 20);
		$user_list = D('user')->field('id,username,score')->where($map)->limit($pager->firstRow . ',' . $pager->listRows)->select();
		foreach($user_list as $key=>$val){
			$user_list[$key]['total_score'] = D('score_log')->where(array('uid'=>$val['id'],'score'=>array('gt', 0)))->sum('score');
		}
		$page = $pager->show();
		$this->assign(compact('user_list', 'page'));
		$this->display();
	}
	
    public function _before_add(){
        $user_list = D('user')->select();
        $this->assign('user_list',$user_list);
    }
    
    public function _before_insert($data) {
         $id = $this->_post('uid','trim');
         $userInfo = D('user')->where(array('id' => $id))->find();
         $data['uname'] = $userInfo['username'];
         if($_POST['is_choose']==1)
         {
         	$data['score']=$this->_post('score', 'trim');
         	$data['coin']=$this->_post('coin', 'trim');
         }else{
             $data['score']="-".$this->_post('score', 'trim');
             $data['coin']="-".$this->_post('coin', 'trim');
         }
         $adm_sess = session('admin');
         $data['admin_uname'] = $adm_sess['username'];
         return $data;
    }

    public function _after_insert($id){
    	$uid = $this->_mod->where(array('id'=>$id))->getField('uid');
    	$sum_score = $this->_mod->where(array('uid'=>$uid))->sum('score');
    	D('user')->where(array('id'=>$uid))->setField('score', $sum_score);
    }

     public function getUids($uname) {
		$ids = array();
		$ulist = D('user')->field('id')->where(array('username'=>array('like',$uname)))->select();
		foreach($ulist as $key=>$val){
			$ids[] = $val['id'];
		}
		return empty($ids) ? '-1' : implode(',',$ids);
	 }

     
}
?>