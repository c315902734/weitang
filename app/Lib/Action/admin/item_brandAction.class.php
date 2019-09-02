<?php
class item_brandAction extends backendAction{
    public function _initialize()
    {
    	parent::_initialize();
    	$this->_mod = D('item_brand');
        $arr = array('0-9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $this->assign('arr', $arr);
    }
    
    protected function _search(){
    	$map = array();
    	($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
    	($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
    	if( $_GET['status']==null ){
            $status = -1;
        }else{
            $status = intval($_GET['status']);
        }
        $status>=0 && $map['status'] = array('eq',$status);
        ($tele = $this->_request('tele', 'trim')) && $map['tele'] = array('like', '%'.$tele.'%');
        ($abst = $this->_request('abst', 'trim')) && $map['abst'] = array('like', '%'.$abst.'%');
        ($pinyin = $this->_request('pinyin', 'trim')) && $map['pinyin'] = $pinyin;
    	($keyword = $this->_request('keyword', 'trim')) && $map['title|address'] = array('like', '%'.$keyword.'%');
    	$this->assign('search', array(
    			'stime' => $stime,
    			'etime' => $etime,
    			'status' =>$status,
                'tele' => $tele,
                'abst' => $abst,
    			'keyword' => $keyword,
                'pinyin'  => $pinyin,
    	));
    
    	return $map;
    }
    
	/**
     * 入库数据整理
     */
    protected function _before_insert($data = '') {
		$data['add_time'] = date('Y-m-d H:i:s', time());

    	return $data;
    }

    public function _before_edit(){
        $id = $this->_get('id','intval');
    }

    public function change_mid()
    {
        if (IS_POST) {
            $ids = $this->_post('ids', 'trim');
            $mid = $this->_post('mid');
            if (D('member')->where(['id' => $mid])->count() == 0) {
                $this->ajaxResultError('mid不存在!');
            }

            $ids = explode(',', $ids);
            if ($ids) {
                $data['mid'] = $mid;
                $data['mname'] = D('member')->where(['id' => $mid])->getField('username');
                D('item_brand')->where(['id' => ['in', $ids]])->save($data);
            }
            $this->ajaxResultSuccess('操作成功', ['dialog' => ACTION_NAME]);
        }
        else {
            $ids = $this->_get('ids', 'trim');
            $this->assign('ids', $ids);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

}