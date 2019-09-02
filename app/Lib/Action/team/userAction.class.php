<?php
class userAction extends teamAction
{
    public function _initialize()
    {
        parent::_initialize();
        if ($_GET['session']) {
            print_r(session_id());
            exit();
        }
        if (!$this->is_visitor_login()) {
            $this->redirect('passport/login');
        }

        if (D('user')->where(['id' => $this->get_visitor_id()])->count() == 0) {
            $this->visitor->logout();
        }

		$this->user_tgroup = $this->visitor->get('tgroup');
    }
	
	/* 用户首页 */
    public function index()
    {
		$info = D('user')->field('topkey,topclass,tgroup,status,price')->find($this->get_visitor_id());
		$nums = D('user')->where(array('topkey'=>$info['topkey']))->count();
		
		$topkey = $info['topkey'];
		$where['u.topkey'] = $topkey;
		$where['o.lottery'] = array('IN',array(1,2,9));

		$stime = date('Y-m-d') . ' 00:00:00';
        $etime = date('Y-m-d') . ' 23:59:59';

		/* 统计 */
		$order['total'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->sum('prices');
		$where['o.add_time'][] =  array('elt', $etime);
		$where['o.add_time'][] =  array('egt', $stime);
		$order['days'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->sum('prices');

		$order['total'] = $order['total'] ? $order['total'] : 0.00;
		$order['days'] = $order['days'] ? $order['days'] : 0.00;

		$this->assign(compact('info','nums','order'));
        $this->display();
    }

	/* 团队管理 */
	public function team()
    {

		$topkey = $this->visitor->get('topkey');
		$where['topkey'] = $topkey;
		$where['tgroup_'.$this->user_tgroup] = $this->uid;

		$tgroup = $this->_get('tgroup', 'intval',0);

		if($tgroup > 0 && $tgroup < 4){
			$where['tgroup'] = $tgroup;
		}elseif($tgroup == 4){
			$where['tgroup'][] = 4;
			$where['tgroup'][] = 0;
			$where['tgroup'][] = 'or';
		}

		($tele = $this->_get('tele', 'trim','')) && $where['tele'] = array('like',$tele);


		$page_size = 20;
		$count = D('user')->where($where)->count();
		$pager = new Page($count,$page_size);
		$list = D('user')->where($where)->limit($pager->firstRow, $pager->listRows)->order('reg_time desc')->select();
		$page = $pager->show();
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('search',array('tgroup'=>$tgroup,'tele'=>$tele));


		/* 统计 */
		$stime = date('Y-m-d') . ' 00:00:00';
        $etime = date('Y-m-d') . ' 23:59:59';

		/**************************************************** 交易统计 start **************************************************************/
		$order_map['u.topkey'] = $topkey;
		$order_map['u.tgroup_'.$this->user_tgroup] = $this->uid;
		$order_map['o.lottery'] = array('IN',array(1,2,9));
		$order_map['o.type'] = 0;

		$order['total'] = $order['refund'] = $order['delivery'];

		//交易总额
		$order_total_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->field('o.lottery as lottery,o.lottery_total as lottery_total,o.prices as prices')->select();
		foreach($order_total_list as $tval){
			$order['total'] += ($tval['lottery'] == 2 ? $tval['lottery_total'] : $tval['prices']);
		}

		$r_map = $d_map = $order_map;

		//退款总额
		$r_map['o.status'] = 9;
		$order_refund_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($r_map)->field('o.lottery as lottery,o.lottery_total as lottery_total,o.prices as prices')->select();
		foreach($order_refund_list as $rval){
			$order['refund'] += ($rval['lottery'] == 2 ? $rval['lottery_total'] : $rval['prices']);
		}
		//提货总额
		$d_map['o.status'] = array('IN',[2,3,4,5,6]);//待提货也加入统计(升级失败待提货加入统计)
		$order_delivery_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($d_map)->field('o.lottery as lottery,o.lottery_total as lottery_total,o.prices as prices')->select();
		foreach($order_delivery_list as $dval){
			$order['delivery'] += ($dval['lottery'] == 2 ? $dval['lottery_total'] : $dval['prices']);
		}

		$this->assign('order',$order);

		/**************************************************** 交易统计 end **************************************************************/


		/**************************************************** 充值、提现统计 start **************************************************************/
		$recharge_map['u.topkey'] = $topkey;
		$recharge_map['u.tgroup_'.$this->user_tgroup] = $this->uid;
		$recharge_map['ur.status'] = 1;

		//充值总额
		$recharge_map['ur.type'] = 1;
		$recharge['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');

		//提现总额
		$recharge_map['ur.type'] = 2;
		$cash['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');

		//我的.充值总额
		unset($recharge_map['u.tgroup_'.$this->user_tgroup]);
		$recharge_map['u.id'] = $this->visitor->info['id'];
		$recharge_map['ur.type'] = 1;
		$recharge_user['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');

		//我的.提现总额
		$recharge_map['ur.type'] = 2;
		$cash_user['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');

		//默认0
		$recharge['total'] = $recharge['total'] ? $recharge['total'] : 0.00;
		$cash['total'] = $cash['total'] ? $cash['total'] : 0.00;
		$recharge_user['total'] = $recharge_user['total'] ? $recharge_user['total'] : 0.00;
		$cash_user['total'] = $cash_user['total'] ? $cash_user['total'] : 0.00;

		$this->assign('recharge',$recharge);
		$this->assign('cash',$cash);
		$this->assign('recharge_user',$recharge_user);
		$this->assign('cash_user',$cash_user);
		/**************************************************** 充值、提现统计 end **************************************************************/

		$this->display();
    }

	/* 消费明细 */
	public function order(){
		$topkey = $this->visitor->get('topkey');

		/**************************************************** 订单列表 start **************************************************************/
		$map['u.topkey'] = $topkey;
		$map['u.tgroup_'.$this->user_tgroup] = $this->uid;
		$map['o.lottery'] = array('IN',array(1,2,9));

		$map['o.type'] = 0;
		($id = $this->_get('id', 'intval',0)) && $map['u.id'] = $id;
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

		$page_size = 20;
		$count = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->count();
		$pager = new Page($count,$page_size);
		if($this->_request('export') != ''){
			$list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->limit('0,1000')->where($map)->field('o.prices as prices,o.lottery_total as lottery_total,o.status as status,o.add_time as add_time,o.lottery as lottery,u.id as uid,u.username as username,u.tele as tele,o.id as oid,u.tgroup_1 as tgroup_1,u.tgroup_2 as tgroup_2,u.tgroup_3 as tgroup_3')->order('o.add_time desc')->select();
		}else{
			$list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->limit($pager->firstRow, $pager->listRows)->where($map)->field('o.prices as prices,o.lottery_total as lottery_total,o.status as status,o.add_time as add_time,o.lottery as lottery,u.id as uid,u.username as username,u.tele as tele,o.id as oid,o.orderid as orderid,u.tgroup_1 as tgroup_1,u.tgroup_2 as tgroup_2,u.tgroup_3 as tgroup_3')->order('o.add_time desc')->select();
		}
		$lottery_arr = array(0=>'待升级',1=>'升级中',2=>'升级成功',9=>'升级失败');
		foreach($list as $key=>$val){
			if(in_array($val['status'],[3,4,5,6])){
				$list[$key]['status'] = '已提货';
			}elseif(in_array($val['status'],[9])){
				$list[$key]['status'] = '退款';
			}else{
				//$list[$key]['status'] = '待提货';
				$list[$key]['status'] = '已提货';
			}
			if($val['lottery'] == 2){
				$list[$key]['prices'] = $val['lottery_total'];
			}
			$list[$key]['lottery'] = $lottery_arr[$val['lottery']];

			$item = D('order_item')->alias('oi')->join(table('item').' as i ON oi.item_id = i.id')->field('i.title as title,i.img as img,oi.nums as nums,oi.skus as skus')->where(array('oi.order_id'=>$val['oid']))->find();
			$list[$key]['title'] = $item['title'];
			$list[$key]['img'] = $item['img'];
			$list[$key]['nums'] = $item['nums'];
			$list[$key]['skus'] = $item['skus'];

			$list[$key]['tgroup_1_name'] = D('user')->where(['id'=>$val['tgroup_1']])->getField('username');
			$list[$key]['tgroup_2_name'] = D('user')->where(['id'=>$val['tgroup_2']])->getField('username');
			$list[$key]['tgroup_3_name'] = D('user')->where(['id'=>$val['tgroup_3']])->getField('username');

		}
		if($this->_request('export') != ''){
			// 引入excel类文件，将订单用excel文件导出来
			Vendor('excelClass.excelclass');
			$excel = new excelClass();
			$excel->echoUserOrderFile('订单'.date('YmdHis').'.xls',$list);
			exit;
		}
		$page = $pager->show();
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('search',array('id'=>$id,'type'=>$type,'lottery'=>$lottery,'stime'=>$map_stime,'etime'=>$map_etime,'tele'=>$tele));
		/**************************************************** 订单列表 end **************************************************************/


		/**************************************************** 统计 start **************************************************************/
		$stime = date('Y-m-d') . ' 00:00:00';
        $etime = date('Y-m-d') . ' 23:59:59';
		$order_map['u.topkey'] = $topkey;
		$order_map['u.tgroup_'.$this->user_tgroup] = $this->uid;
		$order_map['o.lottery'] = array('IN',array(1,2,9));
		$order_map['o.type'] = 0;
		($id = $this->_get('id', 'intval',0)) && $order_map['u.id'] = $id;
		($lottery = $this->_get('lottery', 'intval',0)) && $order_map['o.lottery'] = $lottery;
		($tele = $this->_get('tele', 'trim','')) && $order_map['u.tele'] = array('like','%'.$tele.'%');
		($map_stime = $this->_request('stime', 'trim')) && $order_map['o.add_time'][] = array('egt', $map_stime.' 00:00:00');
        ($map_etime = $this->_request('etime', 'trim')) && $order_map['o.add_time'][] = array('elt', $map_etime.' 23:59:59');
		($tgroup_1 = $this->_get('tgroup_1', 'intval',0)) && $order_map['u.tgroup_1'] = $tgroup_1;
		($tgroup_2 = $this->_get('tgroup_2', 'intval',0)) && $order_map['u.tgroup_2'] = $tgroup_2;
		($tgroup_3 = $this->_get('tgroup_3', 'intval',0)) && $order_map['u.tgroup_3'] = $tgroup_3;

		$order['total'] = $order['refund'] = $order['delivery'] = $order['days'] = 0.00;

		//交易总额
		$order_total_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->field('o.lottery,o.lottery_total,o.prices')->select();
		foreach($order_total_list as $tval){
			$order['total'] += ($tval['lottery'] == 2 ? $tval['lottery_total'] : $tval['prices']);
		}

		$r_map = $d_map = $order_map;

		//总退款额
		$r_map['o.status'] = 9;
		$order_refund_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($r_map)->field('o.lottery,o.lottery_total,o.prices')->select();
		foreach($order_refund_list as $rval){
			$order['refund'] += ($rval['lottery'] == 2 ? $rval['lottery_total'] : $rval['prices']);
		}

		//总提货额
		$d_map['o.status'] = array('IN',[2,3,4,5,6]);
		$order_delivery_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($d_map)->field('o.lottery,o.lottery_total,o.prices')->select();
		foreach($order_delivery_list as $dval){
			$order['delivery'] += ($dval['lottery'] == 2 ? $dval['lottery_total'] : $dval['prices']);
		}

		//今日交易额
		$order_map['o.add_time'][] =  array('elt', $etime);
		$order_map['o.add_time'][] =  array('egt', $stime);

		$order_days_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->field('o.lottery,o.lottery_total,o.prices')->select();
		foreach($order_days_list as $daval){
			$order['days'] += ($daval['lottery'] == 2 ? $daval['lottery_total'] : $daval['prices']);
		}
		
		
		$this->assign('order',$order);

		if($this->_get('profit') == 1){
			$commission_where['u.topkey'] = $topkey;
			$commission_where['u.tgroup_'.$this->user_tgroup] = $this->uid;
			$commission_where['p.action'] = 'commission';
			$commission['total'] = D('price_log')->alias('p')->join(table('user').' as u ON p.uid = u.id')->where($commission_where)->sum('p.price');
			$commission_where['p.add_time'][] =  array('elt', $etime);
			$commission_where['p.add_time'][] =  array('egt', $stime);
			$commission['days'] = D('price_log')->alias('p')->join(table('user').' as u ON p.uid = u.id')->where($commission_where)->sum('p.price');
			$commission['total'] = $commission['total'] ? $commission['total'] : 0.00;
			$commission['days'] = $commission['days'] ? $commission['days'] : 0.00;
			$this->assign('commission',$commission);
		}

		/**************************************************** 统计 end **************************************************************/

		$this->display();
	}


	/* 订单详情 */
	public function order_detail(){
		$id = $this->_get('id','intval');
		$topkey = $this->visitor->get('topkey');
		$map['o.id'] = $id;
		$map['u.topkey'] = $topkey;

		$info = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->field('o.*')->where($map)->find();
		$item = D('order_item')->alias('oi')->join(table('item').' as i ON oi.item_id = i.id')->field('i.title as title,i.img as img,i.price as price,i.title_up as title_up,i.img_up as img_up,i.price_up as price_up,oi.nums as nums,oi.skus as skus')->where(array('oi.order_id'=>$info['id']))->find();
		$info['item'] = $item;
		$lottery = array(0=>'待升级',1=>'升级中',2=>'升级成功',9=>'升级失败');
		if(in_array($info['status'],[3,4,5,6])){
			$info['status_str'] = '已提货';
		}elseif(in_array($info['status'],[9])){
			$info['status_str'] = '退款';
		}else{
			//$info['status_str'] = '待提货';
			$info['status_str'] = '已提货';
		}
		$info['lottery_str'] = $lottery[$info['lottery']];
		if($info['lottery'] == 2){
			$info['total'] = $info['lottery_total'];
		}
		$this->assign('info',$info);
		$status = 1;
		if($info['lottery'] > 0 && !in_array($info['status'],[2,3,4,5,6])){
			$status = 2;
		}elseif($info['status'] == 3 || $info['status'] == 4){
			$status = 3;
		}elseif($info['status'] == 5 || $info['status'] == 6){
			$status = 4;
		}
		$this->assign('status',$status);
		$this->display();
	}

	/* 资金明细 */
	public function price(){
		$topkey = $this->visitor->get('topkey');
		$map['u.topkey'] = $topkey;
		$map['u.tgroup_'.$this->user_tgroup] = $this->uid;

		($id = $this->_get('id', 'intval',0)) && $map['u.id'] = $id;
		($type = $this->_get('type', 'intval',0)) && $map['ur.type'] = $type;

		($tgroup_1 = $this->_get('tgroup_1', 'intval',0)) && $map['u.tgroup_1'] = $tgroup_1;
		($tgroup_2 = $this->_get('tgroup_2', 'intval',0)) && $map['u.tgroup_2'] = $tgroup_2;
		($tgroup_3 = $this->_get('tgroup_3', 'intval',0)) && $map['u.tgroup_3'] = $tgroup_3;

		$map['ur.status'] = 1;
		$count = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($map)->count();
		$page_size = 20;
		$pager = new Page($count,$page_size);
		if($this->_request('export') != ''){
			$list = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->field('ur.*,u.tele as tele,u.tgroup_1 as tgroup_1,u.tgroup_2 as tgroup_2,u.tgroup_3 as tgroup_3')->where($map)->select();
			foreach($list as $key=>$val){
				$list[$key]['type'] = $val['type'] == 1 ? '充值' : '提现';
			}
		}else{
			$list = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->field('ur.*,u.tele as tele,u.tgroup_1 as tgroup_1,u.tgroup_2 as tgroup_2,u.tgroup_3 as tgroup_3')->limit($pager->firstRow, $pager->listRows)->where($map)->select();
		}
		foreach($list as $key=>$val){
			$list[$key]['tgroup_1_name'] = D('user')->where(['id'=>$val['tgroup_1']])->getField('username');
			$list[$key]['tgroup_2_name'] = D('user')->where(['id'=>$val['tgroup_2']])->getField('username');
			$list[$key]['tgroup_3_name'] = D('user')->where(['id'=>$val['tgroup_3']])->getField('username');
		}
		$page = $pager->show();
		if($this->_request('export') != ''){
			// 引入excel类文件，将订单用excel文件导出来
			Vendor('excelClass.excelclass');
			$excel = new excelClass();
			$excel->echoUserPriceFile('资金'.date('YmdHis').'.xls',$list);
			exit;
		}
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('search',array('id'=>$id,'type'=>$type));


		/* 统计 */
		$stime = date('Y-m-d') . ' 00:00:00';
        $etime = date('Y-m-d') . ' 23:59:59';
		$recharge_map['u.topkey'] = $topkey;
		($id = $this->_get('id', 'intval',0)) && $recharge_map['u.id'] = $id;
		$recharge_map['u.tgroup_'.$this->user_tgroup] = $this->uid;

		($tgroup_1 = $this->_get('tgroup_1', 'intval',0)) && $recharge_map['u.tgroup_1'] = $tgroup_1;
		($tgroup_2 = $this->_get('tgroup_2', 'intval',0)) && $recharge_map['u.tgroup_2'] = $tgroup_2;
		($tgroup_3 = $this->_get('tgroup_3', 'intval',0)) && $recharge_map['u.tgroup_3'] = $tgroup_3;

		$recharge_map['ur.status'] = 1;
		$recharge_map['ur.type'] = 1;
		$recharge['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');
		$recharge_map['ur.add_time'][] =  array('elt', $etime);
		$recharge_map['ur.add_time'][] =  array('egt', $stime);
		$recharge['days'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');

		$recharge_map['ur.type'] = 2;
		unset($recharge_map['ur.add_time']);
		$cash['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');
		$recharge_map['ur.add_time'][] =  array('elt', $etime);
		$recharge_map['ur.add_time'][] =  array('egt', $stime);
		$cash['days'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid=u.id')->where($recharge_map)->sum('ur.price');

		$recharge['total'] = $recharge['total'] ? $recharge['total'] : 0.00;
		$recharge['days'] = $recharge['days'] ? $recharge['days'] : 0.00;
		$cash['total'] = $cash['total'] ? $cash['total'] : 0.00;
		$cash['days'] = $cash['days'] ? $cash['days'] : 0.00;

		$this->assign('recharge',$recharge);
		$this->assign('cash',$cash);

		$this->display();
	}

	/* 创建成员 */
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
			$data['topkey'] = $this->visitor->get('topkey');
			//$data['created_id'] = $this->visitor->info['id'];
			$data['invite_uid'] = $this->visitor->info['id'];
			D('user')->add($data);
			$this->ajaxReturn(1,'帐号添加成功','','add_user');
		}else{
			if($this->visitor->get('tgroup') == 1){
				$level = [2,3,4];
			}elseif($this->visitor->get('tgroup') == 2){
				$level = [3];
			}elseif($this->visitor->get('tgroup') == 3){
				$level = [4];
			}else{
				$this->redirect('user/index');
			}
			$this->assign('level', $level);
			$this->assign('tgroupid', $this->visitor->get('tgroup'));
			$this->display();
		}
	}

	/* 二维码 */
	public function erweima(){
        $code_url = U('mall/passport/binding_invite',array('invite_uid'=> $this->visitor->info['id']),true,false,true);
		$this->assign('code_url', $code_url);
		$this->assign('code', $this->visitor->info['id']);
		$count = D('user')->where(array('invite_uid'=>$this->visitor->info['id']))->count();
		$this->assign('count', $count);
		$this->assign('uname', $this->visitor->info['username']);
		
		$invite_uid = $this->visitor->get('invite_uid');
		$recommend_user = D('user')->find($invite_uid);

		$this->display();
	}

	public function erweima_img()
    {
        $code = $this->_request('code', 'trim', '');
        Vendor('qrcode.qrcode');
        $fqrcode = new fqrcode();
        $data = $fqrcode->getCode($code, 'code');
        exit;
    }

	/* 用户联动 */
	public function tgroup_child(){
		$id = $this->_get('id','intval',0);
		$topkey = $this->visitor->get('topkey');
		$this_user_tgroup = $this->visitor->get('tgroup');
		$this_user_tgroup_1 = $this->visitor->get('tgroup_1');
		$this_user_tgroup_2 = $this->visitor->get('tgroup_2');
		$this_user_tgroup_3 = $this->visitor->get('tgroup_3');
		$this_user_tgroup_4 = $this->visitor->get('tgroup_4');
		if($id == 0){
			$map['id'] = $this_user_tgroup == 1 ? $this->visitor->info['id'] : $this_user_tgroup_1;
			$next_tgroup = 1;
		}else{
			$choice_user = D('user')->field('tgroup')->find($id);
			$next_tgroup = $choice_user['tgroup'] + 1;
			$map['tgroup'] = $next_tgroup;
			$this_next_tgroup = 'this_user_tgroup_'.$next_tgroup;
			if($$this_next_tgroup > 0){//我的上级
				$map['id'] = $$this_next_tgroup;
			}elseif($next_tgroup == $this_user_tgroup){//我的
				$map['id'] = $this->visitor->info['id'];
			}elseif($next_tgroup > $this_user_tgroup && $this_user_tgroup > 0){//我的下级
				$map['tgroup_'.$this_user_tgroup] = $this->visitor->info['id'];
			}
		}

		$map['topkey'] = $topkey;
		$list = D('user')->where($map)->field('id,username as name')->select();
		if(!$list){
			$this->ajaxReturn(0,'该用户下没有'.$this->tgroup[$next_tgroup].',请先添加');
		}else{
			$this->ajaxReturn(1,'',array('list'=>$list,'top_option'=>'请选择'.$this->tgroup[$next_tgroup]));
		}
	}

	public function get_tgroup_data(){
		$select_tgroup = $this->_request('select_tgroup','intval',0);
		$tgroup = $this->visitor->get('tgroup');

		for($i=1;$i<$tgroup;$i++){
			$data['tgroup_'.$i] = $this->visitor->get('tgroup_'.$i);
		}
		$data[] = $this->visitor->info['tgroup'];
		$this->assign('select_nums', ($select_tgroup - 1));
		$this->assign('data', $data);
		$response = $this->fetch();
		$this->ajaxReturn(1, '', $response);
	}

	/* 消费树图 */
	public function order_total(){
		$id = $this->_get('id','intval');
		$user = D('user')->field('id,username,tele,img,orders,status')->find($id);
		$order_map['o.lottery'] = array('IN',array(1,2,9));
		$order_map['o.type'] = 0;
		$order_map['u.id'] = $user['id'];
		$order_total_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($order_map)->field('o.prices as prices,o.lottery_total as lottery_total,o.lottery as lottery')->select();
		$order_total = 0.00;
		foreach($order_total_list as $tval){
			$order_total += ($tval['lottery'] == 2 ? $tval['lottery_total'] : $tval['prices']);
		}
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
	
	/* 资金树图 */
	public function price_total(){
		$id = $this->_get('id','intval');
		$user = D('user')->field('id,username,tele,img,orders,status,topkey')->find($id);

		$recharge_map['u.topkey'] = $user['topkey'];
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

	/* 个人资料 */
    public function profile()
    {
        if (IS_POST) {
            $year          = $this->_post('year', 'int');
            $month         = $this->_post('month', 'int');
            $day           = $this->_post('day', 'int');
            $data['birthday']      = $year . '-' . $month . '-' . $day;
            $data['sex']   = $this->_post('sex', 'intval');
            $data['email'] = $this->_post('email', 'trim');
            $data['weixin']   = $this->_post('weixin', 'trim');
            $data['username']   = $this->_post('username', 'trim');
            if ($img = $this->_post('img', 'trim')) {
                $data['img'] = $img;
            }
            D('user')->where(['id' => $this->get_visitor_id()])->save($data);
            
            $this->ajaxResultSuccess('修改成功', ['url' => $this->_post('ret_url')]);
        }
        $info = D('user')->get($this->get_visitor_id(), 'id,username,sex,email,weixin,img,tele,pers');
        //D('yi_order')->where(['uid'=>$info['id']])->setField('uname', $info['username']);
        
        if (strpos($info['email'], "@default.com")) {
            $info['email'] = '';
        }
        $info['birthday'] = D('user')->where(array('id' => $this->get_visitor_id()))->getField('birthday');
        $explode          = explode('-', $info['birthday']);
        $info['year']     = $explode[0] ? $explode[0] : '1980';
        $info['month']    = $explode[1] ? $explode[1] : '01';
        $info['day']      = $explode[2] ? $explode[2] : '01';
        $this->assign('ret_url', $_SERVER["HTTP_REFERER"]);
        
        $this->assign('info', $info);
        $this->display();
    }

	/* 修改密码 */
    public function password()
    {
        if (IS_POST) {
            $oldpassword = $this->_post('oldpassword', 'trim');
            $password    = $this->_post('password', 'trim');
            $repassword  = $this->_post('repassword', 'trim');
            if (!$password) {
                $this->ajaxResultError('请输入新密码');
            }
            if ($password != $repassword) {
                $this->ajaxResultError('两次输入的密码不一致');
            }

            $passlen = strlen($password);
            if ($passlen < 6 || $passlen > 20) {
                $this->ajaxResultError("密码长度在6~20个之间!");
            }
            //连接用户中心
            $passport = $this->_user_server();
            $result   = $passport->edit($this->uid, $oldpassword, array('password' => $password));
            D('user')->where(['id' => $this->get_visitor_id()])->save(['salt' => '0']);

            if ($result) {
                $this->ajaxResultSuccess();
            }
            else {
                $this->ajaxResultError($passport->get_error());
            }
        }
        $this->_config_seo();
        $this->display();
    }


    public function pers(){
		$topkey = $this->visitor->get('topkey');
		$where['topkey'] = $topkey;
		$where['tgroup_'.$this->user_tgroup] = $this->uid;

		if($tgroup = $this->_get('tgroup', 'intval',0)){
			$where['tgroup'] = $tgroup;
		}else{
			$where['tgroup'] = ['gt',0];
		}
		($tele = $this->_get('tele', 'trim','')) && $where['tele'] = array('like',$tele);


		$page_size = 20;
		$count = D('user')->where($where)->count();
		$pager = new Page($count,$page_size);
		$list = D('user')->where($where)->field('pers,tgroup,username,id')->limit($pager->firstRow, $pager->listRows)->order('reg_time desc')->select();


		$page = $pager->show();
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('search',array('tgroup'=>$tgroup,'tele'=>$tele));
        $this->display();
	}

    public function set_pers(){
		$id = $this->_post('id', 'intval');
		$pers = $this->_post('pers', 'trim');
		if(ceil($pers)!=$pers){
			$this->ajaxResultSuccess('收益比例不能为小数',array('error'=>1));
		}
		$user_info = D('user')->field('tgroup,tgroup_1,tgroup_2,tgroup_3,tgroup_4')->find($id);
		if($user_info['tgroup'] > 1){
			$tgroup_top_user_id = $user_info['tgroup_'.($user_info['tgroup']-1)];
			$tgroup_top_user = D('user')->field('pers')->find($tgroup_top_user_id);
			if($pers > $tgroup_top_user['pers']){
				$this->ajaxResultSuccess('收益比例不能大于上级的收益比例<br>该帐号上级收益比例为'.$tgroup_top_user['pers'].'%',array('error'=>1));
			}
		}
		D('user')->where(['id'=>$id,'tgroup_'.$this->user_tgroup=>$this->uid])->save(array('pers'=>$pers));
		$this->ajaxResultSuccess('');
	}

	/**
	 * 获取某月月头/月底时间戳
	 * $m 本月0,上月-1,上2月-2
	 * $t false月头,true月底
	 */

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

		$topkey = $this->visitor->get('topkey');

		$map['u.topkey'] = $topkey;
		$map['u.tgroup_'.$this->user_tgroup] = $this->uid;
		$map['o.lottery'] = array('IN',array(1,2,9));
		$map['o.type'] = 0;
		$map['o.add_time'][] = array('egt',$start_time);
		$map['o.add_time'][] = array('elt',$end_time);

		($tgroup_1 = $this->_get('tgroup_1', 'intval',0)) && $map['u.tgroup_1'] = $tgroup_1;
		($tgroup_2 = $this->_get('tgroup_2', 'intval',0)) && $map['u.tgroup_2'] = $tgroup_2;
		($tgroup_3 = $this->_get('tgroup_3', 'intval',0)) && $map['u.tgroup_3'] = $tgroup_3;

		//我的收益比例
		$this_user_pers = $this->visitor->get('pers');

		$page_size = 20;
		$count = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->count();
		$pager = new Page($count,$page_size);
		$list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->limit($pager->firstRow, $pager->listRows)->where($map)->field('o.prices as prices,o.status as status,o.add_time as add_time,o.lottery as lottery,o.lottery_total as lottery_total,u.id as uid,u.username as username,u.tele as tele,o.id as oid,o.orderid as orderid,u.tgroup_1 as tgroup_1,u.tgroup_2 as tgroup_2,u.tgroup_3 as tgroup_3,u.invite_uid as invite_uid')->order('o.add_time desc')->select();
		$lottery_arr = array(0=>'待升级',1=>'升级中',2=>'升级成功',9=>'升级失败');
		foreach($list as $key=>$val){
			if(in_array($val['status'],[3,4,5,6])){
				$list[$key]['status'] = '已提货';
			}elseif(in_array($val['status'],[9])){
				$list[$key]['status'] = '退款';
			}else{
				//$list[$key]['status'] = '待提货';
				$list[$key]['status'] = '已提货';
			}
			$list[$key]['lottery'] = $lottery_arr[$val['lottery']];
			$list[$key]['tgroup_1_name'] = D('user')->where(['id'=>$val['tgroup_1']])->getField('username');
			$list[$key]['tgroup_2_name'] = D('user')->where(['id'=>$val['tgroup_2']])->getField('username');
			$list[$key]['tgroup_3_name'] = D('user')->where(['id'=>$val['tgroup_3']])->getField('username');
			//$pers = D('user')->where(array('id'=>$val['invite_uid']))->getField('pers');
			$prices = $val['lottery'] == 2 ? $val['lottery_total'] : $val['prices'];
			$pers_price = $prices*($this_user_pers*0.01);
			$list[$key]['pers_price'] = $pers_price;
			//$list[$key]['invite_uname'] = D('user')->where(['id'=>$val['invite_uid']])->getField('username');
		}
		$page = $pager->show();

		//收益总额
		$total_prices = 0;
		$total_prices_list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($map)->select();
		foreach($total_prices_list as $tval){
			$total_prices += ($tval['lottery'] == 2 ? $tval['lottery_total'] : $tval['prices']);
		}
		$total = $total_prices*($this_user_pers*0.01);
		$this->assign(compact('list','total','page'));
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