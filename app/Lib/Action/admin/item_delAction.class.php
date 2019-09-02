<?php
class item_delAction extends backendAction{
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('item');
		$this->_cate_mod = D('item_cate');
		$this->_tags_mod = D('item_tags');
	}
	
	
	protected function _search(){
		$map = array();
		($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
		($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
		($title = $this->_request('title', 'trim')) && $map['title'] =  array('like', '%'.$title.'%');
		($brand_id = $this->_request('brand_id', 'intval')) && $map['brand_id'] =  $brand_id;
		($id = $this->_request('id', 'trim')) && $map['id'] =  $id;
		
		($sfavs = $this->_request('sfavs', 'trim')) && $map['favs'][] = array('egt', $sfavs);
		($efavs = $this->_request('efavs', 'trim')) && $map['favs'][] = array('elt', $efavs);
		($shits = $this->_request('shits', 'trim')) && $map['hits'][] = array('egt', $shits);
		($ehits = $this->_request('ehits', 'trim')) && $map['hits'][] = array('elt', $ehits);
		($scomments = $this->_request('scomments', 'trim')) && $map['comments'][] = array('egt', $scomments);
		($ecomments = $this->_request('ecomments', 'trim')) && $map['comments'][] = array('elt', $ecomments);

		$cate_id = $this->_request('cate_id', 'intval');
		if ($cate_id) {
			$id_arr = $this->_cate_mod->get_child_ids($cate_id, true);
			$map['cate_id'] = array('IN', $id_arr);
			$spid = $this->_cate_mod->where(array('id'=>$cate_id))->getField('spid');
			if( $spid==0 ){
				$spid = $cate_id;
			}else{
				$spid .= $cate_id;
			}
		}
		if( $_GET['status']==null ){
			$status = -1;
		}else{
			$status = intval($_GET['status']);
		}
		$status>=0 && $map['status'] = $status;
		if( $_GET['is_check']==null ){
			$is_check = -1;
		}else{
			$is_check = intval($_GET['is_check']);
		}
		$is_check>=0 && $map['is_check'] = $is_check;
		($keyword = $this->_request('keyword', 'trim')) && $map['title'] = array('like', '%'.$keyword.'%');
		$this->assign('search', array(
				'stime' => $stime,
				'etime' => $etime,
				'shits' => $shits,
				'ehits' => $ehits,
				'scomments' => $scomments,
				'ecomments' => $ecomments,
				'sfavs' => $sfavs,
				'efavs' => $efavs,
				'id'	=> $id,
				'cate_id'=> $cate_id,
				'selected_ids'	=> $spid,
				'status' =>$status,
				'is_check' =>$is_check,
		        'title'=>$title,
				'keyword' => $keyword,
				'brand_id'=> $brand_id,
		));
		$map['is_del'] = 1;
		//print_r($map);
		return $map;
	}
	
	public function index()
    {
        $map = $this->_search();
        $db_prefix = C(DB_PREFIX);
        $search = $this->_request('search', 'trim');
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
        $select = $this->_mod
                       ->where($map)
                       ->order($sort . ' ' . $order);

        $select->relation(true);
        $array = $select->select();
        $count = count($array);
        $pager = new Page($count, $pagesize);
		
		$select = $this->_mod
                   ->where($map)
                   ->order($sort . ' ' . $order);

		$select->relation(true);
        $select->limit($pager->firstRow . ',' . $pager->listRows);
        $page = $pager->show();
        $this->assign("page", $page);
        $list = $select->select();

		$p = $this->_request('p', 'intval', 1);
		$this->assign('p', $p);
		
        $this->assign('list', $list);
        $this->assign('list_table', true);
		$this->display();
    }
	
	public function _before_index() {
    	$cate_list = D('item_cate')->where(array('status'=>1))->select();
    	foreach($cate_list as $key=>$val){
    		$clist[$val['id']] = $val['name'];
    	}
    	$this->assign('cate_list', $clist);

    	$brand_list = D('item_brand')->where(array('status'=>1))->order('title asc')->select();
    	$this->assign('brand_list', $brand_list);
	}

	/**
     * 删除
     */
    public function delete()
    {
        $mod = D('item');
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
			$ids_arr = explode(',',$ids);
			foreach($ids_arr as $key=>$val){
				$img_list = D('item_img')->where(array('item_id'=>$val))->select();
				foreach($img_list as $img){
					!empty($img['img']) && @unlink(C('ins_attach_path') . 'assets/' . $img['img']);
				}
			}
			$this->_delete_attach($ids);
            if (false !== $mod->delete($ids)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'),U($this->_name.'/index'));
            }
        } else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
            $this->error(L('illegal_parameters'),U($this->_name.'/index'));
        }
    }

    public function back(){
		$mod = D('item');
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if($ids != ''){
			$data['is_del'] = 0;
			$mod->where(array('id'=>array('in',$ids)))->save($data);
			if (false !== $mod->where(array('id'=>array('in',$ids)))->save($data)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'),U($this->_name.'/index'));
            }
		}else{
			IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
			$this->error(L('operation_failure'));
		}
	}
}

?>