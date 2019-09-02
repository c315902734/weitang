<?php
class topicAction extends backendAction{
    public function _initialize()
    {
    	parent::_initialize();
    	$this->_mod = D('topic');
    }
    
    protected function _search(){
    	$map = array();
    	$cate_id = $this->_request('cate_id', 'intval');
        if ($cate_id) {
            $id_arr = D('item_cate')->get_child_ids($cate_id, true);
            $map['cate_id'] = array('IN', $id_arr);
            $spid = D('topic_cate')->where(array('id'=>$cate_id))->getField('spid');
            if( $spid==0 ){
                $spid = $cate_id;
            }else{
                $spid .= $cate_id;
            }
        }

    	($keyword = $this->_request('keyword', 'trim')) && $map['title'] = array('like', '%'.$keyword.'%');
    	$this->assign('search', array(
    			'selected_ids' => $spid,
                'cate_id' => $cate_id,
    			'keyword' => $keyword,
    	));
        
    	return $map;
    }

    public function index(){
        $map = $this->_search();
        //排序
        $mod_pk = $this->_mod->getPk();
        if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        } else if (!empty($sort_by)) {
            $sort = $sort_by;
        } else if ($this->sort) {
            $sort = $this->sort;
        } else {
            $sort = $mod_pk;
        }
        if ($this->_request("order", 'trim')) {
            $order = $this->_request("order", 'trim');
        } else if (!empty($order_by)) {
            $order = $order_by;
        } else if ($this->order) {
            $order = $this->order;
        } else {
            $order = 'DESC';
        }
        $pagesize = 20;
        //如果需要分页
        $select = $this->_mod->where($map)->group('id')->order($sort . ' ' . $order);
        $this->list_relation && $select->relation(true);
        if ($pagesize) {
            $array = $select->select();
            $count = count($array);
            $pager = new Page($count, $pagesize);
            
            
            $select = $this->_mod->where($map)->group('id')->order($sort . ' ' . $order);
            $this->list_relation && $select->relation(true);
            $select->limit($pager->firstRow . ',' . $pager->listRows);
            $page = $pager->show();
            $this->assign("page", $page);
        }
        $list = $select->select();
        foreach($list as $k=>$v){
            $spid = D('topic_cate')->where(array('id'=>$v['cate_id']))->getField('spid');
            $ex = explode('|', $spid);
            $count = count($ex)-1;
            for($i=0;$i<$count;$i++){
                $list[$k]['class_name'] .= D('topic_cate')->where(array('id'=>$ex[$i]))->getField('name').'=>';
            }
            $list[$k]['class_name'] .= D('topic_cate')->where(array('id'=>$v['cate_id']))->getField('name');
        }
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }
    
    public function add(){
        if(IS_POST){
            $data = $this->_mod->create();
            $data['add_time'] = date('Y-m-d H:i:s', time());
            $data['admin_id'] = $_SESSION['admin']['id'];
            D('topic')->add($data);
            $this->success('添加成功');
        } else {
            $this->display();
        }
    }

    public function edit(){
        if(IS_POST){
            $data = $this->_mod->create();
            D('topic')->save($data);
            $this->success('修改成功');
        } else {
            $id = $this->_get('id', 'intval');
            $cate_id = $this->_mod->where(array('id' => $id))->getField('cate_id');
            $spid = D('topic_cate')->where(array('id'=>$cate_id))->getField('spid');
            if($spid){
                $spid = $spid.$cate_id;
            } else {
                $spid = $cate_id;
            }
            
            $this->assign('selected_ids', $spid);

            $info = $this->_mod->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $this->display();
        }
    }
}