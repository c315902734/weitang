<?php

class transactionAction extends mbaseAction
{
    public function create_order(){
		$item_id      = $this->_request('item_id', 'intval', 0);
        $sku_id       = $this->_request('sku_id', 'intval', 0);
        $goods_number = $this->_request('goods_number', 1);
        $item = D('item')->where(['id' => $item_id])->relation(true)->find();

        if(C('ins_buy_limit') == 1){
            /*if ($this->hasOrderScoreTimeItem($item_id)) {
                $this->ajaxResultError('限购时间内不能重复购买');
            }*/
			if ($this->hasOrderLimitedTimeItem($item_id)) {
                $this->ajaxResultError('您已经买了很多了^_^');
            }
			if ($item['score_maxs'] > 0 && $goods_number > $item['score_maxs']) {
				$this->ajaxResultError('购买数量过大!');
			}
        }

		if ($goods_number > $item['stock']) {
            $this->ajaxResultError('购买数量过大!');
        }

        $item['express'] = floatval($item['express']);

		$sku  = D('item_sku')->where(['id' => $sku_id])->find();
        $this->assign('sku', $sku);

		$item_price       = intval($sku['price']) > 0 ? $sku['price'] : $item['price'];
        $total_item_price = $item_price * $goods_number;
        $express          = $goods_number * $item['express'];
        $total_cprice     = $goods_number * $item['cprice'];
        $total_price      = $total_item_price + $express;

		if ($total_price <= 0) {
			$this->ajaxResultError('订单金额为0');
		}

		$user  = D('user')->get($this->get_visitor_id(), 'price');

		if($total_price > $user['price']){
			$this->ajaxResultError('余额不足,请充值');
		}
		//增加中奖后金额
		$lottery_p = $item['price_up'] * $goods_number;
		$lottery_t = $lottery_p + $express;

		$data = [
			'mid'               => $item['mid'],
			'type'              => 0,
			'orderid'           => date('YmdHis') . rand(1000, 9999),
			'total'             => $total_price,
			'express'           => $express,
			'prices'            => $total_item_price,
			'cprice'            => $total_cprice,
			'uid'               => $this->get_visitor_id(),
			'uname'             => $this->visitor->info['username'],
			'pays'              => 6,
			'pays_status'       => 1,
			'pays_price'        => $total_price,
			'status'            => 1,
			'pays_time'         => current_date(),
			'lottery_price' => $lottery_p,
			'lottery_total' => $lottery_t
		];

		$order_id = D('order')->add($data);

		$order_item = [
			'item_id'     => $item_id,
			'order_id'    => $order_id,
			'orderid'     => $data['orderid'],
			'title'       => $item['title'],
			'price'       => $item['price'],
			'nums'        => $goods_number,
			'uid'         => $this->get_visitor_id(),
			'uname'       => $this->visitor->info['username'],
			'mid'         => $item['mid'],
		];

		if ($sku) {
			$order_item['skus']   = $sku['name'] . ':' . $sku['val'];
			$order_item['sku_id'] = $sku_id;
		}

		D('order_item')->add($order_item);

		$new_price = $user['price'] - $total_price;
		D('user')->where(['id' => $this->get_visitor_id()])->save([
			'price' => $new_price > 0 ? $new_price : 0,
		]);

		D('price_log')->add([
			'uid'           => $this->get_visitor_id(),
			'uname'         => $this->visitor->info['username'],
			'key_id'        => $order_id,
			'action'        => 'order',
			'price'         => 0 - $total_price,
			'add_time'    => current_date(),
			'remark'         => "订单$data[orderid]消费￥${total_price}元",
		]);

		D('item')->where(array('id'=>$item_id))->setDec('stock',$goods_number);
		D('item')->where(array('id'=>$item_id))->setInc('sales',$goods_number);
		D('user')->where(['id' => $this->get_visitor_id()])->setInc('orders',1);

		$url = full_url('user/order_info',array('id'=>$order_id));
		$this->ajaxResult(compact('url'));

	}

    public function direct_buy()
    {
        $item_id      = $this->_request('item_id', 'intval', 0);
        $sku_id       = $this->_request('sku_id', 'intval', 0);
        $goods_number = $this->_request('goods_number', 1);

        if (!IS_DEVELOPMENT) {
            /*
            if ($this->hasNoPayOrder()) {
                $this->ajaxResultError('您有未支付的订单,请支付完成后再购买');
            }
			*/
            if ($this->hasNoPayOrderItem($item_id)) {
                $this->ajaxResultError('您有未支付的相同商品订单,请支付完成后再购买');
            }
            if ($this->hasOrderScoreTimeItem($item_id)) {
                $this->ajaxResultError('限购时间内不能重复购买');
            }
        }
        $item = D('item')->where(['id' => $item_id])->relation(true)->find();
        $sku  = D('item_sku')->where(['id' => $sku_id])->find();
        $this->assign('sku', $sku);

        if ($item['score_maxs'] > 0 && $goods_number > $item['score_maxs']) {
            $this->ajaxResultError('购买数量过大!');
        }
        $item['express'] = floatval($item['express']);

        $item_price       = intval($sku['price']) > 0 ? $sku['price'] : $item['price'];
        $total_item_price = $item_price * $goods_number;
        $express          = $goods_number * $item['express'];
        $total_cprice     = $goods_number * $item['cprice'];
        $total_price      = $total_item_price + $express;

        $user  = D('user')->get($this->get_visitor_id(), 'score,invite_u1,invite_u2,invite_u3');
        $score = intval($user['score']);
        $score = intval($score > $item['price'] * $goods_number ? $item['price'] * $goods_number : $score);

        if (IS_POST) {
            if ($total_price <= 0) {
                $this->ajaxResultError('订单金额为0');
            }
            $order_score = $this->_post('order_score', 'intval');
            if ($order_score > $score) {
                $this->ajaxResultError('积分输入过大!');
            }
            else if ($order_score < 0) {
                $order_score = 0;
            }

            $user_address = D('user_address')->where(['id' => $this->_post('user_address_id')])->find();
            foreach (['name', 'tele', 'province', 'city', 'area', 'address', 'zipcode'] as $val) {
                if (empty($user_address[$val])) {
                    $this->ajaxResultError('收货地址信息不全!');
                }
            }
            $data = [
                'mid'               => $item['mid'],
                'type'              => $item['type'],
                'orderid'           => date('YmdHis') . rand(1000, 9999),
                'total'             => $total_price,
                'express'           => $express,
                'prices'            => $total_item_price,
                'cprice'            => $total_cprice,
                'uid'               => $this->get_visitor_id(),
                'pays'              => 2,
                'score'             => $order_score,
                'remark'            => $this->_post('remark'),
                'addr_name'         => $user_address['name'],
                'addr_tele'         => $user_address['tele'],
                'addr_province'     => $user_address['province'],
                'addr_city'         => $user_address['city'],
                'addr_area'         => $user_address['area'],
                'addr_address'      => $user_address['address'],
                'addr_zipcode'      => $user_address['zipcode'],
                'pays_status'       => $order_score == $total_price ? 1 : 0,
                'status'            => $order_score == $total_price ? 1 : 0,
                'score_pools'       => $item['score_pools'] * $goods_number,
                'score_my'          => $item['score_my'] * $goods_number,
                'express_recv_time' => C('ins_order_receive_day'),
            ];
            if ($data['pays_status']) {
                $data['pays_time'] = current_date();
            }

            for ($i = 1; $i <= 3; $i++) {
                $data["score_u${i}"]  = $data['score_my'] * $item["score_${i}"];
                $data["invite_u${i}"] = $user["invite_u${i}"];
            }

            $express_id = $this->_post('express_id');
            if ($express_id) {
                $data['express_type'] = $express_id;
                $express              = D('express')->where(['id' => $express_id])->find();
                $data['express_name'] = $express['express_name'];
            }
            $order_id = D('order')->add($data);
            //处理使用积分
            if ($order_score) {
                D('user')->where(['id' => $this->get_visitor_id()])->save([
                    'score' => $user['score'] - $order_score,
                ]);

                $user = D('user')->get($this->get_visitor_id(), 'tele');
                D('score_logs')->add([
                    'uid'           => $this->get_visitor_id(),
                    'type'          => 5,
                    'title'         => "订单$data[orderid]消费${order_score}积分",
                    'tele'          => $user['tele'],
                    'order_id'      => $order_id,
                    'order_orderid' => $data['orderid'],
                    'order_prices'  => $data['prices'],
                    'score'         => 0 - $order_score,
                    'score_time'    => current_date(),
                    'score_status'  => 2,
                    'status'        => 1,
                ]);
            }

            $order_item = [
                'item_id'     => $item_id,
                'order_id'    => $order_id,
                'orderid'     => $data['orderid'],
                'title'       => $item['title'],
                'price'       => $item['price'],
                'nums'        => $goods_number,
                'uid'         => $this->get_visitor_id(),
                'score_pools' => $item['score_pools'],
                'score_my'    => $item['score_my'],
                'mid'         => $item['mid'],
            ];
            for ($i = 1; $i <= 3; $i++) {
                $order_item["score_u${i}"] = $order_item['score_my'] * $item["score_${i}"];
            }

            if ($sku) {
                $order_item['skus']   = $sku['name'] . ':' . $sku['val'];
                $order_item['sku_id'] = $sku_id;
            }

            D('order_item')->add($order_item);

            if ($data['status']) {
                ItemService::update_stock($item_id, $sku_id, $goods_number);
                $url = full_url('user/order');
            }
            else {
                $url = full_url('pay/alipay_index', ['order_id' => $order_id]);
            }
            $this->ajaxResult(compact('url'));
        }
        else {
            $user_score = intval($user['score']);

            $address_id = $this->_get('address_id', 'intval');
            if ($address_id > 0) {
                $user_address = D('user_address')->where(['id' => $address_id])->find();
            }
            else {
                $user_address = D('user_address')->where(['uid' => $this->get_visitor_id()])->order('is_default desc')->find();
            }

            if ($item['mid']) {
                $express_list = D('member_express')->where(['mid' => $item['mid']])->relation(true)->select();
                $this->assign(compact('express_list'));
            }
            $article_list = D('article')->field('id,title')->where(['id' => ['in', [12, 14, 15]]])->select();
            $this->assign(compact('score', 'item', 'total_price', 'sku', 'user_address', 'user_score', 'article_list'));
            $this->display();
        }
    }
}