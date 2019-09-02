<?php
class menuAction extends backendAction {
    public function _initialize() {
        parent::_initialize();
        // 对于后台的菜单栏，有很多的模块和控制器，智能获取模块名
        // MODULE_NAME 为实际获得的模块名
        $this->_mod = D(MODULE_NAME);
    }

    public function index() {
        $tree = new Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = $this->_mod->order('ordid')->select();  // ordid  是否排序
        /*echo '<pre>';
        print_r($result);
        echo '</pre>';*/
        //print_r($result);
        $array = array();
        // $r['str_index']  push的成员为 是否显示--对或错的图标
        // $r['str_manage'] push的成员为 管理操作栏下的  “添加子菜单|编辑|删除” 操作
        foreach($result as $r) {
            $r['cname'] = L($r['name']);
            $r['str_index']  = '<img data-tdtype="toggle" data-id="'.$r['id'].'" data-field="display" data-value="'.$r['display'].'" src="__STATIC__/images/admin/toggle_' . ($r['display'] == 0 ? 'disabled' : 'enabled') . '.gif" />';
            $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U(MODULE_NAME.'/add',array('pid'=>$r['id'])).'" data-title="'.L('add_submenu').'" data-id="add" data-width="500" data-height="350">'.L('add_submenu').'</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U(MODULE_NAME.'/edit',array('id'=>$r['id'])).'" data-title="'.L('edit').' - '. $r['name'] .'" data-id="edit" data-width="500" data-height="350">'.L('edit').'</a> |
                                <a href="javascript:;" class="J_confirmurl" data-acttype="ajax" data-uri="'.U(MODULE_NAME.'/delete',array('id'=>$r['id'])).'" data-msg="'.sprintf(L('confirm_delete_one'),$r['name']).'">'.L('delete').'</a>';
            $array[] = $r;
        }
        // $str 里面变量前加的‘\’ 都是转义字符
        // data-tdtype='edit'意思是td的小栏目可以编辑  class='tdedit' 意思是td里小栏目的图像笔
        $str  = "<tr>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td>\$spacer<span data-tdtype='edit' data-field='name' data-id='\$id' class='tdedit'>\$name</span></td>
                <td align='center'><span data-tdtype='edit' data-field='module_name' data-id='\$id' class='tdedit'>\$module_name</span></td>
                <td align='center'><span data-tdtype='edit' data-field='action_name' data-id='\$id' class='tdedit'>\$action_name</span></td>
                <td align='center'><span data-tdtype='edit' data-field='data' data-id='\$id' class='tdedit'>\$data</span></td>
                <td align='center'><span data-tdtype='edit' data-field='ordid' data-id='\$id' class='tdedit'>\$ordid</span></td>
                <td align='center'>\$str_index</td>
                <td align='center'>\$str_manage</td>
                </tr>";
        // 初始化菜单
        $tree->init($array);
        $menu_list = $tree->get_tree(0, $str);
        /*echo '<pre>';
        print_r($menu_list);
        echo '</pre>';*/
        //print_r($menu_list);
        $this->assign('menu_list', $menu_list);

        $big_menu = array(
            'title' => '添加菜单',
            'iframe' => U(MODULE_NAME.'/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '350',
        );
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }
    // 添加方法的前置动作函数
    public function _before_add()
    {
        $tree = new Tree();
        $result = $this->_mod->select();
        $array = array();
        foreach($result as $r) {
            $r['selected'] = $r['id'] == $_GET['pid'] ? 'selected' : '';
            $array[] = $r;
        }
        $str  = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        // select下拉框的所有菜单
        $select_menus = $tree->get_tree(0, $str);
        $this->assign('select_menus', $select_menus);
    }
    // 编辑方法的前置动作函数
    // 在执行edit编辑方法前，执行_before_edit前置函数
    public function _before_edit()
    {
        // 获取get过来的id
        $id = $this->_get('id','intval');
        $info = $this->_mod->find($id);
        $this->assign('info', $info);
        $tree = new Tree();
        //
        $result = $this->_mod->select();
        /*print_r($result);
        echo $this->_mod->getLastSql();
        die;*/
        $array = array();
        foreach($result as $r) {
            $r['selected'] = $r['id'] == $info['pid'] ? 'selected' : '';
            $array[] = $r;
        }
        // 此处的$id 为get过来的父级id
        $str  = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        // select下拉框的所有菜单
        $select_menus = $tree->get_tree(0, $str);
        $this->assign('select_menus', $select_menus);
    }
}