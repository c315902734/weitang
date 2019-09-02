<?php
class attrAction extends backendAction{
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('attr');
    }

    public function index() {
        $where = $this->_search();
        $list = $this->_mod->where($where)->order('ordid')->select();
        foreach($list as $key=>$val) {
            $list[$key]['cate_id'] = $this->_mod->get_all_name($val['cate_id']);
            $list[$key]['attr_val'] = $this->_mod->get_attr_val($val['id']);
        }
        $this->assign('list', $list);

        $type_list = array('','下拉框', '输入框');
        $this->assign('type_list', $type_list);
        $big_menu = array(
            'title' => '添加属性',
            'iframe' => U('attr/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '160',
        );
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }
    
    protected function _search(){
        $map = array();
        $cate_id = $this->_request('cate_id', 'intval');
        if($cate_id){
            $child_ids = $this->_mod->get_child_ids($cate_id);
            $count = count($child_ids);
            $child_ids[$count] = $cate_id;
            $map['cate_id'] = array('in', $child_ids);

            $spid = $this->_mod->get_spid($cate_id);
            $spid .= $cate_id;
        }
        ($class_id = $this->_request('class_id', 'intval')) && $map['class_id'] =  array('eq', $class_id);

        $this->assign('search', array(
                'cate_id' => $cate_id,
                'class_id' => $class_id,
                'spid' => $spid,
        ));
    
        return $map;
    }
    
    public function add_val()
    {    
        $attr_id = $this->_request('attr_id', 'intval', 0);
        $val_mod = D('attr_val');
        if(IS_POST) {
            if (false === $data = $val_mod->create()) {
                IS_AJAX && $this->ajaxReturn(0, $val_mod->getError());
            }
            if ($val_mod->add($data)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'add_val');
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'),U('attr_val/add'));
            }
        } else {
            $attr_list = $this->_mod->select();
            $this->assign('attr_list', $attr_list);
            $this->assign('attr_id', $attr_id);
            $this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
        }
    
    }
    
    
    protected function _before_insert($data = '') {
    	//检测属性名称是否存在
    	/*if($this->_mod->name_exists($data['name'])){
    		$this->ajaxReturn(0, '属性名称已经存在');
    	}*/
    	return $data;
    }
    
    public function _before_edit(){
        $id = $this->_get('id','intval');
        $attr = $this->_mod->where(array('id'=>$id))->field('cate_id,class_id')->find();
        $spid = D('item_cate')->where(array('id'=>$attr['cate_id']))->getField('spid');
        
        if( $spid==0 ){
            $spid = $attr['cate_id'];
        }else{
            $spid .= $attr['cate_id'];
        }
        $this->assign('spid',$spid);
    }

    /**
     * 修改提交数据
     */
    protected function _before_update($data = '') {        
        //检测属性名称是否存在
        /*if($this->_mod->name_exists($data['name'], $data['id'])){
            $this->ajaxReturn(0, '属性名称已经存在');
        }*/
        return $data;
    }
    
    public function del_val() {
        $id = $this->_get('id', 'intval');
        D('attr_val')->delete($id);
        $this->ajaxReturn(1, L('operation_success'));
    }
}
?>