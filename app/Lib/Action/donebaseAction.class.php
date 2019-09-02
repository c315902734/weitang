<?php
/**
 * 用户控制器基类
 *
 * @author andery
 */
class donebaseAction extends frontendAction {

	public $ordermsg = '';
	public $cartmsg = '';


    public function _initialize(){
        parent::_initialize();
    }
	
	//添加商品到购物车
	protected function _addtocart($item_id,$number,$sku_id,$tuan_id=0){
		$item = D('item')->where(array('id'=>$item_id))->find();
		if(!$item) {
			$this->cartmsg = '商品不存在';
			return false;
		}
		$sku_name = '';
		if($sku_id > 0){
			$sku_info = D('item_sku')->find($sku_id);
			$stock = $sku_info['stock'] * $number;
			$sku_name = $sku_info['name'];
		}else{
			$stock = $number;
		}
		if($item['stock'] >= 0 && $item['stock'] < $stock){
			$this->cartmsg = '商品库存不足';
			return false;
		}
		//限购数量
		if(!$this->itemMaxs($item_id,$number,true)){
			$this->cartmsg = '购买数量超出限购数量';
			return false;
		}
		$price = $item['price'];
		/*if(isset($_SESSION['sessionid'])){
			$session = $_SESSION['sessionid'];
		}else{
			$session = md5(time());
			session('sessionid', $session);
		}*/
		if($sku_id > 0){
			$price = $this->itemPrice($item_id,$sku_id);
		}
		if($tuan_id > 0){
			$price = $this->itemTuanPrice($item_id,$tuan_id);
			if(!$price){
				$this->cartmsg = '团购商品无效';
				return false;
			}
		}
		$where = array('item_id'=>$item_id,'sku_id'=>$sku_id,'uid'=>$this->uid);
		$cart = D('cart')->where($where)->find();
		if($cart['id'] > 0){
			D('cart')->where(array('id'=>$cart['id']))->save(array(
				'price' => $price,
				'nums' => $number + $cart['nums'],
			));
		}else{
			$data['item_id'] = $item_id;
			$data['uid'] = intval($this->visitor->info['id']);
			$data['nums'] = $number;
			$data['price'] = $price;
			$data['attr'] = $sku_name;
			$data['sku_id'] = $sku_id;
			$data['tuan_id'] = $tuan_id;
			$data['add_time'] = date('Y-m-d H:i:s');
			//$data['sessionid'] = $session;
			D('cart')->add($data);
		}
		return true;
	}

	//判断限购
	protected function itemMaxs($item_id,$number,$is_cart = false){
		$maxs = D('item')->where(array('id'=>$item_id))->getField('maxs');
		if($maxs <= 0) return true;
		if($maxs < $number){
			return false;
		}
		if($is_cart == true){
			//所在购物车数量
			$cart_number = D('cart')->where(array('uid'=>$this->uid,'item_id'=>$item_id))->sum('nums');
			$cart_number = $cart_number ? $cart_number : 0;
			if($maxs < $number+$cart_number){
				return false;
			}
		}
		return true;
	}

	//获取属性价格
	protected function itemPrice($item_id,$sku_id){
		$sku = D('item_sku')->where(array('id'=>$sku_id,'item_id'=>$item_id))->find();
		$price = D('item')->where(array('id'=>$item_id))->getField('price');
		$new = $price*$sku['stock']-$sku['price']*$sku['stock'];
		return $new;
	}

	//获取商品团购价格
	protected function itemTuanPrice($item_id,$tuan_id){
		$date = date('Y-m-d');
		$tuan = D('tuan')->where(array('id'=>$tuan_id,'item_id'=>$item_id,'stime'=>array('elt',$date),'etime'=>array('egt',$date),'stock'=>array('gt',0)))->find();
		if(!$tuan['id']) return false;
		return $tuan['price'];
	}

	//获取购物车商品列表
    protected function getCartGoodsList($where = array()){
		$map['uid'] = $this->uid;
		if(!empty($where['id'])){
			$map['id'] = $where['id'];
		}
		$data = D('cart')->where($map)->relation(true)->select();
		if(!$data) return false;
		$list = array();
		foreach($data as $key=>$val){
			$val['subtotal'] = $val['nums'] * $val['price'];
			$list[$val['id']] = $val;
		}
		return $list;
	}

	//计算购物车总额
    protected function getCartAmount($goods_list){
		$total = array(
			'amount' => 0,
			'goodsprice' => 0,
			'weight' => 0,
			'discount' => 0,
			'fee' => 0,
			'number' => 0,
			'catenum' => 0,//种类数量
		);
		foreach($goods_list as $key=>$val){
			$total['goodsprice'] += $val['subtotal'];
			$total['amount'] += $val['subtotal'];
			$total['weight'] += $val['item']['weight'];
			$total['number'] += $val['nums'];
			$total['catenum']++;
		}
		//运费
		$total['fee'] = $this->getFreightPrice($total['weight'],$total['goodsprice']);
		$total['amount'] += $total['fee'];
		if($total['quan'] > 0){
			$total['amount'] -= $total['quan'];
		}
		return $total;
	}
	
	//获取运费
	protected function getFreightPrice($goodsweight,$goodsprice){
		$price = C('ins_freight_price');//基础费用
		$unit = C('ins_freight_unit');//辅料重量
		$weight = C('ins_freight_weight');//按重量单价
		$excess = C('ins_freight_excess');//免首重金额
		$fee = 0;
		//总重量
		$tweight = $goodsweight + $unit;
		//“总重量”小于等于1公斤，且“商品金额”小于“免首重金额”，则运费为“基础运费”
		if($tweight <= 1 && $goodsprice < $excess){
			$fee = $price;
		}
		//“总重量”小于等于1公斤，且“商品金额”大于或等于“免首重金额”，则运费为“0”
		elseif($tweight <= 1 && $goodsprice >= $excess){
			$fee = 0;
		}
		//总重量”大于1公斤，且“商品金额”小于“免首重金额”，则运费为“基础运费”+（“总重量”-1）余数进位取整*“按重量单价”
		elseif($tweight > 1 && $goodsprice < $excess){
			$fee = $price + ceil(($tweight - 1))*$weight;
		}
		//总重量”大于1公斤，且“商品金额”大于或等于“免首重金额”，则运费为（“总重量”-1）余数进位取整*“按重量单价”；
		elseif($tweight > 1 && $goodsprice >= $excess){
			$fee = ceil(($tweight - 1))*$weight;
		}
		return $fee;
	}


	//提交订单
	protected function submitOrder($order,$cart_goods){
		if(!is_array($order) || empty($order)) return false;
		if(!$cart_goods){
			$this->ordermsg = '购物车没有商品';
			return false;
		}
		$data = array();
		if($order['address_id'] > 0){
			$address = D('user_address')->where(array('uid'=>$this->uid,'id'=>$order['address_id']))->find();
		}else{
			$address = $order['address'];
		}
		if($address['name'] == '' || $address['tele'] == '' || $address['province'] == '' || $address['city'] == '' || $address['area'] == '' || $address['address'] == ''){
			$this->ordermsg = '收货人信息不全';
			return false;
		}
		//判断库存
		foreach($cart_goods as $key=>$val){
			if($val['nums'] > $val['item']['stock'] && $val['item']['stock'] >= 0){
				$this->ordermsg = msubstr($val['item']['title'],20).'库存不足';
				return false;
			}
			if($val['tuan_id'] > 0){
				$tuan_stock = D('tuan')->where(array('id'=>$val['tuan_id'],'stime'=>array('elt',date('Y-m-d')),'etime'=>array('egt',date('Y-m-d'))))->getField('stock');
				if($val['nums'] > $tuan_stock){
					$this->ordermsg = '团购商品库存不足';
					return false;
				}
			}
			//限购数量
			if(!$this->itemMaxs($val['item_id'],$val['nums'])){
				$this->ordermsg = '购买数量超出限购数量';
				return false;
			}
			$maxs = D('item')->where(array('id'=>$val['item_id']))->getField('maxs');
			if($maxs < $val['nums'] && $maxs > 0){
				$this->ordermsg = msubstr($val['item']['title'],20).'购买数量超出限购数量';
				return false;
			}
		}
		$data['addr_name'] = $address['name'];
		$data['addr_tele'] = $address['tele'];
		$data['addr_province'] = $address['province'];
		$data['addr_city'] = $address['city'];
		$data['addr_area'] = $address['area'];
		$data['addr_address'] = $address['address'];
		$data['pays'] = $order['pays'];
		$data['quan_id'] = $order['quan_id'];
		$data['score'] = $order['score'];
		if($data['quan_id'] > 0){
			//判断当前优惠券是否有效
			$date = date('Y-m-d');
			$quan_info = D('quan')->where(array('id'=>$data['quan_id'],'uid'=>$this->uid,'stime'=>array('elt',$date),'etime'=>array('egt',$date)))->find();
			if(!$quan_info){
				$this->ordermsg = '优惠券不存在';
				return false;
			}
			if($quan_info['max'] == 1 && $quan_info['used_status'] == 1){
				$this->ordermsg = '优惠券不存在';
				return false;
			}
			$quan = A('home/quan');
			$cart_cate_ids = $quan->getCartItemCateId();
			$cart_item_ids = $quan->getCartItemItemId();
			$code = D('quan_code')->where(array('id'=>$quan_info['quan_id']))->find();
			$cate_id = $code['cate_id'];
			$item_id = $code['item_id'];
			if($cate_id > 0 && $cart_cate_ids && !in_array($cate_id,$cart_cate_ids)){
				$this->ordermsg = '优惠券不存在';
				return false;
			}
			if($item_id > 0 && $cart_item_ids && !in_array($item_id,$cart_item_ids)){
				$this->ordermsg = '优惠券不存在';
				return false;
			}
			$data['quan_price'] = $quan_info['price'];
		}
		if($data['score'] > 0){
			$user_score = D('user')->where(array('id'=>$this->uid))->getField('score');
			if($user_score < $data['score']){
				$this->error('积分不足');
			}
			$score_price = $data['score'] / 100;
		}
		$data['uid'] = $this->visitor->info['id'];
		$data['uname'] = $this->visitor->info['username'];
		$data['orderid'] = date('YmdHis').strRand(4,'number');
		$total = $order['total'];
		if($data['quan_price'] > 0){
			$total['amount'] -= $data['quan_price'];
		}
		if($score_price > 0){
			$total['amount'] -= $score_price;
		}
		$data['express'] = $total['fee'];
		$data['total'] = $total['amount'] > 0 ? $total['amount'] : 0.01;
		$data['prices'] = $total['goodsprice'];
		$data['add_time'] = date('Y-m-d H:i:s');
		$data['express_type'] = $order['express_type'];
		$data['dev'] = 1;
		//发票
		$data['invoice_status'] = $order['invoice_status'];
		$invoice['type'] = $order['invoice_type'];
		$invoice['title'] = $order['invoice_title'];
		$invoice['info'] = $order['invoice_info'];
		$invoice['tax'] = $order['invoice_tax'];
		$invoice['address'] = $order['invoice_address'];
		$invoice['tele'] = $order['invoice_tele'];
		$invoice['bank'] = $order['invoice_bank'];
		$invoice['account'] = $order['invoice_account'];
		$invoice['uid'] = $this->visitor->info['id'];
		$invoice['uname'] = $this->visitor->info['username'];
		$invoice['add_time'] = date('Y-m-d H:i:s');
		$result = D('order')->add($data);
		if($result){
			D('quan')->where(array('id'=>$data['quan_id']))->save(array('used_time'=>date('Y-m-d H:i:s'),'used_uid'=>$this->uid,'used_status'=>1,'status'=>1));
			$invoice['order_id'] = $result;
			$data['invoice_status'] == 1 && D('order_invoice')->add($invoice);
			if($score_price > 0){
				$score_log['uid'] = $this->visitor->info['id'];
				$score_log['uname'] = $this->visitor->info['username'];
				$score_log['action'] = 'consume';
				$score_log['score'] = '-'.$data['score'];
				$score_log['add_time'] = date('Y-m-d H:i:s');
				$score_log['remark'] = '订单消费:'.$data['orderid'];
				D('score_log')->add($score_log);
				D('user')->where(array('id'=>$score_log['uid']))->setDec('score',$data['score']);
			}
		}
		foreach($cart_goods as $key=>$val){
			$item['uid'] = $data['uid'];
			$item['item_id'] = $val['item_id'];
			$item['order_id'] = $result;
			$item['orderid'] = $data['orderid'];
			$item['title'] = $val['item']['title'];
			$item['price'] = $val['price'];
			$item['nums'] = $val['nums'];
			$item['attr'] = $val['attr'];
			$item['sku_id'] = $val['sku_id'];
			$item['tuan_id'] = $val['tuan_id'];
			$item['name'] = $val['item']['name'];
			$item['sn'] = $val['item']['sn'];
			$item['spec'] = $val['item']['spec'];
			$item['score'] = $val['item']['score'];
			$item['add_time'] = $data['add_time'];
			D('order_item')->add($item);
			if($val['sku_id'] > 0){
				$sku_info = D('item_sku')->find($val['sku_id']);
				$nums = $sku_info['stock'] * $val['nums'];
			}else{
				$nums = $item['nums'];
			}
			D('item')->where(array('id'=>$item['item_id'],'stock' => array('gt',0)))->setDec('stock',$nums);
			D('item')->where(array('id'=>$item['item_id']))->setInc('sales',$nums);
			if($item['tuan_id'] > 0){
				D('tuan')->where(array('id'=>$item['tuan_id'],'stock' => array('gt',0)))->setDec('stock',$nums);
				D('tuan')->where(array('id'=>$item['tuan_id']))->setInc('sales',$nums);
			}
		}
		return $result;
	}


	protected function getUseQuan(){
		$quan = A('home/quan');
		$list = $quan->couponList($this->visitor->info['id'],0,array(),true);
		return $list;
	}


}