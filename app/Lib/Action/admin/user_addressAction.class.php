<?php
class user_addressAction extends backendAction
{
	protected $pk = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('user_address');
    }

    protected function _search() {
        $map = array();
        ($uname = $this->_request('uname', 'trim')) && $map['uname'] =  array('like', '%'.$uname.'%');
        ($is_show = $this->_request('is_show', 'trim')) && $map['is_show'] = array('eq', $is_show);
        ($is_default = $this->_request('is_default', 'trim')) && $map['is_default'] = array('eq', $is_default);
        ($status = $this->_request('status', 'trim')) && $map['status'] = array('eq', $status);
       
        $this->assign('search', array(
			'uname' => $uname,
            'is_show' => $is_show,
            'is_default' => $is_default,
            'status' => $status,
        ));
        return $map;
    }

    public function _before_index() {
        $sub_menu = array(
			0=>array(
				'name' => '添加地址',
				'action_name' => 'add',
				'module_name' => 'user_address',
				'class' => 'add'
			)
        );
        $this->assign('sub_menu', $sub_menu);

        $this->list_relation = true;
        $this->_before_add();
		//默认排序
        $this->sort = 'id';
        $this->order = 'DESC';
        $this->assign('img_dir',$this->_get_imgdir());
		$city_list = D('city')->select();
		foreach($city_list as $k=>$v){
			$city_list[$v['id']] = $v['name'];
		}
        $this->assign('city_list',$city_list);
    }

    public function _before_add() {
        $cate_list = D('flink_cate')->where(array('status'=>1))->select();
        $this->assign('cate_list',$cate_list);
    }

    public function _before_edit()
    {
        $this->_before_add();
        $id = $this->_request('id');
        $info = $this->_mod->where(array('id'=>$id))->find();
        $spid = $ppd = '';
        if($info['province_id']){
            $spid .= $info['province_id'];
            $ppd   = $info['province_id'];
        }
        if($info['city_id']){
            $spid .= '|'.$info['city_id'];
            $ppd   = $info['city_id'];
        }
        if($info['area_id']){
            $spid .= '|'.$info['area_id'];
            $ppd   = $info['area_id'];
        }
        $this->assign('selected_cids',$spid);
        $this->assign('ppd',$ppd);
        $this->assign('img_dir',$this->_get_imgdir());
    }

    public function ajax_check_name()
    {
        $name = $this->_get('name', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name, $id)) {
            $this->ajaxReturn(0, '链接名称已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

    /**
     * 友情链接图片上传目录
     *
     * @staticvar null $dir
     * @return string
     */
    private function _get_imgdir() {
        static $dir = null;
        if ($dir === null) {
            $dir = './data/upload/flink/';
        }
        return $dir;
    }

	public function _get_uids($uname){
		$where['username'] = array('like', '%'.$uname.'%');
		$uidarr = D('user')->where($where)->field('id')->select();
		$_idarr = array();
		foreach($uidarr as $v){
			$_idarr[] = $v['id'];
		}
		return implode(',',$_idarr);
	}

	/**
     * 入库数据整理
     */
    protected function _before_insert($data = '') {
		$order_uid = $this->_request('order_uid', 'intval');
		if($order_uid){
			$user = D('user')->where('id='.$order_uid)->field('id,username')->find();
			$data['uid'] = $user['id'];
			$data['uname'] = $user['username'];
		}
        $c_id = $this->_request('c_id');
        if($c_id){
            $spid = D('city')->where(array('id'=>$c_id))->getField('spid');
            $ex = explode('|', $spid);
            $count = count($ex);
            $data['province'] = $data['city'] = $data['area'] = '';
            $data['province_id'] = $data['city_id'] = $data['area_id'] = '0';
            if($count == 1){
                $data['province'] = D('city')->where(array('id'=>$c_id))->getField('name');
                $data['province_id'] = $c_id;
            } elseif($count == 2){
                $data['province'] = D('city')->where(array('id'=>$ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city'] = D('city')->where(array('id'=>$c_id))->getField('name');
                $data['city_id'] = $c_id;
            } elseif($count == 3){
                $data['province'] = D('city')->where(array('id'=>$ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city'] = D('city')->where(array('id'=>$ex[1]))->getField('name');
                $data['city_id'] = $ex[1];
                $data['area'] = D('city')->where(array('id'=>$c_id))->getField('name');
                $data['area_id'] = $c_id;
            }
        }
		
    	return $data;
    }

	/**
     * 修改提交数据
     */
    protected function _before_update($data = '') {       
		$order_uid = $this->_request('order_uid', 'intval');
		if($order_uid){
			$user = D('user')->where('id='.$order_uid)->field('id,username')->find();
			$data['uid'] = $user['id'];
			$data['uname'] = $user['username'];
		}
        $c_id = $this->_request('c_id');
        if($c_id){
            $spid = D('city')->where(array('id'=>$c_id))->getField('spid');
            $ex = explode('|', $spid);
            $count = count($ex);
            $data['province'] = $data['city'] = $data['area'] = '';
            $data['province_id'] = $data['city_id'] = $data['area_id'] = '0';
            if($count == 1){
                $data['province'] = D('city')->where(array('id'=>$c_id))->getField('name');
                $data['province_id'] = $c_id;
            } elseif($count == 2){
                $data['province'] = D('city')->where(array('id'=>$ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city'] = D('city')->where(array('id'=>$c_id))->getField('name');
                $data['city_id'] = $c_id;
            } elseif($count == 3){
                $data['province'] = D('city')->where(array('id'=>$ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city'] = D('city')->where(array('id'=>$ex[1]))->getField('name');
                $data['city_id'] = $ex[1];
                $data['area'] = D('city')->where(array('id'=>$c_id))->getField('name');
                $data['area_id'] = $c_id;
            }
        }
    	return $data;
    }

    public function search_user(){
        $username = $this->_request('username', 'trim');
        $user_list = D('user')->where('username like "%'.$username.'%"')->field('id,username')->select();
        $str = '';
        if($user_list){
            foreach($user_list as $k=>$v){
                $str .= '<option value="'.$v['id'].'">'.$v['username'].'</option>';
            }
        }
        echo $str;
    }
}