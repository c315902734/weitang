<?php

class teamAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('team');
		$this->tgroup = [1=>'联席董事',2=>'官方合伙人',3=>'总经销商',4=>'经销商',0=>'普通用户',];
		$this->assign('tgroup', $this->tgroup);
    }

    protected function _search()
    {
        $map = array();
        ($item_id = $this->_request('item_id', 'trim')) && $map['item_id'] = array('eq', $item_id);
        ($keyword = $this->_request('keyword', 'trim')) && $map['info'] = array('like', '%' . $keyword . '%');
        $this->assign('search', array(
            'keyword' => $keyword,
            'item_id' => $item_id,
        ));

        return $map;
    }

    public function _before_index()
    {
		$big_menu = array(
            'title' => '添加团队',
            'iframe' => U('team/add'),
            'id' => 'add',
            'width' => '350',
            'height' => '50',
        );
		$this->assign('big_menu',$big_menu);
    }

	public function _before_insert($data)
    {
		if(D('team')->where(array('title'=>$data['title']))->count() > 0){
			$this->ajaxReturn(0, '团队名称不能重复');
		}
		return $data;
    }

	public function _before_update($data)
    {
		$id = $this->_request('id','intval',0);
		if(D('team')->where(array('title'=>$data['title'],'id'=>array('neq',$id)))->count() > 0){
			$this->ajaxReturn(0, '团队名称不能重复');
		}
		return $data;
    }


    public function delete()
    {
        $id  = $this->_get('id', 'trim');
        $ids = explode(',', $id);
        foreach ($ids as $k => $v) {
			$title = D('team')->where(array('id'=>$v))->getField('title');
            D('user')->where(array('topkey'=>$title))->save(array('topkey'=>'','topclass'=>0));
			$this->_mod->delete($v);
        }
        $this->ajaxReturn(1, '', '');
    }

    public function add_user(){
		if(IS_POST){
			$data = D('user')->create();
			for($i=1;$i<$data['tgroup'];$i++){
				if($data['tgroup_'.$i] == ''){
					$this->ajaxReturn(0,'请选择'.$this->tgroup[$i].'！');
				}
			}
			if($data['username'] == ''){
				$this->ajaxReturn(0,'用户名不能为空！');
			}
			if($data['tele'] == ''){
				$this->ajaxReturn(0,'帐号电话不能为空！');
			}
			if(ceil($data['pers']) != $data['pers']){
				$this->ajaxReturn(0,'收益比例不能为小数');
			}
			if($data['tgroup'] > 1){
				$tgroup_top_user_id = $data['tgroup_'.($data['tgroup']-1)];
				$tgroup_top_user = D('user')->field('pers')->find($tgroup_top_user_id);
				if($data['pers'] > $tgroup_top_user['pers']){
					$this->ajaxReturn(0,'收益比例不能大于'.$tgroup_top_user['pers'].'%');
				}
			}
			if ($data['password'] != '' && $_POST['rpassword'] != '') {
				if (trim($data['password']) != trim($_POST['rpassword'])) {
					$this->ajaxReturn(0,'两次输入的帐号密码不一致，请重新填写！');
				}
			} else {
				$this->ajaxReturn(0,'帐号密码不能为空，请重新填写！');
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
					$this->ajaxReturn(0,'手机号码已经存在，请重新填写！');
				}
			}
			if ($data['username']) {
				//会员昵称是否重复
				if (D('user')->where(array('username' => $data['username']))->count()) {
					$this->ajaxReturn(0,'用户名已经存在，请重新填写！');
				}
			}
			$data['password'] = md5($data['password']);
			$id = $this->_request('id','intval');
			$team = D('team')->find($id);
			$data['topkey'] = $team['title'];
			D('user')->add($data);
			$this->ajaxReturn(1,'帐号添加成功','','add_user');
		}else{
			$id = $this->_request('id','intval');
			$team = D('team')->find($id);
			$this->assign('team',$team);
			$response = $this->fetch();
			$this->ajaxReturn(1, '', $response);
		}
	}

	public function user(){
		 //排序
        if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        }
        else {
            $sort = 'id';
        }
        if ($this->_request("order", 'trim')) {
            $order = $this->_request("order", 'trim');
        }
        else {
            $order = 'DESC';
        }

		$id = $this->_request('id','intval',0);
		$team = D('team')->find($id);
		$map['topkey'] = $team['title'];

		($tele = $this->_get('tele', 'trim','')) && $map['tele'] = array('like',$tele);

		$count = D('user')->where($map)->count();
		$pager = new Page($count,20);
		$list = D('user')->where($map)->order($sort . ' ' . $order)->limit($pager->firstRow . ',' . $pager->listRows)->select();
		foreach($list as $key=>$val){
			$list[$key]['invite'] = D('user')->field('username')->find($val['invite_uid']);
		}
		$page = $pager->show();
		$this->assign('list_table', true);

		$this->assign(compact('team','list','page'));

		$this->assign('search',array('tele'=>$tele));

		$this->display();
	}

	public function price(){
		$id = $this->_request('id','intval',0);
		$team = D('team')->find($id);

		$suse_time = date('Y-m-d') . ' 00:00:00';
        $euse_time = date('Y-m-d') . ' 23:59:59';

		/* 充值统计 */
		$recharge_count_map['ur.status'] = 1;
		$recharge_count_map['ur.type'] = 1;
		$recharge_count_map['u.topkey'] = $team['title'];
		$recharge['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($recharge_count_map)->sum('ur.price');
		$recharge_count_map['ur.add_time'][] = array('elt', $euse_time);
		$recharge_count_map['ur.add_time'][] = array('egt', $suse_time);
		$recharge['days'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($recharge_count_map)->sum('ur.price');

		/* 提现统计 */
		$cash_count_map['ur.status'] = 1;
		$cash_count_map['ur.type'] = 2;
		$cash_count_map['u.topkey'] = $team['title'];
		$cash['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($cash_count_map)->sum('ur.price');
		$cash_count_map['ur.add_time'][] = array('elt', $euse_time);
		$cash_count_map['ur.add_time'][] = array('egt', $suse_time);
		$cash['days'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($cash_count_map)->sum('ur.price');

		$where['u.topkey'] = $team['title'];
		$where['ur.status'] = 1;
		$page_size = 20;
		$count = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($where)->count();
		$pager = new Page($count,$page_size);
		$list = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->field('u.username as username,u.tele as tele,ur.type as type,ur.price as price,ur.add_time as add_time,ur.status as status')->limit($pager->firstRow, $pager->listRows)->where($where)->order('ur.add_time desc')->select();
		$page = $pager->show();
		$this->assign('list',$list);


		$this->assign(compact('team','recharge','cash','page'));
		$this->display();

	}


	public function order(){
		/********************************************* 列表 start *********************************************/
		$id = $this->_request('id','intval',0);
		$team = D('team')->find($id);
		$topkey = $team['title'];
		$map['u.topkey'] = $topkey;
		$map['o.lottery'] = array('IN',array(1,2,9));

		($lottery = $this->_get('lottery', 'intval',0)) && $map['o.lottery'] = $lottery;
		($tele = $this->_get('tele', 'trim','')) && $map['u.tele'] = array('like','%'.$tele.'%');

		($map_stime = $this->_request('stime', 'trim')) && $map['o.add_time'][] = array('egt', $map_stime.' 00:00:00');
        ($map_etime = $this->_request('etime', 'trim')) && $map['o.add_time'][] = array('elt', $map_etime.' 23:59:59');


		($tgroup_1 = $this->_get('tgroup_1', 'intval',0)) && $map['u.tgroup_1'] = $tgroup_1;
		($tgroup_2 = $this->_get('tgroup_2', 'intval',0)) && $map['u.tgroup_2'] = $tgroup_2;
		($tgroup_3 = $this->_get('tgroup_3', 'intval',0)) && $map['u.tgroup_3'] = $tgroup_3;

		
		$type = $this->_get('type', 'intval',0);
		if($type == 1){
			$map['o.status'] = array('IN',[1,2]);
		}elseif($type == 2){
			$map['o.status'] = array('IN',[9]);
		}elseif($type == 3){
			$map['o.status'] = array('IN',[3,4,5,6]);
		}

		$keywords = $this->_get('keywords', 'trim');
		$page_size = 20;
		$count = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->count();
		$pager = new Page($count,$page_size);
		$list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->limit($pager->firstRow, $pager->listRows)->where($map)->field('o.prices as prices,o.status as status,o.add_time as add_time,o.lottery as lottery,u.id as uid,u.username as username,u.tele as tele,o.id as oid,o.orderid as orderid')->order('o.add_time desc')->select();
		$lottery_arr = array(1=>'升级中',2=>'升级成功',9=>'升级失败');
		foreach($list as $key=>$val){
			if(in_array($val['status'],[3,4,5,6])){
				$list[$key]['status'] = '已提货';
			}elseif(in_array($val['status'],[9])){
				$list[$key]['status'] = '退款';
			}else{
				$list[$key]['status'] = '待提货';
			}
			$list[$key]['lottery'] = $lottery_arr[$val['lottery']];

		}
		$this->assign('list',$list);
		$page = $pager->show();
		$this->assign(compact('team','list','page'));
		$this->assign('search',array('type'=>$type,'lottery'=>$lottery,'stime'=>$map_stime,'etime'=>$map_etime,'tele'=>$tele));
		/********************************************* 列表 end *********************************************/

		
		/**************************************************** 统计 start **************************************************************/
		$stime = date('Y-m-d') . ' 00:00:00';
        $etime = date('Y-m-d') . ' 23:59:59';
		$order_map['u.topkey'] = $topkey;
		$order_map['o.lottery'] = array('IN',array(1,2,9));
		$order_map['o.type'] = 0;

		$type = $this->_get('type', 'intval',0);
		if($type == 1){
			$order_map['o.status'] = array('IN',[1,2]);
		}elseif($type == 2){
			$order_map['o.status'] = array('IN',[9]);
		}elseif($type == 3){
			$order_map['o.status'] = array('IN',[3,4,5,6]);
		}


		($uid = $this->_get('uid', 'intval',0)) && $order_map['u.id'] = $uid;
		($lottery = $this->_get('lottery', 'intval',0)) && $order_map['o.lottery'] = $lottery;
		($tele = $this->_get('tele', 'intval',0)) && $order_map['u.tele'] = array('like','%'.$tele.'%');
		($map_stime = $this->_request('stime', 'trim')) && $order_map['o.add_time'][] = array('egt', $map_stime.' 00:00:00');
        ($map_etime = $this->_request('etime', 'trim')) && $order_map['o.add_time'][] = array('elt', $map_etime.' 23:59:59');
		($tgroup_1 = $this->_get('tgroup_1', 'intval',0)) && $order_map['u.tgroup_1'] = $tgroup_1;
		($tgroup_2 = $this->_get('tgroup_2', 'intval',0)) && $order_map['u.tgroup_2'] = $tgroup_2;
		($tgroup_3 = $this->_get('tgroup_3', 'intval',0)) && $order_map['u.tgroup_3'] = $tgroup_3;

		$order['total'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->sum('prices');
		$r_map = $d_map = $order_map;
		$r_map['o.status'] = 9;
		$order['refund'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($r_map)->sum('prices');
		$d_map['o.status'] = array('IN',[3,4,5,6]);
		$order['delivery'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($d_map)->sum('prices');
		$order_map['o.add_time'][] =  array('elt', $etime);
		$order_map['o.add_time'][] =  array('egt', $stime);
		$order['days'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->sum('prices');
		
		$order['total'] = $order['total'] ? $order['total'] : 0.00;
		$order['refund'] = $order['refund'] ? $order['refund'] : 0.00;
		$order['delivery'] = $order['delivery'] ? $order['delivery'] : 0.00;
		$order['days'] = $order['days'] ? $order['days'] : 0.00;
		
		$this->assign('order',$order);

		/**************************************************** 统计 end **************************************************************/

		$this->display();
	}

	/* 用户联动 */
	public function tgroup_child(){
		$id = $this->_get('id','intval',0);
		$topkey = $this->_get('topkey','trim','');
		if($id == 0){
			$map['tgroup'] = 1;
		}else{
			$userinfo = D('user')->field('tgroup,id')->find($id);
			$this_user_tgroup = $userinfo['tgroup'];
			$map['tgroup_'.$this_user_tgroup] = $userinfo['id'];
			$map['tgroup'] = $this_user_tgroup+1;
		}
		$map['topkey'] = $topkey;
		$list = D('user')->where($map)->field('id,username as name')->select();
		if(!$list){
			$this->ajaxReturn(0,'该用户下没有'.$this->tgroup[$this_user_tgroup+1].',请先添加');
		}else{
			$this->ajaxReturn(1,'',$list);
		}
	}


	public function order_total(){
		$id = $this->_get('id','intval');
		$user = D('user')->field('id,username,tele,img,orders,status')->find($id);
		$order_map['o.lottery'] = array('IN',array(1,2,9));
		$order_map['o.type'] = 0;
		$order_map['u.id'] = $user['id'];
		$order_total = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->sum('prices');
		$user['order_total'] = $order_total ? $order_total : 0.00;
		$this->assign("user", $user);
		$this->display();
	}

	/* 获取下级 */
	public function get_child(){
		$id = $this->_request('id','intval');
		$userinfo = D('user')->field('tgroup,id,topkey')->find($id);
		$this_user_tgroup = $userinfo['tgroup'];
		if($this_user_tgroup < 1){
			$this->ajaxReturn(0, '');
		}
		$map['tgroup_'.$this_user_tgroup] = $userinfo['id'];
		if($this_user_tgroup < 3){
			$map['tgroup'] = $this_user_tgroup+1;
		}
		$map['topkey'] = $userinfo['topkey'];
		$count = D('user')->where($map)->count();
		$page_size = 30;
		$pager = new Page($count, $page_size);
		$list = D('user')->field('id,username,tele,img,orders,reg_time,last_time,status,tgroup')->where($map)->limit($pager->firstRow . ',' . $pager->listRows)->select();
		foreach($list as $key=>$val){
			$list[$key]['nums'] = D('user')->where(array('tgroup_'.$val['tgroup']=>$val['id']))->count();
			$list[$key]['img'] = avatar($val['img'], 64);

			$order_map['o.lottery'] = array('IN',array(1,2,9));
			$order_map['o.type'] = 0;
			$order_map['u.id'] = $val['id'];

			$order_total = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->sum('prices');
			$list[$key]['order_total'] = $order_total ? $order_total : 0.00;
		}
		$this->ajaxReturn(1, '', $list);
	}
	
	public function price_total(){
		$id = $this->_get('id','intval');
		$user = D('user')->field('id,username,tele,img,orders,status')->find($id);

		$recharge_map['u.id'] = $user['id'];
		$recharge_map['ur.status'] = 1;

		//充值总额
		$recharge_map['ur.type'] = 1;
		$recharge_total = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');
		$user['recharge_total'] = $recharge_total ? $recharge_total : 0.00;

		//提现总额
		$recharge_map['ur.type'] = 2;
		$cash_total = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');
		$user['cash_total'] = $cash_total ? $cash_total : 0.00;

		$this->assign("user", $user);
		$this->display();
	}

	/* 获取下级 */
	public function get_price_child(){
		$id = $this->_request('id','intval');
		$userinfo = D('user')->field('tgroup,id,topkey')->find($id);
		$this_user_tgroup = $userinfo['tgroup'];
		if($this_user_tgroup < 1){
			$this->ajaxReturn(0, '');
		}
		$map['tgroup_'.$this_user_tgroup] = $userinfo['id'];
		if($this_user_tgroup < 3){
			$map['tgroup'] = $this_user_tgroup+1;
		}
		$map['topkey'] = $userinfo['topkey'];
		$count = D('user')->where($map)->count();
		$page_size = 30;
		$pager = new Page($count, $page_size);
		$list = D('user')->field('id,username,tele,img,orders,reg_time,last_time,status,tgroup')->where($map)->limit($pager->firstRow . ',' . $pager->listRows)->select();
		foreach($list as $key=>$val){
			$list[$key]['nums'] = D('user')->where(array('tgroup_'.$val['tgroup']=>$val['id']))->count();
			$list[$key]['img'] = avatar($val['img'], 64);

			$recharge_map['u.id'] = $val['id'];
			$recharge_map['ur.status'] = 1;

			//充值总额
			$recharge_map['ur.type'] = 1;
			$recharge_total = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');
			$recharge_total = $recharge_total ? $recharge_total : 0.00;

			//提现总额
			$recharge_map['ur.type'] = 2;
			$cash_total = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');
			$cash_total = $cash_total ? $cash_total : 0.00;

			$list[$key]['recharge_total'] = $recharge_total ? $recharge_total : 0.00;
			$list[$key]['cash_total'] = $cash_total ? $cash_total : 0.00;

		}
		$this->ajaxReturn(1, '', $list);
	}

	/* 收益明细 */
	public function price_log(){
		$s_date = $this->_get('month', 'trim', '');
		if($s_date == ''){
			$d = date('d');
			if($d<15){
				$s_date = date('Ym',strtotime('-1 month')).'1';
			}else{
				$s_date = date('Ym',time()).'0';
				$month[] = array('date'=>$s_date,'name'=>date('Y').'年'.date('m').'月上旬');
			}
		}
		for($i=1;$i<=12;$i++){
			$y = date('Y',strtotime('-'.$i.' month'));
			$m = date('m',strtotime('-'.$i.' month'));
			$date = date('Ym',strtotime('-'.$i.' month'));
			$month[] = array('date'=>$date.'1','name'=>$y.'年'.$m.'月下旬');
			$month[] = array('date'=>$date.'0','name'=>$y.'年'.$m.'月上旬');
		}
		$this->assign('month',$month);

		$map_year = substr($s_date,0,4);
		$map_month = substr($s_date,4,2);
		$map_type = substr($s_date,6,1);
		if($map_type == 1){
			$end_time =$this->getMonth($map_year,$map_month,true);
			$start_time = date('Y-m-d H:i:s',strtotime('- 15 days',strtotime($end_time))-86399);
		}else{
			$start_time = $this->getMonth($map_year,$map_month);
			$end_time = date('Y-m-d H:i:s',strtotime('+ 15 days',strtotime($start_time)));
		}

		$id = $this->_request('id','intval',0);
		$team = D('team')->find($id);
		$topkey = $team['title'];
		$map['u.topkey'] = $topkey;


		$map['o.lottery'] = array('IN',array(1,2,9));
		$map['o.type'] = 0;
		$map['o.add_time'][] = array('egt',$start_time);
		$map['o.add_time'][] = array('elt',$end_time);

		($tgroup_1 = $this->_get('tgroup_1', 'intval',0)) && $map['u.tgroup_1'] = $tgroup_1;
		($tgroup_2 = $this->_get('tgroup_2', 'intval',0)) && $map['u.tgroup_2'] = $tgroup_2;
		($tgroup_3 = $this->_get('tgroup_3', 'intval',0)) && $map['u.tgroup_3'] = $tgroup_3;


		$page_size = 20;
		$count = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->count();
		$pager = new Page($count,$page_size);
		$list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->limit($pager->firstRow, $pager->listRows)->where($map)->field('o.prices as prices,o.status as status,o.add_time as add_time,o.lottery as lottery,u.id as uid,u.username as username,u.tele as tele,o.id as oid,o.orderid as orderid,u.tgroup_1 as tgroup_1,u.tgroup_2 as tgroup_2,u.tgroup_3 as tgroup_3,u.invite_uid as invite_uid')->order('o.add_time desc')->select();
		$lottery_arr = array(0=>'待升级',1=>'升级中',2=>'升级成功',9=>'升级失败');
		foreach($list as $key=>$val){
			if(in_array($val['status'],[3,4,5,6])){
				$list[$key]['status'] = '已提货';
			}elseif(in_array($val['status'],[9])){
				$list[$key]['status'] = '退款';
			}else{
				$list[$key]['status'] = '待提货';
			}
			$list[$key]['lottery'] = $lottery_arr[$val['lottery']];
			$list[$key]['tgroup_1_name'] = D('user')->where(['id'=>$val['tgroup_1']])->getField('username');
			$list[$key]['tgroup_2_name'] = D('user')->where(['id'=>$val['tgroup_2']])->getField('username');
			$list[$key]['tgroup_3_name'] = D('user')->where(['id'=>$val['tgroup_3']])->getField('username');

			$tgroup_1_pers = D('user')->where(array('id'=>$val['tgroup_1']))->getField('pers');
			$tgroup_2_pers = D('user')->where(array('id'=>$val['tgroup_2']))->getField('pers');
			$tgroup_3_pers = D('user')->where(array('id'=>$val['tgroup_3']))->getField('pers');
			$tgroup_4_pers = D('user')->where(array('id'=>$val['tgroup_4']))->getField('pers');

			($tgroup_1_pers > 0) && $list[$key]['tgroup_1_pers_price'] = $val['prices']*($tgroup_1_pers*0.01);
			($tgroup_2_pers > 0) && $list[$key]['tgroup_2_pers_price'] = $val['prices']*($tgroup_2_pers*0.01);
			($tgroup_3_pers > 0) && $list[$key]['tgroup_3_pers_price'] = $val['prices']*($tgroup_3_pers*0.01);
			($tgroup_4_pers > 0) && $list[$key]['tgroup_4_pers_price'] = $val['prices']*($tgroup_4_pers*0.01);

		}
		$page = $pager->show();

		$total_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->field('SUM(o.prices) as prices,u.tgroup_1 as tgroup_1')->group('u.tgroup_1')->order('o.id desc')->select();
		$total_tgroup_1 = 0;
		foreach($total_list as $val){
			$pers = D('user')->where(array('id'=>$val['tgroup_1']))->getField('pers');
			$total_tgroup_1 += $val['prices']*($pers*0.01);
		}

		$total_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->field('SUM(o.prices) as prices,u.tgroup_2 as tgroup_2')->group('u.tgroup_2')->order('o.id desc')->select();
		$total_tgroup_2 = 0;
		foreach($total_list as $val){
			$pers = D('user')->where(array('id'=>$val['tgroup_2']))->getField('pers');
			$total_tgroup_2 += $val['prices']*($pers*0.01);
		}

		$total_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->field('SUM(o.prices) as prices,u.tgroup_3 as tgroup_3')->group('u.tgroup_3')->order('o.id desc')->select();
		$total_tgroup_3 = 0;
		foreach($total_list as $val){
			$pers = D('user')->where(array('id'=>$val['tgroup_3']))->getField('pers');
			$total_tgroup_3 += $val['prices']*($pers*0.01);
		}

		$this->assign(compact('list','page','total_tgroup_1','total_tgroup_2','total_tgroup_3','team'));
		$this->assign('search',array('month'=>$s_date));

        $this->display();
	}

	public function getMonth($y,$m,$t=false){
		$year = $y;
		$month = $m;
		$day = $t ? $this->dayInMonth($month,$year) : 1;
		$hour = $t ? 23 : 0;
		$minute = $t ? 59 : 0;
		$second = $t ? 59 : 0;
		return date('Y-m-d H:i:s',mktime($hour,$minute,$second,$month,$day,$year));
	}

	public function dayInMonth($month,$year){
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}





}