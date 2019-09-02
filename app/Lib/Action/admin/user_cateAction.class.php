<?php
class user_cateAction extends backendAction{
    public function _initialize()
    {
    	parent::_initialize();
    	$this->_mod = D('user_cate');
    }

	public function index() {
        $tree = new Tree();
        $tree->icon = array('│ ','├─ ','└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		//排序
        if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        } else if (!empty($sort_by)) {
            $sort = $sort_by;
        } else if ($this->sort) {
            $sort = $this->sort;
        } else {
            $sort = 'ordid';
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
        $result = $this->_mod->order($sort . ' ' . $order)->select();
        $array = array();
        foreach($result as $r) {
            $r['str_name'] = L($r['name']);
			$r['str_remark'] = $r['remark'];
            $r['str_add_time'] = L($r['add_time']);
            $r['str_update_time'] = L($r['update_time']);
            $r['str_status'] = $r['status'] == 1 ?'enabled' : 'disabled';
            $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('user_cate/add',array('pid'=>$r['id'])).'" data-title="'.L('add_submenu').'" data-id="add" data-width="500" data-height="200">'.L('add_submenu').'</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('user_cate/edit',array('id'=>$r['id'])).'" data-title="'.L('edit').' - '. $r['name'] .'" data-id="edit" data-width="500" data-height="200">'.L('edit').'</a> |
                                <a href="javascript:;" class="J_confirmurl" data-acttype="ajax" data-uri="'.U('user_cate/delete',array('id'=>$r['id'])).'" data-msg="'.sprintf(L('confirm_delete_one'),$r['name']).'">'.L('delete').'</a>';
            $r['parentid_node'] = ($r['pid'])? ' class="child-of-node-'.$r['pid'].'"' : '';
            $array[] = $r;
        }
        $str  = " <tr id='node-\$id' \$parentid_node>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td>\$spacer<span data-tdtype='edit' data-field='name' data-id='\$id' class='tdedit'  style='color:\$fcolor'>\$str_name</span></td>
				<td align='center'>\$str_remark</td>
                <td align='center'>\$str_add_time</td>
                <td align='center'>\$str_update_time</td>
                <td align='center'><span data-tdtype='edit' data-field='ordid' data-id='\$id' class='tdedit'>\$ordid</span></td>
				<td align='center'><img data-tdtype='toggle' data-id='\$id' data-field='status' data-value='\$status' src='__STATIC__/images/admin/toggle_\$str_status.gif' /></td>
                <td align='center'>\$str_manage</td>
                </tr>
                ";
        $tree->init($array);
        $menu_list = $tree->get_tree(0, $str);
        $this->assign('menu_list', $menu_list);

        $big_menu = array(
            'title' => '添加用户分类',
            'iframe' => U('user_cate/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '220',
        );
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }   
    
    /**
     * 添加子菜单上级默认选中本栏目
     */
    public function _before_add()
    {    
        $pid = $this->_get('pid', 'intval', 0);
        if ($pid) {
        	$spid = $this->_mod->where(array('id'=>$pid))->getField('spid');
        	$spid = $spid ? $spid.$pid : $pid;
        	$this->assign('spid', $spid);
        }
    
    }   
    
    /**
     * 入库数据整理
     */
    protected function _before_insert($data = '') {
    	//检测分类是否存在
    	if($this->_mod->name_exists($data['name'], $data['pid'])){
    		$this->ajaxReturn(0, L('user_cate_already_exists'));
    	}
    	//生成spid
    	$data['spid'] = $this->_mod->get_spid($data['pid']);
    	$data['add_time'] =  date('Y-m-d H:i:s');
		$data['update_time'] = date('Y-m-d H:i:s');

    	return $data;
    }
    
    /**
     * 修改提交数据
     */
    protected function _before_update($data = '') {        
        $pid = $this->_get('id', 'intval', 0);
        if ($pid) {
        	$spid = $this->_mod->where(array('id'=>$pid))->getField('spid');
        	$spid = $spid ? $spid.$pid : $pid;
        	$this->assign('spid', $spid);
        }
        
    	if ($this->_mod->name_exists($data['name'], $data['pid'], $data['id'])) {
    		$this->ajaxReturn(0, L('user_cate_already_exists'));
    	}
    	$item_cate = $this->_mod->field('pid')->where(array('id'=>$data['id']))->find();
    	if ($data['pid'] != $item_cate['pid']) {
    		//不能把自己放到自己或者自己的子目录们下面
    		$wp_spid_arr = $this->_mod->get_child_ids($data['id'], true);
    		if (in_array($data['pid'], $wp_spid_arr)) {
    			$this->ajaxReturn(0, L('cannot_move_to_child'));
    		}
    		//重新生成spid
    		$data['spid'] = $this->_mod->get_spid($data['pid']);
    	}
		$data['update_time'] =  date('Y-m-d H:i:s');
    	return $data;
    }
    
    public function ajax_getchilds(){
        $id = $this->_get('id', 'intval');
        $return = $this->_mod->field('id,name')->where(array('pid'=>$id))->select();        
        if ($return) {
        	$this->ajaxReturn(1, L('operation_success'), $return);
        } else {
        	$this->ajaxReturn(0, L('operation_failure'));
        }
        
    }
    
}

?>