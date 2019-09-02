<?php
class menuModel extends baseModel {
    
    protected $_validate = array(
        array('name', 'require', '{%menu_name_require}'), //菜单名称为必须
        array('name', 'require', '{%module_name_require}'), //模块名称必须
        array('name', 'require', '{%action_name_require}'), //方法名称必须
    );

    public function admin_menu($pid, $with_self=false) {
        $pid = intval($pid);
        $where="pid=$pid and display=1 ";
        if ($with_self) {
            $where.=" or id=$pid";
        }
        if($_SESSION['admin']['role_id']>1){
            $where.=" and (select count(a.menu_id) from ".C(DB_PREFIX)."admin_auth as a 
                where a.role_id=".$_SESSION['admin']['role_id']." and a.menu_id=id)>0";   
        }
        $menus = M("menu")->where($where)->order('ordid')->select();     
        //print_r($menus);exit();
        return $menus;
    }
    
    public function sub_menu($pid = '', $big_menu = false) {
        $array = $this->admin_menu($pid, false);
        //print_r($array);exit();
        $numbers = count($array);
        // if ($numbers==1 && !$big_menu) {
        //     return '';
        // }
        return $array;
    }
    
    public function get_level($id,$array=array(),$i=0) {
        foreach($array as $n=>$value){
            if ($value['id'] == $id) {
                if($value['pid']== '0') return $i;
                if($i == 4){
                    return $i;
                }
                $i++;
                return $this->get_level($value['pid'],$array,$i);
            }
        }
    }
    function get_menu_data(){            
        $where="display=1";
        if($_SESSION['admin']['role_id']>1){
            $where.=" and (select count(a.menu_id) from ".C(DB_PREFIX)."admin_auth as a 
            where a.role_id=".$_SESSION['admin']['role_id']." and a.menu_id=id)>0"; 
        }
        $res=$this->where($where)->field("id,name,module_name as m,action_name as a,data")->select();
        $menu_data=array('id_0'=>array('name'=>'后台首页','m'=>'index','a'=>'panel','data'=>''));
        foreach($res as $key=>$val){
            $menu_data['id_'.$val['id']]=$val;
            unset($menu_data['id_'.$val['id']]['id']);
        }
  
        return $menu_data;
    }
}
