<?php
include_once './app/Lib/Inslib/QrCode/src/QrCode.php';
use Endroid\QrCode\QrCode;

class userAction extends mbaseAction
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
    }

    public function index()
    {
        $fields = 'id,username,img,score,price,agent_province,agent_city,agent_area,level_id,fans,invite_u1,agent_type,score_agent,score_days,topclass,tgroup';
        $user   = D('user')->get($this->get_visitor_id(), $fields);

        $this->assign(compact('user'));
        $this->show_footer();
        $this->display();
    }

    public function other_settings()
    {
        $user           = D('user')->get($this->get_visitor_id(), 'email,weixin');
        $profile_status = true;
        if ($user['email'] == '' || $user['weixin'] == '' || strpos($user['email'], "@default.com")) {
            $profile_status = false;
        }
        $this->assign(compact('alipay_status', 'auth_status', 'profile_status'));
        $this->display();
    }

    public function aboutus()
    {
        $this->display();
    }

    protected function _order_search()
    {
        $status  = $this->_get('status', 'intval', -1);
        $lottery  = $this->_get('lottery', 'intval', -1);

        $orderid = $this->_post('orderid');
        $where   = array('uid' => $this->uid,'type'=>0);
        if ($status >= 0) {
            $where['status'] = $status;
        }
		if ($lottery >= 0) {
            $where['lottery'] = $lottery;
        }
        if (in_array($status, [5, 6])) {
            $where['status'] = ['in', [5, 6, 9]];
        }
		if (in_array($status, [4, 3])) {
            $where['status'] = ['in', [4, 3]];
        }
        if ($orderid) {
            $where['orderid'] = array('like', "%" . $orderid . "%");
        }
        $this->assign('search', array(
            'status'  => $status,
            'orderid' => $orderid,
            'lottery' => $lottery,
        ));
        return $where;
    }

    //我的订单
    public function order()
    {
        $where = $this->_order_search();
        $count = D('order')->where($where)->count();
        $pager = $this->_pager($count);

        $list = D('order')->where($where)->limit($pager->firstRow, $pager->listRows)->relation(true)->select();
        foreach ($list as $key => $val) {
            $list[$key]['can_receive'] = time() > (strtotime($val['express_time']) + $val['express_recv_time'] * 24 * 3600);
			if($val['lottery'] == 2){
				$prices = 0;
				foreach ($val['order_item_list'] as $item_key => $item_val) {
					$item = D('item')->field('title_up,price_up,img_up')->find($item_val['item_id']);
					$list[$key]['order_item_list'][$item_key]['title'] = $item['title_up'];
					$list[$key]['order_item_list'][$item_key]['price'] = $item['price_up'];
					$list[$key]['order_item_list'][$item_key]['img'] = $item['img_up'];
					$prices += $item['price_up']*$item_val['nums'];
				}
				$list[$key]['prices'] = sprintf("%.2f", $prices);
			}else{
				foreach ($val['order_item_list'] as $item_key => $item_val) {
					$item = D('item')->field('img')->find($item_val['item_id']);
					$list[$key]['order_item_list'][$item_key]['img'] = $item['img'];
				}
			}

            /*foreach ($list[$key]['order_item_list'] as $k => $v) {
                $list[$key]['order_item_list'][$k]['type'] = D('item')->where(['id' => $v['item_id']])->getField('type');
            }*/

			/* 判断是否需要支付提货邮费 */
			$express = C('ins_express');
			if($express > 0 && $val['express'] <= 0){
				$list[$key]['is_express'] = 1;
			}
        }

        $this->assign('list', $list);
        if (count($list) == $pager->listRows) {
            $this->assign('show_load', 1);
        }
        if (isset($_GET['status'])) {
            $this->assign('status', $this->_get('status'));
            $this->assign('lottery', $this->_get('lottery'));
        }
        else {
            $this->assign('status', -1);
        }

        if (IS_AJAX) {
            $resp = $this->fetch('order_waterfall');
            $data = array(
                'isfull' => count($list) == $pager->listRows,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }
        else {
            $this->display();
        }
    }

    public function order_info()
    {
        $id           = $this->_get('id', 'intval');
        $info         = D('order')->find($id);
        $info['nums'] = D('order_item')->where(array('order_id' => $id))->sum('nums');
        $item_list    = D('order_item')->where(array('order_id' => $id))->select();
		/* 升级成功显示升级成功的商品信息 */
		if($info['lottery'] == 2){
			$prices = 0;
			foreach ($item_list as $key => $val) {
				$item = D('item')->field('title_up,price_up,img_up')->find($val['item_id']);
				$item_list[$key]['title'] = $item['title_up'];
				$item_list[$key]['price'] = $item['price_up'];
				$item_list[$key]['img'] = $item['img_up'];
				$prices += $item['price_up']*$val['nums'];
			}
			$info['prices'] = sprintf("%.2f", $prices);
		}else{
			foreach ($item_list as $key => $val) {
				$item = D('item')->field('img')->find($val['item_id']);
				$item_list[$key]['img'] = $item['img'];
			}
		}
        if ($info['express_time']) {
            $can_order_receive = time() >= (strtotime($info['express_time']) + $info['express_recv_time'] * 24 * 3600);
        }
        else {
            $can_order_receive = false;
        }

		/* 判断是否需要支付提货邮费 */
		$express = C('ins_express');
		if($express > 0 && $info['express'] <= 0){
			$info['is_express'] = 1;
		}

        $this->assign(compact('info', 'item_list', 'can_order_receive'));
        $this->display();
    }

    /**
     * 用户绑定
     */
    public function binding()
    {
        $user_bind_info = object_to_array(cookie('user_bind_info'));
        $this->assign('user_bind_info', $user_bind_info);
        $this->_config_seo();
        $this->display();
    }

    public function express()
    {
        $id    = $this->_request('id', 'intval', 0);
        $order = D('order')->field('express_name,express_time,express_code,express_sn')->find($id);

        $params = array('id'=>C('WULIU_KEY'),'com'=>$order['express_code'],'nu'=>$order['express_sn']);

        $express = Http::get('http://api.kuaidi.com/openapi.html', $params);

        $express = json_decode($express, true);

        if ($express['success']) {
            $list = $express['data'];//JSON_UNESCAPED_UNICODE
            $this->assign('list', $list);
            $this->assign('info', $order);
            $response = $this->fetch();
            echo $response;
            exit;
        }
        else {
            $response = $this->fetch();
            echo $response;
            exit;
        }
    }

    public function orders_express()
    {
        $oid   = $this->_post('oid', 'intval', 0);
        $order = D('order')->where(array('id' => $oid))->find();
        if ($order['status'] == 0) {
            $this->ajaxResultError('商品状态错误');
        }
        if ($order['express_name'] == '') {
            $this->ajaxResultError('没有物流信息');
        }
        $this->ajaxResult(array('name' => $order['express_name'], 'sn' => $order['express_sn']));
    }

    public function orders_item_express()
    {
        $id         = $this->_post('id', 'intval', 0);
        $order_item = D('order_item')->where(array('id' => $id))->find();
        if ($order_item['is_express'] == 0) {
            $this->ajaxResultError('商品状态错误');
        }
        if ($order_item['express_name'] == '') {
            $this->ajaxResultError('没有物流信息');
        }
        $this->ajaxResult(array('name' => $order_item['express_name'], 'sn' => $order_item['express_sn']));
    }

    /*
     * 订单确认收货
     * */
    public function order_confirm_receive()
    {
        $id    = $this->_post('id', 'intval', 0);
        $where = [
            'id'  => $id,
            'uid' => $this->get_visitor_id(),
        ];
        D('order')->where($where)->save([
            'status'     => 5,
            'check_time' => current_date(),
            'check_ip'   => $_SERVER['REMOTE_ADDR'],
            'check_uid'  => $this->get_visitor_id(),
        ]);
        /*$type = D('order')->where($where)->getField('type');
        if ($type == 1) {
            $service = new OrderService(['order_id' => $id]);
            $service->run();
        }*/
        $this->ajaxResultSuccess();
    }

    public function ajax_refund_order()
    {
        $id    = $this->_request('id');
        $order = D('order')->find($id);
        if ($order['status'] > 2) {
            $this->ajaxResultError('未发货的商品才能退款');
        }
		if ($order['lottery'] == 9 || $order['lottery'] ==1) {
            $this->ajaxResultError('升级失败订单与升级中的订单无法退款');
        }
		//修正中奖后退款差价
		$r_price = $order['lottery'] == 2 ? $order['lottery_total'] : $order['total'];
		//退款手续费
		$order_refund_fee = C('order_refund_fee') > 0 ? round($r_price * C('order_refund_fee'), 2) : 0;
		//退款金额
		$refund_price = $r_price - $order_refund_fee;
        if ($this->_get('act') == 'tip') {
            $tips = [];
            if ($order['pays_price'] > 0) {
                $tips[] = "退款金额：￥" . $refund_price;
                ($order_refund_fee > 0) && $tips[] = "手续费：￥" . $order_refund_fee;
            }
            $tips[] = "金额返还到余额中，确认要退款吗?";
            $tip    = implode("<br>", $tips);
            $this->ajaxResult(compact('tip'));
        }
		//增加退款记录
		$order_refund_id = D('order_refund')->add(array(
			'orderid'  => $order['orderid'],
			'order_id' => $order['id'],
			'order_price' => $refund_price,
			'refund_price' => $refund_price,
			'fee'      => $order_refund_fee,
			'add_time' => current_date(),
			'uid'      => $this->get_visitor_id(),
			'uname'    => $this->visitor->info['username'],
			'status'   => 2,
		));
		//修改用户余额
		D('user')->where(array('id'=>$this->get_visitor_id()))->setInc('price',$refund_price);
		D('price_log')->add(array(
			'uid' => $this->get_visitor_id(),
			'uname' => $this->visitor->info['username'],
			'price' => $refund_price,
			'action' => 'order_refund',
			'add_time' => date('Y-m-d H:i:s'),
			'remark' => '订单'.$order['orderid'].'退款',
			'key_id' => $order_refund_id,
		));
		//修改订单状态
		$order_status = 9;
		$resp_tip     = '退款金额已返还至您的余额';
        $order_where = [
            'id'  => $order['id'],
            'uid' => $this->get_visitor_id(),
        ];
        D('order')->where($order_where)->save([
            'status' => $order_status,
        ]);
        $this->ajaxResult([
            'tip' => $resp_tip,
        ]);
    }
/*
	public function ajax_refund_order()
    {
        $id    = $this->_request('id');
        $order = D('order')->find($id);
        if ($order['status'] > 2) {
            $this->ajaxResultError('未发货的商品才能退款');
        }
		if ($order['lottery'] == 9 || $order['lottery'] ==1) {
            $this->ajaxResultError('升级失败订单与升级中的订单无法退款');
        }
		//需要退款金额修正
		$r_price = $order['lottery']==2 ? $order['lottery_total'] : $order['order_total'];

        if ($this->_get('act') == 'tip') {
            $tips = [];
            if ($order['score'] > 0) {
                $tips[] = "积分：" . intval($order['score']);
            }
            if ($order['pays_price'] > 0) {
                $tips[] = "退款金额：￥" . round($r_price - $r_price * C('order_refund_fee'), 2);
                (C('order_refund_fee') > 0) && $tips[] = "手续费：￥" . round($r_price * C('order_refund_fee'), 2);
            }
            $tips[] = "退款申请成功后金额返还到余额中，<br>您确认要退款吗?";
            $tip    = implode("<br>", $tips);
            $this->ajaxResult(compact('tip'));
        }
		
		$order_refund_id = D('order_refund')->add(array(
			'orderid'  => $order['orderid'],
			'order_id' => $order['id'],
			'order_price' => round($r_price - $r_price * C('order_refund_fee'), 2),
			'refund_price' => round($r_price - $r_price * C('order_refund_fee'), 2),
			'fee'      => round($r_price * C('order_refund_fee'), 2),
			'add_time' => current_date(),
			'uid'      => $this->get_visitor_id(),
			'uname'    => $this->visitor->info['username'],
			'status'   => 1,
		));

		$order_status = 7;
		$resp_tip     = '退款申请已提交.';

		$order_where = [
            'id'  => $order['id'],
            'uid' => $this->get_visitor_id(),
        ];
        D('order')->where($order_where)->save([
            'status' => $order_status,
        ]);
        $this->ajaxResult([
            'tip' => $resp_tip,
        ]);
    }
*/
    /*
     * 退款
     */
    public function order_refund()
    {
        $id    = $this->_post('id', 'intval', 0);
        $where = [
            'id'  => $id,
            'uid' => $this->get_visitor_id(),
        ];
        $order = D('order')->where($where)->find();
        D('order')->where($where)->save([
            'status' => 9,
        ]);

        D('score_logs')->add([
            'uid'           => $this->get_visitor_id(),
            'type'          => 6,
            'order_id'      => $order['id'],
            'order_orderid' => $order['orderid'],
            'order_prices'  => $order['prices'],
            'score'         => $order['score'],
        ]);
        $this->ajaxResultSuccess();
    }

    /**
     * 订单评论
     */
    public function order_comment()
    {
        $order_id = $this->_request('order_id');
        $where    = [
            'id'  => $order_id,
            'uid' => $this->get_visitor_id(),
        ];
        $order    = D('order')->where($where)->find();
        if (empty($order)) {
            $this->ajaxResultError('非法访问');
        }
        if ($order['status'] == 6) {
            $this->ajaxResultError('已经评价过了!');
        }
        if ($order['status'] != 5) {
            $this->ajaxResultError('现在还不能评价!');
        }

        if (IS_POST) {
            D('order')->where($where)->save([
                'status' => 6,
            ]);

            $order_item       = D('order_item')->where(['order_id' => $order['id']])->find();
            $comment_img_list = $this->_post('comment_img');

            $comment_id = D('item_comment')->add([
                'item_id' => $order_item['item_id'],
                'info'    => $this->_post('info'),
                'skus'    => $order_item['skus'],
                'uid'     => $this->get_visitor_id(),
                'has_img' => count($comment_img_list) > 0 ? 1 : 0,
            ]);
            $data       = [];
            foreach ($comment_img_list as $key => $val) {
                $data[] = [
                    'item_id'    => $order_item['item_id'],
                    'comment_id' => $comment_id,
                    'img'        => $val,
                ];
            }
            D('item_comment_img')->addAll($data);
            $this->ajaxResultSuccess();
        }
        else {
            $this->display();
        }
    }

    public function feedback()
    {
        if (IS_POST) {
            $data['uid']  = $this->get_visitor_id();
            $data['info'] = $this->_post('info', 'trim');
            D('feedback')->add($data);
            $this->ajaxResultSuccess();
        }
        else {
            $this->display();
        }
    }

    /**
     * 特卖提醒列表
     */
    public function sales_remind()
    {
        $type = $this->_get('type', 'trim', 'topic');

        if (!empty($this->visitor->info['remind']) && $type == 'topic') {
            $list = D('topic')->where(array('id' => array('in', $this->visitor->info['remind'])))->select();
        }
        if (!empty($this->visitor->info['remind_item']) && $type == 'item') {
            $list = D('item')->where(array('id' => array('in', $this->visitor->info['remind_item'])))->select();
            foreach ($list as $key => $val) {
                $info = D('user_remind')->where(array('uid' => $this->visitor->info['id'], 'item_id' => $val['id']))->find();
                if ($info['stime'] > 0) {
                    $list[$key]['stime'] = $info['stime'];
                }
                if ($info['etime'] > 0) {
                    $list[$key]['etime'] = $info['etime'];
                }
                if ($info['item_topic_id'] > 0) {
                    $list[$key]['topic_id'] = $info['item_topic_id'];
                }
            }
        }
        $this->assign('list', $list);
        $this->assign('type', $type);
        $this->display();
    }

    /**
     * 修改密码
     */
    public function edit_password()
    {
        if (IS_POST) {
            $oldpassword = $this->_post('oldpassword', 'trim');
            $password    = $this->_post('password', 'trim');
            $repassword  = $this->_post('repassword', 'trim');
            if (!$password) {
                $this->ajaxResultError(L('no_new_password'));
            }
            if ($password != $repassword) {
                $this->ajaxResultError(L('inconsistent_password'));
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

    //我的资料
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
        $info = D('user')->get($this->get_visitor_id(), 'id,username,sex,email,weixin,img,tele');
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

    /**
     * 收货地址
     */
    public function address()
    {
        $user_address_mod = M('user_address');
        $id               = $this->_request('id', 'intval');
        $type             = $this->_request('type', 'trim', 'edit');
        if ($id) {
            if ($type == 'del') {
                $user_address_mod->where(array('id' => $id, 'uid' => $this->uid))->delete();
                $msg = array('status' => 1, 'info' => L('delete_success'));
                $this->assign('msg', $msg);
                IS_AJAX && $this->ajaxReturn('', '', 1);
                if (!empty($from)) {
                    redirect(U(MODULE_NAME . '/' . ACTION_NAME, compact('from')));
                }
            }
            elseif ($type == 'edit') {
                $info = $user_address_mod->find($id);
                $this->assign('info', $info);
            }
            elseif ($type == 'use') {
                $user_address_mod->where(array('uid' => $this->uid))->save(array('is_default' => 0));
                $user_address_mod->where(array('id' => $id, 'uid' => $this->uid))->save(array(
                    'is_default' => 1,
                ));
                $msg = array('status' => 1, 'info' => '设置成功');
                $this->assign('msg', $msg);
                if (!empty($from)) {
                    redirect($from);
                }
            }
            $this->assign('info', $info);

        }
        $address_list = $user_address_mod
            ->where(['uid' => $this->visitor->info['id']])
            ->limit(10)
            ->select();

        $this->assign('address_list', $address_list);
        $this->_config_seo();
        $this->display();
    }

    /**
     * 修改收货地址
     */
    public function add_address()
    {
        $this->assign('crumb_title', '修改收货地址');
        $user_address_mod = M('user_address');
        $id               = $this->_get('id', 'intval');
        $is_default       = $this->_get('is_default', 'intval');
        $this->assign('page_type', $this->_get('page_type', 'intval'));
        if ($id) {
            $info = $user_address_mod->where(array('id' => $id))->find();
            $this->assign('info', $info);
        }
        $drr0_list = D('city')->where(array('pid' => 0, 'status' => 1))->order('ordid asc, id asc')->select();
        $this->assign('drr0_list', $drr0_list);
        $this->display();
    }

    /**
     * 修改收货地址
     */
    public function edit_address()
    {
        $user_address_mod = M('user_address');
        $id               = $this->_get('id', 'intval');
        $is_default       = $this->_get('is_default', 'intval');
        $info             = $user_address_mod->where(array('id' => $id))->find();
        $province         = str_replace('省', '', $info['province']);
        $province         = str_replace('市', '', $province);
        $address[0]       = D('city')->field('id,name')->where('name like "%' . $province . '%" AND pid = 0')->find();
        $address[1]       = D('city')->field('id,name')->where('name like "%' . str_replace('市', '', $info['city']) . '%" AND pid = "' . $address[0]['id'] . '"')->find();
        $address[2]       = D('city')->field('id,name')->where('name like "%' . str_replace('区', '', $info['area']) . '%" AND pid = "' . $address[1]['id'] . '"')->find();
        $drr0_list        = D('city')->where(array('pid' => 0, 'status' => 1))->select();
        $drr1_list        = D('city')->where(array('pid' => $address[0]['id']))->select();
        $drr2_list        = D('city')->where(array('pid' => $address[1]['id']))->select();
        $this->assign('drr0_list', $drr0_list);
        $this->assign('drr1_list', $drr1_list);
        $this->assign('drr2_list', $drr2_list);
        $this->assign('info', $info);
        $this->assign('address', $address);
        $this->display('add_address');
    }

    public function ajax_save_address()
    {
        $id               = $this->_post('id', 'intval');
        $data['name']     = $this->_post('name', 'trim');
        $data['tele']     = $this->_post('tele', 'trim');
        $data['zipcode']  = $this->_post('zipcode', 'trim');
        $data['province'] = $this->_post('province', 'trim');
        $data['city']     = $this->_post('city', 'trim');
        $data['area']     = $this->_post('area', 'trim');
        $data['address']  = $this->_post('address', 'trim');
        $data['province'] = $data['province'] == '省份' ? '' : $data['province'];
        $data['city']     = $data['city'] == '地级市' ? '' : $data['city'];
        $data['area']     = $data['area'] == '市、县级市' ? '' : $data['area'];

        foreach (['name', 'tele', 'province', 'city', 'area', 'address', 'zipcode'] as $val) {
            if (empty($data[$val])) {
                $this->ajaxResultError('地址信息不全!');
            }
        }

        if ($id > 0) {
            D('user_address')->where(array('id' => $id))->save($data);
        }
        else {
            $data['uid']   = $this->visitor->info['id'];
            $data['uname'] = $this->visitor->info['username'];
            $id            = D('user_address')->add($data);
            if (!$id) {
                $this->ajaxResultError();
            }
        }

        $this->ajaxResultSuccess('修改地址成功', ['address_id' => $id, 'url' => $this->_post('ret_url')]);
    }

    /**
     * 删除收货地址
     */
    public function delete_address()
    {
        $id = $this->_post('id', 'intval');
        if ($id <= 0) {
            $this->ajaxResultError();
        }
        $where = [
            'uid' => $this->get_visitor_id(),
            'id'  => $id,
        ];
        D('user_address')->where($where)->delete();
        $this->ajaxResultSuccess();
    }

    /**
     * 设为默认地址 ajax
     */
    public function is_default_address()
    {
        $user_address_mod = M('user_address');
        $id               = $this->_get('id', 'intval');
        $user_address_mod->where(array('uid' => $this->uid))->save(array('is_default' => 0));
        $user_address_mod->where(array('id' => $id, 'uid' => $this->uid))->save(array('is_default' => 1));
        $this->ajaxResultSuccess('修改默认地址成功');
    }

    /**
     * 第三方头像保存
     */
    private function _save_avatar($uid, $img)
    {
        //获取后台头像规格设置
        $avatar_size = explode(',', C('ins_avatar_size'));
        //会员头像保存文件夹
        $avatar_dir = C('ins_attach_path') . 'avatar/' . avatar_dir($uid);
        !is_dir($avatar_dir) && mkdir($avatar_dir, 0777, true);
        //生成缩略图
        $img = C('ins_attach_path') . 'avatar/temp/' . $img;
        foreach ($avatar_size as $size) {
            Image::thumb($img, $avatar_dir . md5($uid) . '_' . $size . '.jpg', '', $size, $size, true);
        }
        @unlink($img);
    }

    /**
     * 用户消息提示
     */
    public function msgtip()
    {
        $result = D('user_msgtip')->get_list($this->visitor->info['id']);
        $this->ajaxResultSuccess();
    }


    /**
     * 修改头像
     */
    public function upload_avatar()
    {

        if (!empty($_FILES['avatar']['name'])) {
            //会员头像规格
            $avatar_size = explode(',', C('ins_avatar_size'));
            //回去会员头像保存文件夹
            $uid        = abs(intval($this->visitor->info['id']));
            $suid       = sprintf("%09d", $uid);
            $dir1       = substr($suid, 0, 3);
            $dir2       = substr($suid, 3, 2);
            $dir3       = substr($suid, 5, 2);
            $avatar_dir = $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
            //上传头像
            $suffix = '';
            foreach ($avatar_size as $size) {
                $suffix .= '_' . $size . ',';
            }
            $result = $this->_upload($_FILES['avatar'], 'avatar/' . $avatar_dir, array(
                'width'         => C('ins_avatar_size'),
                'height'        => C('ins_avatar_size'),
                'remove_origin' => true,
                'suffix'        => trim($suffix, ','),
                'ext'           => 'jpg',
            ), md5($uid));
            if ($result['error']) {
                $this->ajaxReturn(0, $result['info']);
            }
            else {
                //.date('Y').date('m').date('d').'_'
                $data = __ROOT__ . '/data/upload/avatar/' . $avatar_dir . md5($uid) . '_' . $size . '.jpg?' . time();
                D('user')->where(array("id" => $this->visitor->info['id']))->save(array('img' => $avatar_dir . md5($uid)));
                $this->ajaxResultSuccess();
            }
        }
        else {
            $this->ajaxResultSuccess();
        }
    }

    /**
     * 身份证验证
     */
    public function idcard()
    {
        $info = D('user')->field('idcard,card_name')->where(array('id' => $this->visitor->info['id']))->find();
        if (IS_POST) {
            $card_name = $this->_post('card_name', 'trim');
            $idcard    = $this->_post('idcard', 'trim');
            D('user')->where(array("id" => $this->visitor->info['id']))->save(array('idcard' => $idcard, 'card_name' => $card_name));
            IS_AJAX && $this->ajaxReturn('', '', 1);
            $this->success('操作成功 ');
        }
        else {
            $this->assign('page_type', $this->_get('page_type'));
            $this->assign('info', $info);
            $this->display();
        }
    }


    /**
     * 帐号绑定
     */
    public function bind()
    {
        //获取已经绑定列表
        $bind_list = M('user_bind')->field('type')->where(array('uid' => $this->uid))->select();
        $binds     = array();
        if ($bind_list) {
            foreach ($bind_list as $val) {
                $binds[] = $val['type'];
            }
        }

        //获取网站支持列表
        $oauth_list = $this->oauth_list;
        foreach ($oauth_list as $type => $_oauth) {
            $oauth_list[$type]['isbind'] = '0';
            if (in_array($type, $binds)) {
                $oauth_list[$type]['isbind'] = '1';
            }
        }
        $this->assign('oauth_list', $oauth_list);
        $this->_config_seo();
        $this->display();
    }


    /**
     * 检测用户
     */
    public function ajax_check()
    {
        $type     = $this->_get('type', 'trim', 'email');
        $user_mod = D('user');
        switch ($type) {
            case 'email':
                $email = $this->_get('J_email', 'trim');
                $user_mod->email_exists($email) ? $this->ajaxReturn(0) : $this->ajaxReturn(1);
                break;

            case 'username':
                $username = $this->_get('J_username', 'trim');
                $user_mod->name_exists($username) ? $this->ajaxReturn(0) : $this->ajaxReturn(1);
                break;
        }
    }

    /**
     * 取消关注
     */
    public function unfollow()
    {
        $mod = D('item_favs');
        $pk  = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
            if (false !== $mod->where(array('uid' => $this->uid, 'item_id' => array('in', $ids)))->delete()) {
                $this->ajaxResultSuccess('取消关注成功');
            }
            else {
                IS_AJAX && $this->ajaxResultError('取消关注失败');
                $this->error('取消关注失败', U('user/follow_list'));
            }
        }
        else {
            IS_AJAX && $this->ajaxResultError(L('illegal_parameters'));
            $this->error(L('illegal_parameters'), U('user/follow_list'));
        }
    }

    public function receipt()
    {
        $id    = $this->_post('oid', 'intval', 0);
        $order = D('order')->field(array('id,status'))->where(array('id' => $id))->find();
        if ($order['status'] != 0) {
            D('order')->where(array('id' => $id))->save(array('status' => 3));
            $this->ajaxResultSuccess();
        }
        else {
            $this->ajaxResultError('订单状态不正确');
        }
    }

    public function favs()
    {
        $type = $this->_get('type', 'trim', 'item');
        if ($type == 'item') {
            $where = [
                'uid' => $this->get_visitor_id(),
            ];

            $count = D('item_favs')->where($where)->count();
            $pager = $this->_pager($count);

            $res  = D('item_favs')->where($where)->limit($pager->firstRow, $pager->listRows)->select();
            $list = [];
            foreach ($res as $key => $val) {
                $list[] = D('item')->where(['id' => $val['item_id']])->find();
            }
        }
        else {
            $where = [
                'uid' => $this->get_visitor_id(),
            ];

            $count = D('member_favs')->where($where)->count();
            $pager = $this->_pager($count);

            $res  = D('member_favs')->where($where)->limit($pager->firstRow, $pager->listRows)->select();
            $list = [];
            foreach ($res as $key => $val) {
                $member              = D('member')->where(['id' => $val['mid']])->find();
                $where               = [
                    'mid'    => $member['id'],
                    'status' => 1,
                ];
                $member['item_list'] = D('item')->field('id,title,img,price,official_price')->where($where)->limit(10)->select();
                $list[]              = $member;
            }
        }
        $this->assign(compact('list', 'type'));

        if (IS_AJAX) {
            $data = [
                'is_full' => count($list) == $pager->listRows,
                'html'    => $this->fetch('favs_waterfall'),
            ];
            $this->ajaxResult($data);
        }
        else {
            $this->assign('show_loading', count($list) == $pager->listRows);
            $this->display();
        }
    }

    public function kan()
    {
        $where = [
            'uid' => $this->get_visitor_id(),
        ];

        $count = D('kan_logs')->where($where)->count();
        $pager = $this->_pager($count);

        $list= D('kan_logs')->where($where)->limit($pager->firstRow, $pager->listRows)->select();
        
        $kan_mod = D('kan');
        $kan_user_mod = D('kan_user');
        foreach ($list as $key => $val) {
            $kan_info = $kan_mod->where(array('id'=>$val['kan_id']))->find();
            $list[$key]['kan_title'] = $kan_info['title'];
            $list[$key]['kan_sum_price'] = $kan_user_mod->where(['kan_id'=>$val['kan_id'],'logs_id'=>$val['id']])->sum('price');
            $list[$key]['kan_count'] = $kan_user_mod->where(['kan_id'=>$val['kan_id'],'logs_id'=>$val['id']])->count();
            $list[$key]['kan_price'] = $kan_info['mprice'] - $list[$key]['kan_sum_price'];
        }
        $this->assign(compact('list'));

        if (IS_AJAX) {
            $data = [
                'is_full' => count($list) == $pager->listRows,
                'html'    => $this->fetch('kan_waterfall'),
            ];
            $this->ajaxResult($data);
        }
        else {
            $this->assign('show_loading', count($list) == $pager->listRows);
            $this->display();
        }
    }

    public function remove_collect()
    {
        $id = $this->_post('id', 'intval');
        D('item_collect')->delete($id);
        $this->ajaxResultSuccess();
    }

    public function take_item()
    {
        $id   = $this->_post('oid', 'intval', 0);
        $item = D('order_item')->field(array('id,status,is_express'))->where(array('id' => $id))->find();
        if ($item['is_express'] == 1) {
            D('order_item')->where(array('id' => $id, 'uid' => $this->uid))->save(array('is_take' => 1, 'take_time' => date('Y-m-d H:i:s')));
            $this->ajaxResultSuccess('已确认收货');
        }
        else {
            $this->ajaxResultError('还未发货');
        }
    }

    public function addressajax()
    {
        $id    = $this->_post('id', 'intval', 0);
        $where = [
            'pid'    => $id,
            'status' => 1,
        ];
        $list  = D('city')->where($where)->select();
        $this->ajaxResult(compact('list'));
    }

    public function address_set_default()
    {
        $id = $this->_post('id', 'intval', 0);
        D('user_address')->where(['uid' => $this->get_visitor_id(), 'id' => ['neq', $id]])->save(['is_default' => 0]);
        D('user_address')->where(['uid' => $this->get_visitor_id(), 'id' => $id])->save(['is_default' => 1]);
        $this->ajaxResultSuccess();
    }

    /*
     * 我的评论
     * */
    public function comment_list()
    {
        if (IS_AJAX) {
            $where = [
                'uid'    => $this->get_visitor_id(),
                'status' => 1,
            ];
            if ($this->_get('type') == 'img') {
                $where['has_img'] = ['gt', 0];
            }

            $count  = D('item_comment')->where($where)->count();
            $pager  = $this->_pager($count);
            $list   = D('item_comment')->where($where)
                ->limit($pager->firstRow, $pager->listRows)
                ->relation(true)
                ->select();
            $isfull = count($list) == $pager->listRows;
            $this->ajaxResult(compact('list', 'isfull'));
        }
        else {
            $comment_has_img_count = D('item_comment')->where(['uid' => $this->get_visitor_id(), 'has_img' => ['gt', 0]])->count();
            $this->assign(compact('comment_has_img_count'));
            $this->display();
        }
    }

    public function invite_qrcode()
    {
        $act = $this->_get('act', 'trim');
        if ($act == 'img') {
            $invite_code = $this->get_invite_code($this->get_visitor_id());
            $url         = full_url('mall/passport/register');
            $qrCode      = new QrCode();

            $qrCodeSize = 320;
            $qrCode
                ->setText($url)
                ->setSize($qrCodeSize)
                ->setPadding(10)
                ->setErrorCorrection('high')
                ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
                ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0])
                ->setImageType(QrCode::IMAGE_TYPE_PNG);

            $qrCode_img      = $qrCode->getImage();
            $qrCode_img_file = './app/Tpl/mall/public/images/qrcode_bg.png';
            $qrCode_img_info = getimagesize($qrCode_img_file);

            $qrCode_bg = imagecreatefrompng($qrCode_img_file);

            $font = "./data/font/simhei.ttf";
            if (function_exists('imagettftext') && file_exists($font)) {
                $invite_text_color = imagecolorallocate($qrCode_bg, 255, 0, 0);
                $info              = D('user')->where(['id' => $this->get_visitor_id()])->field('username,tele')->find();
                $username          = $info['username'];
                if (empty($username)) {
                    $username = $info['tele'];
                }
                $text = safe_tele($username) . '的推广二维码';
                imagettftext($qrCode_bg, 20, 0, ($qrCode_img_info[0] - $qrCodeSize) / 2, 330, $invite_text_color, $font, $text);
            }

            imagecopymerge($qrCode_bg, $qrCode_img, ($qrCode_img_info[0] - $qrCodeSize) / 2 - 10, 350, 0, 0, $qrCodeSize + 20, $qrCodeSize + 20, 100);

            header('Content-Type: ' . $qrCode->getContentType());
            imagepng($qrCode_bg);
            imagedestroy($qrCode_bg);
            imagedestroy($qrCode_img);
        }
        else {
            $info = D('user')->where(['uid' => $this->get_visitor_id()])->field('qrcode_status')->find();
            $this->assign(compact('info'));
            $this->display();
        }
    }

    public function ajax_qrcode_agreement()
    {
        $agreement_text = C('ins_qrcode_protocol');
        $this->ajaxResult(compact('agreement_text'));
    }

    public function invite_qrcode_update()
    {
        $user_info_id = D('user')->where(array('id' => $this->get_visitor_id()))->getField('id');
        if ($user_info_id > 0) {
            D('user')->where(['uid' => $this->get_visitor_id()])->save([
                'qrcode_status' => 1,
                'qrcode_time'   => date('Y-m-d H:i:s'),
            ]);
        }
        else {
            D('user')->add([
                'uid'           => $this->get_visitor_id(),
                'qrcode_status' => 1,
                'qrcode_time'   => date('Y-m-d H:i:s'),
            ]);
        }
        $this->ajaxResultSuccess();
    }

    public function ajax_grant_user()
    {
        $tele = $this->_get('tele');
        $uid  = D('user')->where(compact('tele'))->getField('id');
        if ($uid == $this->get_visitor_id()) {
            $this->ajaxResultError('不能转赠给自己');
        }
        $data = D('user')->get($uid, 'tele,realname');
        if ($data) {
            $this->ajaxResult($data);
        }
        else {
            $this->ajaxResultError('用户不存在!');
        }
    }

    /*
     * 转赠积分
     * */
    public function score_grant()
    {
        $info        = D('user')->get($this->get_visitor_id(), 'score,username');
        $uid_label   = '对方手机';
        $score_label = '转赠数量';

        $score_apply_min = C('ins_score_step');
        $score_apply_max = C('ins_score_grant_limit');
        if ($info['score'] < C('ins_score_grant_limit') * (1 + C('ins_score_grant_fee'))) {
            $score_apply_max = intval($info['score'] / (1 + C('ins_score_grant_fee')));
        }

        if (!$this->is_auth_name()) {
            $this->redirect('user/auth_name', ['from' => base64_encode(full_url(MODULE_NAME . '/' . ACTION_NAME))]);
        }

        if (IS_AJAX) {
            $score_apply = $this->_post('score', 'floatval', 1);
            $tele        = $this->_post('tele', 'intval', 0);
            $uid         = D('user')->where(compact('tele'))->getField('id');
            $to_info     = D('user')->get($uid, 'score,username');

            $to_score_total = $to_info['score'] + $score_apply;

            $score_apply_total = $score_apply * (1 + C('ins_score_grant_fee'));
            $from_score_total  = $info['score'] - $score_apply_total;

            if ($score_apply < $score_apply_min) {
                $this->ajaxResultError($score_label . '过小!');
            }
            if ($score_apply > $score_apply_max) {
                $this->ajaxResultError($score_label . '过大!');
            }
            if ($score_apply % C('ins_score_step') != 0) {
                $this->ajaxResultError('转赠数量必须是' . C('ins_score_step') . '的倍数');
            }
            if (D('user')->where(['id' => $uid,])->count() == 0) {
                $this->ajaxResultError($score_label . '不存在!');
            }
            if (!$this->is_auth_name()) {
                $this->ajaxResultError('您没有实名认证', ['url' => full_url('user/auth_name')]);
            }
            if (!$this->is_auth_name($uid)) {
                $this->ajaxResultError('对方没有实名认证');
            }

            if ($this->_post('act') == 'validate') {
                $this->ajaxResult([
                    'score_apply' => $score_apply,
                    'fee'         => $score_apply * C('ins_score_grant_fee')
                ]);
            }

            $user = D('user')->where(['id' => $uid])->find();

            $data = [
                'uid'              => $uid,
                'tele'             => $user['tele'],
                'title'            => sprintf('"%s"赠送给"%s"共%.2f积分', $info['username'], $to_info['username'], $score_apply),
                'score'            => $score_apply_total,
                'price'            => $score_apply,
                'from_uid'         => $this->get_visitor_id(),
                'from_score_total' => $from_score_total,
            ];
            D('score_grant')->add($data);

            $this->ajaxResultSuccess();
        }
        else {
            $status = $info['score'] * (1 + C('ins_score_grant_fee')) > C('ins_score_step');
            $this->assign(compact('info', 'status', 'score_label', 'uid_label'));
            $this->display();
        }
    }

    public function score_grant_list()
    {
        if (IS_AJAX) {
            $where = [
                'uid|from_uid' => $this->get_visitor_id(),
                'status'       => 1,
            ];

            $count = D('score_grant')->where($where)->count();
            $pager = $this->_pager($count);

            $list = D('score_grant')->where($where)
                ->limit($pager->firstRow, $pager->listRows)
                ->relation(true)
                ->select();

            foreach ($list as $key => $val) {
                $list[$key]['is_grant'] = $val['from_uid'] == $this->get_visitor_id();
                $list[$key]['add_time'] = date('m/d H:i', strtotime($val['add_time']));

                $status_label = '';
                if ($val['status'] == 0) {
                    $status_label = '失败';
                }
                else if ($val['status'] == 1) {
                    $status_label = '成功';
                }
                else if ($val['status']) {
                    $status_label = '无效';
                }
                $list[$key]['status_label'] = $status_label;
            }
            $isfull = count($list) == $pager->listRows;
            $this->ajaxResult(compact('list', 'isfull'));
        }
        else {
            $this->display();
        }
    }

    /**
     * 积分兑换申请
     */
    public function score_apply()
    {
        $info = D('user')->get($this->get_visitor_id(), 'score,realname,tele,realname,alipay_account');

        if (!$this->is_auth_name()) {
            $this->redirect('user/auth_name', ['from' => base64_encode(full_url(MODULE_NAME . '/' . ACTION_NAME))]);
        }

        if (!$this->ia_auth_pay()) {
            $this->redirect('user/auth_alipay', ['from' => base64_encode(full_url(MODULE_NAME . '/' . ACTION_NAME))]);
        }

        $score_apply_min = C('ins_score_step');
        $score_apply_max = C('ins_score_apply_limit');

        if ($info['score'] < C('ins_score_apply_limit') * (1 + C('ins_score_apply_fee'))) {
            $score_apply_max = intval($info['score'] / (1 + C('ins_score_apply_fee')));
        }

        if (IS_AJAX) {
            $score_apply = $this->_post('score', 'floatval', 1);

            $score_apply_total = $score_apply * (1 + C('ins_score_apply_fee'));
            $score_remain      = $info['score'] - $score_apply_total;

            if ($score_remain < 0) {
                $this->ajaxResultError('兑换数量过大!');
            }
            if (!$this->is_in_score_apply_time()) {
                $this->ajaxResultError('只接受工作时间申请!');
            }
            if ($score_apply_min > 0 && $score_apply < $score_apply_min) {
                $this->ajaxResultError("最低积分兑换数量$score_apply_min");
            }

            if ($score_apply > $score_apply_max) {
                $this->ajaxResultError('最大积分兑换数量' . $score_apply_max);
            }

            if ($score_apply % C('ins_score_step') != 0) {
                $this->ajaxResultError('积分兑换数量必须是' . C('ins_score_step') . '的倍数');
            }

            $score_times_where = [
                'uid'    => $this->get_visitor_id(),
                'status' => 0,
            ];
            if (D('user_apply')->where($score_times_where)->count()) {
                $this->ajaxResultError("您有未审核的申请");
            }

            $user = D('user')->get($this->get_visitor_id(), 'alipay_account,realname,realname,tele');
            $data = [
                'uid'         => $this->get_visitor_id(),
                'total'       => $score_remain,
                'score'       => $score_apply_total,
                'price'       => $score_apply,
                'fee'         => $score_apply * C('ins_score_apply_fee'),
                'pay_account' => $user['alipay_account'],
                'pay_method'  => '支付宝',
                'realname'    => $user['realname'],
                'tele'        => $user['tele'],
            ];
            if ($this->_post('act') == 'validate') {
                $this->ajaxResult([
                    'score_apply' => $score_apply,
                    'fee'         => $score_apply * C('ins_score_apply_fee')
                ]);
            }
            D('user_apply')->add($data);
            D('user')->where(['id' => $this->get_visitor_id(),])->save([
                'score'        => $score_remain,
                'score_frozen' => $score_apply_total
            ]);
            $this->ajaxResultSuccess();
        }
        else {
            $status = $info['score'] * (1 + C('ins_score_apply_fee')) > C('ins_score_step')
                && $this->is_in_score_apply_time();

            $this->assign(compact('info', 'status', 'score_apply_min', 'score_apply_max'));
            $this->display();
        }
    }

    private function is_in_score_apply_time()
    {
        return (date('w', time()) >= 1 && date('w', time()) <= 6) && time() >= strtotime(date('Y-m-d ' . C('score_apply_start_time') . ':00'))
        && time() <= strtotime(date('Y-m-d ' . C('score_apply_end_time') . ':00'));
    }

    public function score_apply_list()
    {
        if (IS_AJAX) {
            $where = [
                'uid' => $this->get_visitor_id(),
            ];

            $count = D('user_apply')->where($where)->count();
            $pager = $this->_pager($count);

            $list = D('user_apply')->where($where)
                ->limit($pager->firstRow, $pager->listRows)
                ->select();
            foreach ($list as $key => $val) {
                $list[$key]['add_time'] = date('Y-m-d', strtotime($val['add_time']));
            }
            $isfull = count($list) == $pager->listRows;
            $this->ajaxResult(compact('list', 'isfull'));
        }
        else {
            $this->display();
        }
    }

    public function score_list()
    {
        $score_logs_where   = ['not in', [5, 10]];
        $score_orders_where = ['in', [5]];
        if (IS_AJAX) {
            $type = $this->_get('type', 'trim', 'score_logs');

            $where = [
                'uid' => $this->get_visitor_id(),
            ];

            $mod_name = 'score_logs';
            $field    = "id,title,score";
            if ($type == 'score_orders') {
                $where['type']         = $score_orders_where;
                $where['score_status'] = 2;
                $where['is_hide']      = 0;
                $field .= ',score_time,type';
            }
            else if ($type == 'score_logs') {
                $where['type']         = $score_logs_where;
                $where['score_status'] = 2;
                $where['is_hide']      = 0;
                $field .= ',score_time,type';
            }
            else if ($type == 'score_logs_type_10') {
                $where['type']         = 10;
                $where['score_status'] = 2;
                $where['is_hide']      = 0;
                $field .= ',score_time,type';
            }
            else if ($type == 'score_days') {
                $mod_name      = 'score_days';
                $where['type'] = 1;
                $field .= ',add_time as score_time';
            }
            $count = D($mod_name)->where($where)->count();
            $pager = $this->_pager($count);
            $list  = D($mod_name)->field($field)
                ->where($where)
                ->limit($pager->firstRow, $pager->listRows)
                ->select();

            $isfull = count($list) == $pager->listRows;
            $this->ajaxResult(compact('list', 'isfull'));
        }
        else {
            $logs_score = floatval(D('score_logs')
                ->where(['uid' => $this->get_visitor_id(), 'type' => 1, 'score_status' => 2])
                ->sum('score'));

            $orders_score = floatval(D('score_logs')
                ->where(['uid' => $this->get_visitor_id(), 'type' => 5, 'score_status' => 2])
                ->sum('score'));

            D('user')->where(['id' => $this->get_visitor_id()])->save([
                'score_logs'   => $logs_score,
                'score_orders' => $orders_score,
            ]);
            $this->assign(compact('logs_score', 'orders_score'));
            $this->display();
        }
    }

    public function fans_list()
    {
        if (IS_AJAX) {
            $level = $this->_get('level', 'intval', 1);

            $where = [
                "invite_u$level" => $this->get_visitor_id(),
            ];

            $count  = D('user')->where($where)->count();
            $pager  = $this->_pager($count, 50);
            $fields = 'id,username,score,level_id,reg_time';
            if ($level == 1) {
                $fields .= ',weixin';
            }
            $list = D('user')->field($fields)
                ->where($where)
                ->relation(true)
                ->limit($pager->firstRow, $pager->listRows)
                ->select();
            foreach ($list as $key => $val) {
                $list[$key]['reg_time'] = date('Y-m-d', strtotime($val['reg_time']));
            }

            $isfull = count($list) == $pager->listRows;
            $this->ajaxResult(compact('list', 'isfull'));
        }
        else {
            $info = [];
            for ($i = 1; $i <= 3; $i++) {
                $info["total_level_$i"] = D('user')->where(["invite_u$i" => $this->get_visitor_id()])->count();
            }
            $this->assign(compact('info'));
            $this->display();
        }
    }

    public function auth_name()
    {
        $info           = D('user')->where(['uid' => $this->get_visitor_id()])->field('realname,cardid')->find();
        $info['weixin'] = D('user')->where(['id' => $this->get_visitor_id()])->getField('weixin');
        $status         = D('user')->where(['id' => $this->get_visitor_id()])->getField('is_auth');

        if (IS_AJAX) {
            if ($status) {
                $this->ajaxResultError('非法操作!');
            }
            $cardid = $this->_post('cardid');
            if (!Valid::idCard($cardid)) {
                $this->ajaxResultError('身份证号错误!');
            }
            $data = [
                'realname' => $this->_post('realname'),
                'cardid'   => $cardid,
            ];
            if (D('user')->where(['cardid' => $data['cardid'], 'uid' => ['neq', $this->get_visitor_id()]])->count() > 0) {

                $this->ajaxResultError('身份证号已经被注册');
            }
            $user_info_id = D('user')->where(array('id' => $this->get_visitor_id()))->getField('id');
            if ($user_info_id > 0) {
                D('user')->where(['id' => $this->get_visitor_id()])->save($data);
                D('user')->where(['id' => $this->get_visitor_id()])->save(array('weixin' => $this->_post('weixin')));
            }
            else {
                $data['uid']  = $this->get_visitor_id();
                $user_info_id = D('user')->add($data);
            }
            ($user_info_id > 0) && D('user')->where(['id' => $this->get_visitor_id()])->save([
                'is_auth' => 1,
            ]);

            $this->ajaxResultSuccess();
        }
        else {
            $this->assign(compact('info', 'status'));

            $this->display();
        }
    }


    public function user_settings()
    {
        $auth_status    = D('user')->where(['id' => $this->get_visitor_id()])->getField('is_auth');
        $user           = D('user')->get($this->get_visitor_id(), 'email,weixin');
        $profile_status = true;
        if ($user['email'] == '' || $user['weixin'] == '' || strpos($user['email'], "@default.com")) {
            $profile_status = false;
        }
        $this->assign(compact('alipay_status', 'auth_status', 'profile_status'));
        $this->display();
    }

	/* 抽奖 
    public function lottery(){
		if(IS_POST){
			$order_id = $this->_post('order_id','intval');
			$order_item_id = $this->_post('order_item_id','intval');
			$type = $this->_post('type','trim','odd');
			$order = D('order')->field('lottery,status')->find($order_id);
			$order_item = D('order')->field('item_id')->find($order_item_id);
			if($order['lottery'] != 0 || $order['status'] != 1){
				$this->ajaxResultError('订单状态不正确!');
			}
			$ssc_data = D('ssc_data')->order('opentimestamp desc')->find();
			$result = false;
			if($type == 'odd'){
				$result = $this->is_odd($ssc_data['lastno']);
			}
			if($type == 'even'){
				$result = $this->is_even($ssc_data['lastno']);
			}
			$ssc_log = array(
				'uid' => $this->get_visitor_id(),
				'item_id' => $order_item['item_id'],
				'order_id' => $order_id,
				'order_item_id' => $order_item_id,
				'guess' => $type,
				'data_id' => $ssc_data['id'],
				'add_time' => current_date(),
			);
			if($result == true){
				$order_data = array('lottery' => 2);
				$ssc_log['status'] = $lottery_type = 2;
			}else{
				$order_data = array('lottery' => 9);
				$ssc_log['status'] = $lottery_type = 1;
			}
			D('order')->where(array('id'=>$order_id))->save($order_data);
			D('ssc_log')->add($ssc_log);
			$this->ajaxResultSuccess('',array('type'=>$lottery_type));
		}else{
			$id = $this->_get('oid');
			$order = D('order')->field('lottery,status')->find($id);
			if($order['lottery'] != 0 || $order['status'] != 1){
				 $this->redirect('user/order_info',array('id'=>$id));
			}
			$order_item_id = D('order_item')->where(array('order_id'=>$id))->getField('id');
			$this->assign(compact('id', 'order_item_id'));
			$this->display();
		}
	}
	*/
	public function lottery(){
		if(IS_POST){
			$order_id = $this->_post('order_id','intval');
			$type = $this->_post('type','trim','odd');
			$order = D('order')->field('uid,prices,lottery,status')->find($order_id);
			if($order['lottery'] != 0){
				$this->ajaxResultError('订单状态不正确!');
			}
			$stime = time();
			$order_data = array(
				'lottery' => 1,
				'lottery_no' => $type=='odd' ? 1 : 0,
				'lottery_time' => $stime,
				'lottery_date' => date('Y-m-d H:i:s',$stime),
			);
			D('order')->where(array('id'=>$order_id))->save($order_data);

			/* 返佣 */
			$user = D('user')->field('invite_uid')->find($order['uid']);
			if($user['invite_uid'] > 0){
				$invite_user = D('user')->field('id,username')->find($user['invite_uid']);
				if($invite_user['id'] > 0){
					$commission_price = $order['prices']*0.01;
					$commission_price = $commission_price < 0.01 ? 0.01 : $commission_price;//最少一分钱
					D('price_log')->add(array(
						'uid' => $invite_user['id'],
						'uname' => $invite_user['username'],
						'price' => $commission_price,
						'action' => 'commission',
						'add_time' => date('Y-m-d H:i:s'),
						'remark' => '返佣',
						'key_id' => $order_id,
					));
					D('user')->where(array('id'=>$invite_user['id']))->setInc('price',$commission_price);
				}
			}

			$this->ajaxResultSuccess('',array('type'=>1));
		}else{
			$id = $this->_get('oid');
			$order = D('order')->field('lottery,status')->find($id);
			if($order['lottery'] != 0 || $order['status'] != 1){
				 $this->redirect('user/order_info',array('id'=>$id));
			}
			$order_item_id = D('order_item')->where(array('order_id'=>$id))->getField('id');
			$this->assign(compact('id', 'order_item_id'));
			$this->display();
		}
	}
	function is_odd($num){
		return (is_numeric($num)&($num&1));
	}

	function is_even($num){
		return (is_numeric($num)&(!($num&1)));
	}

	/* 升级后填写地址 */
	public function order_address(){
		if(IS_POST){
			$user_address = D('user_address')->where(['id' => $this->_post('address_id')])->find();
            foreach (['name', 'tele', 'province', 'city', 'area', 'address', 'zipcode'] as $val) {
                if (empty($user_address[$val])) {
                    $this->ajaxResultError('收货地址信息不全!');
                }
            }
			 $data = [
                'addr_name'         => $user_address['name'],
                'addr_tele'         => $user_address['tele'],
                'addr_province'     => $user_address['province'],
                'addr_city'         => $user_address['city'],
                'addr_area'         => $user_address['area'],
                'addr_address'      => $user_address['address'],
                'addr_zipcode'      => $user_address['zipcode'],
                'status'            => 3,
            ];

			D('order')->where(array('id'=>$this->_post('order_id')))->save($data);
			$this->ajaxResultSuccess();

		}else{
			$address_list = D('user_address')
				->where(['uid' => $this->visitor->info['id']])
				->limit(10)
				->select();

			$this->assign('address_list', $address_list);
			$this->display();
		}
	}

	/* 充值 */
	public function recharge(){
		if(IS_POST){
			$pricetype = $this->_post('pricetype','trim');
			$price = $pricetype ? $pricetype : $this->_post('price','trim');
			/* 生成支付订单 */
			$order_id = D('order')->add(array(
				'type' => 1,
				'uid' => $this->get_visitor_id(),
				'uname' => $this->visitor->info['username'],
				'orderid' => date('YmdHis') . rand(1000, 9999),
				'total' => $price,
				'prices' => $price,
				'add_time' => current_date(),
			));
			
			/* 充值记录 */
			D('user_recharge')->add(array(
				'uid' => $this->get_visitor_id(),
				'uname' => $this->visitor->info['username'],
				'type' => 1,
				'price' => $price,
				'add_time' => current_date(),
				'order_id' => $order_id,
			));
			session('ret_url',U('user/recharge'));
			$url = full_url('weixinpay/index', ['order_id' => $order_id]);
            $this->ajaxResult(compact('url'));

		}else{
			$price = $this->visitor->get('price');
			$log = D('user_recharge')->where(array('uid'=>$this->get_visitor_id(),'type'=>1,'status'=>1))->limit(20)->select();
			$this->assign(compact('price','log'));
			$this->display();
		}
	}

	/* 二维码 */
	public function erweima(){
		$id = $this->_get('id', 'intval');
        $code_url = U('passport/binding_invite',array('invite_uid'=> $this->visitor->info['id']),true,false,true);
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

	/* 我的钱包 */
	public function wallet()
    {
		$price = $this->visitor->get('price');
		$typename = array(
			'order' => '消费',
			'recharge' => '充值',
			'commission' => '返佣',
			'cash' => '提现',
		);
		$where['uid'] = $this->get_visitor_id();
		$count = D('price_log')->where($where)->count();
        $pager = $this->_pager($count,20);
		$log = D('price_log')->where($where)->limit($pager->firstRow, $pager->listRows)->order('add_time desc')->select();
		if (count($log) == $pager->listRows) {
            $this->assign('show_load', 1);
        }
		$this->assign(compact('price','log','typename'));
		if (IS_AJAX) {
            $resp = $this->fetch('user_price_waterfall');
            $data = array(
                'isfull' => count($list) == $pager->listRows,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }
        else {
            $this->display();
        }
    }

	/* 提现 */
	public function cash(){
		if(IS_POST){
			$price = $this->_post('price','trim');
			$user_price = $this->visitor->get('price');
			if($price > $user_price){
				$this->ajaxResultError('余额不足');
			}
			$freeprice = $price*0.02;
			$freeprice = $freeprice < 0.01 ? 0.01 : $freeprice;//最少一分钱
			$realprice = $price - $freeprice;
			$cash_id = D('user_recharge')->add(array(
				'uid' => $this->get_visitor_id(),
				'type' => 2,
				'uname' => $this->visitor->info['username'],
				'price' => $price,
				'freeprice' => $freeprice, 
				'realprice' => $realprice,
				'add_time' => current_date(),
			));

			D('user')->where(array('id'=>$this->get_visitor_id()))->setDec('price',$price);
			D('price_log')->add(array(
				'uid' => $this->get_visitor_id(),
				'uname' => $this->visitor->info['username'],
				'price' => 0 - $price,
				'action' => 'cash',
				'add_time' => date('Y-m-d H:i:s'),
				'remark' => '余额提现',
				'key_id' => $cash_id,
			));
			$this->ajaxResultSuccess();
		}else{
			$user = D('user')->get($this->get_visitor_id(),'price,bankname,bankid,realname,is_auth');
			$log = D('user_recharge')->where(array('uid'=>$this->get_visitor_id(),'type'=>2))->limit(20)->order('add_time desc')->select();
			$this->assign(compact('user','log'));
			$this->display();
		}
	}

	/* 实名认证 */
	public function certification(){
		if(IS_POST){
			$data = [
                'bankname' => $this->_post('bankname'),
                'bankid'   => $this->_post('bankid'),
                'realname' => $this->_post('realname'),
				'sex' => $this->_post('sex'),
				'company' => $this->_post('company'),
				'cardid'  => $this->_post('cardid'),
				'receive_erweima'  => $this->_post('receive_erweima'),
				'is_auth' => 1 //直接通过认证
            ];
			D('user')->where(['id' => $this->get_visitor_id()])->save($data);
            $this->ajaxResultSuccess();
		}else{
			//来路
			$ret_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __APP__;
			$this->assign('ret_url', $ret_url);
			$info = D('user')->get($this->get_visitor_id(),'price,bankname,bankid,realname,sex,company,cardid,is_auth,receive_erweima');
			$this->assign('info',$info);
			$this->display();
		}
	}

	/* 玩法说明 */
	public function explain(){
		$this->display();
	}
	
	public function lower(){
		$topkey = $this->visitor->get('topkey');
		$where['topkey'] = $topkey;
		$where['id'] = array('neq',$this->get_visitor_id());
		$page_size = 20;
		$count = D('user')->where($where)->count();
		$pager = $this->_pager($count,$page_size);
		$list = D('user')->where($where)->limit($pager->firstRow, $pager->listRows)->order('reg_time desc')->select();
		$this->assign('list',$list);

		if (count($list) == $pager->listRows) {
            $this->assign('show_loading', 1);
        }

		if (IS_AJAX) {
            $resp = $this->fetch('lower_waterfall');
            $data = array(
                'isfull' => count($list) == $pager->listRows,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }

		$suse_time = date('Y-m-d') . ' 00:00:00';
        $euse_time = date('Y-m-d') . ' 23:59:59';
		
		/* 充值统计 */
		$recharge_count_map['ur.status'] = 1;
		$recharge_count_map['ur.type'] = 1;
		$recharge_count_map['u.topkey'] = $topkey;
		$recharge['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($recharge_count_map)->sum('ur.price');
		$recharge_count_map['ur.add_time'][] = array('elt', $euse_time);
		$recharge_count_map['ur.add_time'][] = array('egt', $suse_time);
		$recharge['days'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($recharge_count_map)->sum('ur.price');

		/* 提现统计 */
		$cash_count_map['ur.status'] = 1;
		$cash_count_map['ur.type'] = 2;
		$cash_count_map['u.topkey'] = $topkey;
		$cash['total'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($cash_count_map)->sum('ur.price');
		$cash_count_map['ur.add_time'][] = array('elt', $euse_time);
		$cash_count_map['ur.add_time'][] = array('egt', $suse_time);
		$cash['days'] = D('user_recharge')->alias('ur')->join(table('user').' as u ON ur.uid = u.id')->where($cash_count_map)->sum('ur.price');

		$recharge['total'] = $recharge['total'] ? $recharge['total'] : 0.00;
		$recharge['days'] = $recharge['days'] ? $recharge['days'] : 0.00;
		$cash['total'] = $cash['total'] ? $cash['total'] : 0.00;
		$cash['days'] = $cash['days'] ? $cash['days'] : 0.00;


		$this->assign('count',$count);
		$this->assign('recharge',$recharge);
		$this->assign('cash',$cash);
		$this->display();
	}
	
	public function lowerdetail(){
		$uid = $this->_get('uid','intval');
		if(D('user')->where(array('id'=>$uid,'topkey'=>$this->visitor->get('topkey'),'id'=>array('neq',$this->get_visitor_id())))->count() < 1){
			$this->_404();
		}
		$type = $this->_request('type','intval',1);
		$where['uid'] = $uid;
		$where['type'] = $type;
		$list = D('user_recharge')->where($where)->order('add_time desc')->select();
		$this->assign('list',$list);
		$this->assign('type',$type);
		$this->display();
	}

	/* 绑定手机号 */
	public function bindtele(){
		if(IS_POST){
			$tele = $this->_post('tele','trim');
			if(!preg_match("/^1[0-9][0-9]{1}[0-9]{8}$/",$tele)){    
				$this->ajaxResultSuccess('手机号格式不正确',array('error'=>1));
			}
			$is_has = D('user')->where(array('tele'=>$tele))->count();
			if($is_has > 0){
				$this->ajaxResultSuccess('已绑定微信',array('error'=>1));
			}
			$password = $this->_post('password','trim');
			$rpassword = $this->_post('rpassword','trim');
			if($password == ''){
				$this->ajaxResultSuccess('密码不能为空',array('error'=>1));
			}
			if($password != $rpassword){
				$this->ajaxResultSuccess('两次密码输入不一致',array('error'=>1));
			}
			D('user')->where(['id'=>$this->get_visitor_id()])->save(['password'=>md5($password),'tele'=>$tele]);
			/* 更新 */
			$user_info = D('user')->field('id,username,nickname,password,tele,img,email,quans,score,msg_sys')->find($this->get_visitor_id());
			$this->visitor->assign_info($user_info);
			$this->ajaxResultSuccess('');
		}else{
			$this->display();
		}
	}

	public function commission(){
		$where['u.invite_uid'] = $this->get_visitor_id();
		$where['o.lottery'] = array('IN',array(1,2,9));
		$keywords = $this->_get('keywords', 'trim');
        if ($keywords) {
            $where['username|tele'] = ['like', "%$keywords%"];
            $this->assign(compact('keywords'));
        }
		$page_size = 20;
		$count = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->count();
		$pager = $this->_pager($count,$page_size);
		$list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->limit($pager->firstRow, $pager->listRows)->where($where)->field('o.prices as prices,o.status as status,o.add_time as add_time,o.lottery as lottery,u.id as uid,u.username as username,u.tele as tele,o.id as oid')->order('o.add_time desc')->select();
		$lottery = array(1=>'升级中',2=>'升级成功',9=>'升级失败');
		foreach($list as $key=>$val){
			if(in_array($val['status'],[3,4,5,6])){
				$list[$key]['status'] = '已提货';
			}elseif(in_array($val['status'],[9])){
				$list[$key]['status'] = '退款';
			}else{
				$list[$key]['status'] = '待提货';
			}
			$list[$key]['lottery'] = $lottery[$val['lottery']];

		}
		$this->assign('list',$list);

		if (count($list) == $pager->listRows) {
            $this->assign('show_loading', 1);
        }
		if (IS_AJAX) {
            $resp = $this->fetch('commission_waterfall');
            $data = array(
                'isfull' => count($list) == $pager->listRows,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }

		$stime = date('Y-m-d') . ' 00:00:00';
        $etime = date('Y-m-d') . ' 23:59:59';

		/* 统计 */
		$order['total'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->sum('prices');
		$where['o.add_time'][] =  array('elt', $etime);
		$where['o.add_time'][] =  array('egt', $stime);
		$order['days'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->sum('prices');

		$commission_where['uid'] = $this->get_visitor_id();
		$commission_where['action'] = 'commission';
		$commission['total'] = D('price_log')->where($commission_where)->sum('price');
		$commission_where['add_time'][] =  array('elt', $etime);
		$commission_where['add_time'][] =  array('egt', $stime);
		$commission['days'] = D('price_log')->where($commission_where)->sum('price');
		
		$order['total'] = $order['total'] ? $order['total'] : 0.00;
		$order['days'] = $order['days'] ? $order['days'] : 0.00;
		$commission['total'] = $commission['total'] ? $commission['total'] : 0.00;
		$commission['days'] = $commission['days'] ? $commission['days'] : 0.00;

		$this->assign('order',$order);
		$this->assign('commission',$commission);
		$this->display();

	}
	
	public function lower_commission(){
		$topkey = $this->visitor->get('topkey');
		$where['u.topkey'] = $topkey;
		$where['o.lottery'] = array('IN',array(1,2,9));
		$keywords = $this->_get('keywords', 'trim');
        if ($keywords) {
            $where['username|tele'] = ['like', "%$keywords%"];
            $this->assign(compact('keywords'));
        }
		$page_size = 20;
		$count = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->count();
		$pager = $this->_pager($count,$page_size);
		$list = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->limit($pager->firstRow, $pager->listRows)->where($where)->field('o.prices as prices,o.status as status,o.add_time as add_time,o.lottery as lottery,u.id as uid,u.username as username,u.tele as tele,o.id as oid')->order('o.add_time desc')->select();
		$lottery = array(1=>'升级中',2=>'升级成功',9=>'升级失败');
		foreach($list as $key=>$val){
			if(in_array($val['status'],[3,4,5,6])){
				$list[$key]['status'] = '已提货';
			}elseif(in_array($val['status'],[9])){
				$list[$key]['status'] = '退款';
			}else{
				$list[$key]['status'] = '待提货';
			}
			$list[$key]['lottery'] = $lottery[$val['lottery']];

		}
		$this->assign('list',$list);

		if (count($list) == $pager->listRows) {
            $this->assign('show_loading', 1);
        }
		if (IS_AJAX) {
            $resp = $this->fetch('commission_waterfall');
            $data = array(
                'isfull' => count($list) == $pager->listRows,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }

		$stime = date('Y-m-d') . ' 00:00:00';
        $etime = date('Y-m-d') . ' 23:59:59';

		/* 统计 */
		$order['total'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->sum('prices');
		$where['o.add_time'][] =  array('elt', $etime);
		$where['o.add_time'][] =  array('egt', $stime);
		$order['days'] = D('order')->alias('o')->join(table('user').' as u ON o.uid = u.id')->where($where)->sum('prices');


		$commission_where['u.topkey'] = $topkey;
		$commission_where['p.action'] = 'commission';

		$commission['total'] = D('price_log')->alias('p')->join(table('user').' as u ON p.uid = u.id')->where($commission_where)->sum('p.price');

		$commission_where['p.add_time'][] =  array('elt', $etime);
		$commission_where['p.add_time'][] =  array('egt', $stime);
		$commission['days'] = D('price_log')->alias('p')->join(table('user').' as u ON p.uid = u.id')->where($commission_where)->sum('p.price');

		
		$order['total'] = $order['total'] ? $order['total'] : 0.00;
		$order['days'] = $order['days'] ? $order['days'] : 0.00;
		$commission['total'] = $commission['total'] ? $commission['total'] : 0.00;
		$commission['days'] = $commission['days'] ? $commission['days'] : 0.00;

		$this->assign('order',$order);
		$this->assign('commission',$commission);
		$this->display();

	}



	public function order_take_delivery(){
		$id = $this->_request('id','intval');
		$order = D('order')->field('express,status')->find();
		if($order['status'] == 2 && $order['express'] <= 0){
			$price = $this->visitor->get('price');
			$express = C('ins_express');
			if($price < $express){
				$this->ajaxResultSuccess('',['error'=>2]);
			}
			D('user')->where(array('id'=>$this->get_visitor_id()))->setDec('price',$express);
			D('price_log')->add(array(
				'uid' => $this->get_visitor_id(),
				'uname' => $this->visitor->info['username'],
				'price' => 0 - $express,
				'action' => 'express',
				'add_time' => date('Y-m-d H:i:s'),
				'remark' => '支付提货邮费',
				'key_id' => $id,
			));
			D('order')->where(['id'=>$id])->save(array('express'=>$express));
			$this->ajaxResultSuccess('');
		}else{
			$this->ajaxResultError('订单状态错误');
		}
	}

}