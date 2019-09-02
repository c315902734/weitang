<?php

/**
 * 用户信息管理
 */
class userAction extends backendAction
{

    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('user');
		$this->tgroup = [0=>'普通用户',1=>'联席董事',2=>'官方合伙人',3=>'总经销商',4=>'经销商'];
		$this->assign('tgroup', $this->tgroup);
    }
    // 按条件搜索
    protected function _search()
    {
        // 满足条件的映射数组
        $map = array();
        if ($keyword = $this->_request('keyword', 'trim')) {
            $map['username|email'] = array('like', "%" . $keyword . "%");
        }

        if ($tele = $this->_request('tele', 'trim')) {
            $map['tele'] = array('like', "%" . $tele . "%");
        }
        if ($realname = $this->_request('realname', 'trim')) {
            $map['realname'] = array('like', "%" . $realname . "%");
        }
		
		if ($topkey = $this->_request('topkey', 'trim')) {
            $map['topkey'] = $topkey;
        }
		
		if ($tgroup = $this->_request('tgroup', 'intval')) {
            $map['tgroup'] = $tgroup;
        }

        $status = $this->_request('status', 'intval', '-1');
        if ($status >= 0) {
            $map['status'] = $status;
        }
        $this->assign('search', array(
            'keyword'  => $keyword,
            'tele'     => $tele,
            'realname' => $realname,
            'status'   => $status,
            'topkey'   => $topkey,
            'tgroup'   => $tgroup,
        ));
        return $map;
    }

    public function index()
    {
        $map = $this->_search();
        $mod = D($this->_name);
        if (!empty($mod)) {
            $result = $this->_list($mod, $map);
            $this->assign('page', $result['page']);
			$list = $result['list'];
			foreach($list as $key=>$val){
				$list[$key]['invite'] = D('user')->field('username')->find($val['invite_uid']);
			}
            $this->assign('list', $list);
            $this->display();
        }

    }

    public function download()
    {
        $map    = $this->_search();
        $mod    = D($this->_name);
        $result = $this->_list($mod, $map, '', '', '*', 0);
        $i      = 0;
        $sex    = array('0' => '女', '1' => '男');
        $status = array('0' => '停用', '1' => '启用');
        foreach ($result['list'] as $key => $val) {
            $data[$i]['id']           = $val['id'];
            $data[$i]['username']     = $val['username'];
            $data[$i]['realname']     = $val['realname'];
            $data[$i]['tele']         = $val['tele'];
            $data[$i]['age']          = $val['age'];
            $data[$i]['sex']          = $sex[$val['sex']];
            $data[$i]['favs']         = $val['favs'];
            $data[$i]['likes']        = $val['likes'];
            $data[$i]['orders']       = $val['orders'];
            $data[$i]['reg_time']     = $val['reg_time'];
            $data[$i]['last_time']    = $val['last_time'];
            $data[$i]['total_month']  = $val['total_month'];
            $data[$i]['total_season'] = $val['total_season'];
            $data[$i]['total_year']   = $val['total_year'];
            $data[$i]['status']       = $status[$val['status']];
            $i++;
        }
        Vendor('excelClass.excelclass');
        $excel = new excelClass();
        $excel->echoUserFile('用户' . date('YmdHis') . '.xls', $data);
        exit;
    }

    public function _before_insert($data)
    {
        if ($data['password'] != '' && $_POST['repassword'] != '') {
            if (trim($data['password']) != md5(trim($_POST['repassword']))) {
                $this->error('两次输入的会员密码不一致，请重新填写！');
            }
        }
        else {
            $this->error('会员密码不能为空，请重新填写！');
        }
        if (!$data['reg_time']) {
            $data['reg_time'] = date('Y-m-d H:i:s', time());
        }
        if (!$data['status']) {
            $data['status'] = 1;
        }
        if ($data['tele']) {
            //手机号码是否重复
            if (D('user')->where(array('tele' => $data['tele']))->count()) {
                $this->error('手机号码已经存在，请重新填写！');
            }
        }
        if ($data['username']) {
            //会员昵称是否重复
            if (D('user')->where(array('username' => $data['username']))->count()) {
                $this->error('会员昵称已经存在，请重新填写！');
            }
        }
        if (!$data['username']) {
            $data['username'] = $data['tele'];
        }
        return $data;
    }

    public function _after_insert($id)
    {
        $img = $this->_post('img', 'trim');
        $this->user_thumb($id, $img);

        $c_id = $this->_request('c_id');
        if ($c_id) {
            $spid                = D('city')->where(array('id' => $c_id))->getField('spid');
            $ex                  = explode('|', $spid);
            $count               = count($ex);
            $data['province']    = $data['city'] = $data['area'] = '';
            $data['province_id'] = $data['city_id'] = $data['area_id'] = '0';
            if ($count == 1) {
                $data['province']    = D('city')->where(array('id' => $c_id))->getField('name');
                $data['province_id'] = $c_id;
            }
            elseif ($count == 2) {
                $data['province']    = D('city')->where(array('id' => $ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city']        = D('city')->where(array('id' => $c_id))->getField('name');
                $data['city_id']     = $c_id;
            }
            elseif ($count == 3) {
                $data['province']    = D('city')->where(array('id' => $ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city']        = D('city')->where(array('id' => $ex[1]))->getField('name');
                $data['city_id']     = $ex[1];
                $data['area']        = D('city')->where(array('id' => $c_id))->getField('name');
                $data['area_id']     = $c_id;
            }
        }

        //注册完成钩子
        $username = D('user')->where('id=' . $id)->getField('username');
        $tag_arg  = array('uid' => $id, 'uname' => $username, 'action' => 'register');
        tag('register_end', $tag_arg);
    }

    public function _before_update($data)
    {
        if (($data['password'] != '') && (trim($data['password']) != '')) {
            $data['password'] = md5($data['password']);
        }
        else {
            unset($data['password']);
        }
        if ($data['img'] == '') {
            unset($data['img']);
        }
        if ($data['cover'] == '') {
            unset($data['cover']);
        }
        $c_id = $this->_request('c_id');
        if ($c_id) {
            $spid                = D('city')->where(array('id' => $c_id))->getField('spid');
            $ex                  = explode('|', $spid);
            $count               = count($ex);
            $data['province']    = $data['city'] = $data['area'] = '';
            $data['province_id'] = $data['city_id'] = $data['area_id'] = '0';
            if ($count == 1) {
                $data['province']    = D('city')->where(array('id' => $c_id))->getField('name');
                $data['province_id'] = $c_id;
            }
            elseif ($count == 2) {
                $data['province']    = D('city')->where(array('id' => $ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city']        = D('city')->where(array('id' => $c_id))->getField('name');
                $data['city_id']     = $c_id;
            }
            elseif ($count == 3) {
                $data['province']    = D('city')->where(array('id' => $ex[0]))->getField('name');
                $data['province_id'] = $ex[0];
                $data['city']        = D('city')->where(array('id' => $ex[1]))->getField('name');
                $data['city_id']     = $ex[1];
                $data['area']        = D('city')->where(array('id' => $c_id))->getField('name');
                $data['area_id']     = $c_id;
            }
        }
        //print_r($data);die;
        return $data;
    }

    public function _after_update($id)
    {
        $img = $this->_post('img', 'trim');
        if ($img) {
            $this->user_thumb($id, $img);
        }
    }

    public function user_thumb($id, $img)
    {
        $img_path = avatar_dir($id);
        //会员头像规格
        $avatar_size = explode(',', C('ins_avatar_size'));
        $paths       = C('ins_attach_path');

        foreach ($avatar_size as $size) {
            if ($paths . 'avatar/' . $img_path . md5($id) . '_' . $size . '.jpg') {
                @unlink($paths . 'avatar/' . $img_path . md5($id) . '_' . $size . '.jpg');
            }
            !is_dir($paths . 'avatar/' . $img_path) && mkdir($paths . 'avatar/' . $img_path, 0777, true);
            Image::thumb($paths . 'avatar/temp/' . $img, $paths . 'avatar/' . $img_path . md5($id) . '_' . $size . '.jpg', '', $size, $size, true);
        }
    }
    // 批量增加会员
    public function add_users()
    {
        if (IS_POST) {
            $users    = $this->_post('username', 'trim');
            $users    = explode(',', $users);
            $password = $this->_post('password', 'trim');
            $tele     = $this->_post('tele', 'trim');
            $sex      = $this->_post('sex', 'intavl');
            $reg_time = date("Y-m-d H:i:s");
            $data     = array();
            foreach ($users as $val) {
                $data['password'] = $password;
                $data['tele']     = $tele;
                $data['sex']      = $sex;
                $data['reg_time'] = $reg_time;
                if ($sex == 3) {
                    $data['sex'] = rand(0, 1);
                }
                $data['username'] = $val;
                $this->_mod->create($data);
                $this->_mod->add();
            }
            $this->success(L('operation_success'));
        }
        else {
            $this->display();
        }
    }

    public function ajax_upload_imgs_cover()
    {
        //上传图片
        if (!empty($_FILES['cover']['name'])) {
            $result = $this->_upload($_FILES['cover'], array('width' => '980', 'height' => '200'));
            if ($result['error']) {
                $this->error($result['info']);
            }
            else {
                $data['cover'] = $result['data'][0]['savePath'];
                $this->ajaxReturn(1, L('operation_success'), $data['cover']);
            }
        }
        else {
            $this->ajaxReturn(0, L('illegal_parameters'));
        }
    }

    /**
     * ajax检测会员是否存在
     */
    public function ajax_check_name()
    {
        $name = $this->_get('username', 'trim');
        $id   = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name, $id)) {
            $this->ajaxReturn(0, '该会员已经存在');
        }
        else {
            $this->ajaxReturn();
        }
    }

    /**
     * ajax检测邮箱是否存在
     */
    public function ajax_check_email()
    {
        $name = $this->_get('email', 'trim');
        $id   = $this->_get('id', 'intval');
        if ($this->_mod->email_exists($name, $id)) {
            $this->ajaxReturn(0, '该邮箱已经存在');
        }
        else {
            $this->ajaxReturn();
        }
    }

    public function ajax_check_tele()
    {
        $tele  = $this->_get('tele', 'trim');
        $id    = $this->_get('id', 'intval');
        $where = compact('tele');
        if (!empty($id)) {
            $where['id'] = array('neq', $id);
        }
        if ($this->_mod->where($where)->count()) {
            $this->ajaxReturn(0, '该手机已经存在');
        }
        else {
            $this->ajaxReturn();
        }
    }

    public function _before_add()
    {
        $level_list = D('user_level')->order('is_default desc')->select();
		//团队列表
		$team = D('team')->where(array('status'=>1))->order('id asc')->select();
        $this->assign(compact('level_list','team'));
    }

    /**
     * 添加子菜单上级默认选中本栏目
     */
    public function _before_edit()
    {
        $this->_before_add();
        //获取城市分类
        $id   = $this->_get('id', 'intval');
        $info = $this->_mod->field('id,province_id,city_id,area_id,birthday')->where(array('id' => $id))->find();
        $spid = $ppd = '';
        if ($info['province_id']) {
            $spid .= $info['province_id'];
            $ppd = $info['province_id'];
        }
        if ($info['city_id']) {
            $spid .= '|' . $info['city_id'];
            $ppd = $info['city_id'];
        }
        if ($info['area_id']) {
            $spid .= '|' . $info['area_id'];
            $ppd = $info['area_id'];
        }
        $this->assign('selected_cids', $spid);
        $this->assign('ppd', $ppd);
		$team = D('team')->where(array('status'=>1))->order('id asc')->select();
        $this->assign('team',$team);
    }

    public function reset_password()
    {
        $id = $this->_request('id', 'intval');
        if (IS_POST) {
            $password   = $this->_post('password', 'trim');
            $repassword = $this->_post('repassword', 'trim');
            if ($password != $repassword) {
                IS_AJAX && $this->ajaxReturn(0, '两次输入的密码不一致');
                $this->error('两次输入的密码不一致');
            }
            else {
                D('user')->where(array('id' => $id))->setField('password', md5($password));
                IS_AJAX && $this->ajaxReturn(1, '密码重置成功', '', 'reset_password');
                $this->error('密码重置成功');
            }
        }
        else {
            $this->assign('id', $id);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }
	
	public function price()
    {
        $id = $this->_request('id', 'intval');
        if (IS_POST) {
			$info = D('user')->field('id,price,username')->find($id);
            $price   = $this->_post('price', 'trim');
			D('user')->where(array('id' => $info['id']))->setField('price', $price);

			D('price_log')->add([
				'uid'           => $info['id'],
				'uname'         => $info['username'],
				'key_id'        => 0,
				'action'        => 'admin',
				'price'         => $price - $info['price'],
				'add_time'      => current_date(),
				'remark'        => "后台调整",
			]);
			IS_AJAX && $this->ajaxReturn(1, '修改成功', '', 'price');
        }
        else {
            $this->assign('id', $id);
			$info = D('user')->field('price,username')->find($id);
            $this->assign('info', $info);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }
	
	public function topclass()
    {
        $id = $this->_request('id', 'intval');
        if (IS_POST) {
			$info = D('user')->field('id,topclass')->find($id);
            $topclass   = $this->_post('topclass', 'intval');
            $topkey   = $this->_post('topkey', 'trim');
            $tgroup   = $this->_post('tgroup', 'intval',0);
            $tgroup_1   = $this->_post('tgroup_1', 'intval',0);
            $tgroup_2   = $this->_post('tgroup_2', 'intval',0);
            $tgroup_3   = $this->_post('tgroup_3', 'intval',0);
            $tgroup_4   = $this->_post('tgroup_4', 'intval',0);
            $pers   = $this->_post('pers', 'intval',0);
			if($pers < 1){
				$this->ajaxReturn(0, '收益比例不能小于1');
			}

			if(ceil($pers)!=$pers){
				$this->ajaxReturn(0,'收益比例不能为小数');
			}
			$user_info = D('user')->field('tgroup,tgroup_1,tgroup_2,tgroup_3,tgroup_4')->find($id);
			if($user_info['tgroup'] > 1){
				$tgroup_top_user_id = $user_info['tgroup_'.($user_info['tgroup']-1)];
				$tgroup_top_user = D('user')->field('pers')->find($tgroup_top_user_id);
				if($pers > $tgroup_top_user['pers']){
					$this->ajaxReturn(0,'收益比例不能大于'.$tgroup_top_user['pers'].'%');
				}
			}

			D('user')->where(array('id' => $info['id']))->save(array(
				'topclass'=>$topclass,
				'topkey'=>$topkey,
				'tgroup'=>$tgroup,
				'tgroup_1'=>$tgroup_1,
				'tgroup_2'=>$tgroup_2,
				'tgroup_3'=>$tgroup_3,
				'tgroup_4'=>$tgroup_4,
				'pers'=>$pers,
			));
			IS_AJAX && $this->ajaxReturn(1, '修改成功', '', 'topclass');
        }
        else {
            $this->assign('id', $id);
			$info = D('user')->field('topclass,topkey,tgroup,tgroup_1,tgroup_2,tgroup_3,tgroup_4,pers')->find($id);
            $this->assign('info', $info);
			//团队列表
			$team = D('team')->where(array('status'=>1))->order('id asc')->select();
            $this->assign('team', $team);

			for($i=1;$i<$info['tgroup'];$i++){
				$data['tgroup_'.$i] = $info['tgroup_'.$i];
			}

            $this->assign('data', $data);



            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

	public function relationship(){
		if ($keyword = $this->_request('keyword', 'trim')) {
            $map['username'] = array('like', "%" . $keyword . "%");
        }

        if ($tele = $this->_request('tele', 'trim')) {
            $map['tele'] = array('like', "%" . $tele . "%");
        }
		$map['topclass'] = 1;
		$count = D('user')->where($map)->count();
		$pager = new Page($count, $page_size);
		$list = D('user')->where($map)->order('reg_time desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
		$this->assign('list', $list);
        $this->assign('list_table', true);
		$this->assign("page", $page);
		$this->assign("search", array(
			'keyword' =>$keyword,
			'tele' =>$tele,
		));
		$this->display();
	}
	
	public function lower(){

		if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        }
        else {
            $sort = 'reg_time';
        }
        if ($this->_request("order", 'trim')) {
            $order = $this->_request("order", 'trim');
        }
        else {
            $order = 'DESC';
        }


		$map['invite_uid'] = $this->_get('id','intval',0);
		$count = D('user')->where($map)->count();
		$pager = new Page($count, $page_size);
		$list = D('user')->where($map)->order($sort . ' ' . $order)->limit($pager->firstRow . ',' . $pager->listRows)->select();
		$this->assign('list', $list);
        $this->assign('list_table', true);
		$this->assign("page", $page);
		$this->assign("search", array(
			'keyword' =>$keyword,
			'tele' =>$tele,
		));
		$this->display();
	}



	/* 用户关系 */
	public function relationship_tree(){
		$id = $this->_get('id','intval');
		$user = D('user')->field('id,username,tele,img,orders,status')->find($id);
		$this->assign("user", $user);
		$this->display();
	}

	/* 获取下级 */
	public function get_child(){
		$id = $this->_request('id','intval');
		$user = D('user')->field('topkey')->find($id);
		$count = D('user')->where(array('topkey'=>$user['topkey']))->count();
		$page_size = 30;
		$pager = new Page($count, $page_size);
		$list = D('user')->field('id,username,tele,img,orders,reg_time,last_time,status')->where(array('topkey'=>$user['topkey']))->limit($pager->firstRow . ',' . $pager->listRows)->select();
		foreach($list as $key=>$val){
			$list[$key]['nums'] = D('user')->where(array('topkey'=>array('IN',$val['id'])))->count();
			$list[$key]['img'] = avatar($val['img'], 64);
		}
		$this->ajaxReturn(1, '', $list);

	}

	/* 编辑上下级 */
	public function relationship_edit(){
		if(IS_POST){
			$user_id = $this->_post('user_id','intval');
			$id = $this->_post('id','intval');
			if($user_id == ''){
				$this->ajaxReturn(0, '请选择上级用户');
			}
			if($id == $user_id){
				$this->ajaxReturn(0, '上级不能是自己');
			}

			$iuser = D('user')->where(array('id'=>$user_id))->field('id,topkey,tgroup,tgroup_1,tgroup_2,tgroup_3,tgroup_4')->find();
			$save_data['invite_uid'] = $user_id;
			($iuser['tgroup_1'] > 0) && $save_data['tgroup_1'] = $iuser['tgroup_1'];
			($iuser['tgroup_2'] > 0) && $save_data['tgroup_2'] = $iuser['tgroup_2'];
			($iuser['tgroup_3'] > 0) && $save_data['tgroup_3'] = $iuser['tgroup_3'];
			($iuser['tgroup_4'] > 0) && $save_data['tgroup_4'] = $iuser['tgroup_4'];

			if(in_array($iuser['tgroup'],[1,2,3,4])){
				$save_data['tgroup_'.$iuser['tgroup']] = $iuser['id'];
			}
			D('user')->where(array('id'=>$id))->save($save_data);

			$this->ajaxReturn(1, '', '','edit');

		}else{
			$id = $this->_get('id','intval');
			$user = D('user')->field('id,invite_uid,username,tele')->find($id);
			$invite_user = D('user')->field('username,tele,topkey')->find($user['invite_uid']);
			$this->assign("user", $user);
			$this->assign("invite_user", $invite_user);
			$this->assign("id", $user['id']);
			$response = $this->fetch();
			$this->ajaxReturn(1, '', $response);
		}
	}

	public function searchuser(){
		$keyword = $this->_post('keyword','trim');
		$map = array();
		if($keyword != '') $map['username|tele'] = array('like','%'.$keyword.'%');
		$list = D('user')->where($map)->select();
		$this->ajaxReturn(1, '', array('list'=>$list));
	}






}