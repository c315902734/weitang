<?php
class admin_logAction extends backendAction
{
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('admin_log');
    }

    public function index() {
		$pagesize = 20;
		//排序
		$model = D('admin_log');
        $mod_pk = 'id';
		$map = $this->_search();
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

        //如果需要分页
        if ($pagesize) {
            $count = $model->where($map)->count($mod_pk);
            $pager = new Page($count, $pagesize);
        }
        $select = $model->field($field_list)->where($map)->order($sort . ' ' . $order);
        $this->list_relation && $select->relation(true);
        if ($pagesize) {
            $select->limit($pager->firstRow . ',' . $pager->listRows);
            $page = $pager->show();
            $this->assign("page", $page);
        }
        $list = $select->select();
		foreach($list as $k=>$v){
			$action = explode(',',$v['actions']);
			foreach($action as $av){
				if(trim($av) != ''){
					$_temp = explode(':',$av);
					$list[$k][$_temp[0]] = $_temp[1];
				}
			}
		}
        $this->assign('list', $list);
        $this->assign('list_table', true);
		$this->display();
	}
	protected function _search(){
    	$map = array();
        ($stime = $this->_request('stime', 'trim')) && $map['admin_time'][] = array('egt', $stime);
        ($etime = $this->_request('etime', 'trim')) && $map['admin_time'][] = array('elt', $etime);
    	($admin_name = $this->_request('admin_name', 'trim')) && $map['admin_name'] = array('like', '%'.$admin_name.'%');
    	$this->assign('search', array(
    		'admin_name' => $admin_name,
            'stime'      => $stime,
            'etime'      => $etime,
    	));
    	return $map;
    }
}