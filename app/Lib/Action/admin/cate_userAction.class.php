<?php
class cate_userAction extends backendAction
{

	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('cate_user');
	}

	public function _before_index(){
		$cate_list = D('cate')->where(['status'=>1])->order('id desc')->field('id,title')->select();
		$this->assign('cate_list', $cate_list);
	}
    
	public function _af_index($list){
		$type = array('未知','微信','APP','网页');
		$cate_mod = D('cate');
		foreach($list as $key=>$val){
			$list[$key]['type_name'] = $type[$val['type']];
			$list[$key]['cate_title'] = $cate_mod->where(array('id'=>$val['cate_id']))->getField('title');
		}
		return $list;
	}

	protected function _search() {
		$map = array();
		if( $real_name = $this->_request('real_name', 'trim') ){
			$logs_list = D('cate_user')->where(array('name'=>array('like', '%'.$real_name.'%')))->field('id')->select();
			if($logs_list){
				foreach($logs_list as $val){
					$logs_ids[] = $val['id'];
				}
			} else {
				$logs_ids = 0;
			}
			$map['logs_id'] = array('in', $logs_ids);
		}
		($logs_id = $this->_request('logs_id', 'intval')) && $map['logs_id'] = $logs_id;
		($cate_id = $this->_request('cate_id', 'intval')) && $map['cate_id'] = $cate_id;
		$this->assign('search', array(
				'real_name' => $real_name,
				'cate_id'	=> $cate_id,
		));
		
		return $map;
	}
    public function ajax_get_num()
    {
        echo 444;
        $tempNum =$this->_post('tempNum', 'intval');
        $this->ajaxReturn(0,'您已经参加过该店活动了22！');
        //echo 99;
        // 还要获取该用户的openid信息，
        $num =D('cate_user')->getField('num');
        $arr =array();
        // protected function ajaxReturn($status = 1, $msg = '', $data = '', $dialog = '')
        /*if(in_array($tempNum,$arr))
        {
            $this->ajaxReturn(0,'您已经参加过该店活动了！');
        }*/
        /*elseif()
        {

        }*/

    }
}

?>