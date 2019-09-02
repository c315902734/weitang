<?php
class topic_itemAction extends backendAction{
    public function _initialize()
    {
    	parent::_initialize();
    	$this->_mod = D('topic_item');
    }
    
    protected function _search(){
    	$map = array();
    	$topic_id = $this->_request('topic_id', 'intval');
        if ($topic_id) {
            $map['topic_id'] = $topic_id;
        }

    	$this->assign('search', array(
                'topic_id' => $topic_id,
    	));

        $topic_list = D('topic')->select();
        $this->assign('topic_list', $topic_list);
        
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
            $list[$k]['item_name'] = D('item')->where(array('id'=>$v['item_id']))->getField('title');
            $list[$k]['topic_name'] = D('topic')->where(array('id'=>$v['topic_id']))->getField('title');
            $list[$k]['item_img'] = D('item')->where(array('id'=>$v['item_id']))->getField('img');
            $list[$k]['topic_img'] = D('topic')->where(array('id'=>$v['topic_id']))->getField('img');
        }
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }
    
    public function add(){
        if(IS_POST){
            $data = $this->_mod->create();
            $data['add_time'] = date('Y-m-d H:i:s', time());
            D('topic_item')->add($data);
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
?>