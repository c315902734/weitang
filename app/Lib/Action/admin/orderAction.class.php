<?php

/**
 * 订单管理
 * @package api
 */
class orderAction extends backendAction
{

    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('order');
        $this->status = array(
			0=>'未付款',
			1=>'已付款',
			2=>'已提货',
			3=>'待发货',
			4=>'已发货',
			5=>'已完成',
			7=>'待处理退款',
			8=>'已关闭/退款',
			9=>'已关闭/退款'
		);
    }

    protected function _search()
    {
        $map = array();
		$map['type'] = 0;
        ($orderid = $this->_request('orderid', 'trim')) && $map['orderid'] = array('like', '%'.$orderid.'%');
        //($uname = $this->_request('uname', 'trim')) && $map['uname'] = array('like', '%'.$uname.'%');
        ($uname = $this->_request('uname', 'trim')) && $map['uname'] = $uname;
        ($price_min = $this->_request('price_min', 'trim')) && $map['total'][] = array('egt', $price_min);
        ($price_max = $this->_request('price_max', 'trim')) && $map['total'][] = array('elt', $price_max);
        ($pays = $this->_request('pays', 'trim')) && $map['pays'] = array('eq', $pays);
        $dev = $this->_request('dev', 'intval', '-1');
        if($dev >= 0){
            $map['dev'] = array('eq', $dev);
        }
        $status = $this->_request('status', 'intval', -1);
        if($status >= 0){
        	if($status == 12){
        		$map['status'] = array('in', '3,4,5');
        	} else {
            	$map['status'] = array('eq', $status);
            }
        }
        $pays_status = $this->_request('pays_status', 'intval', -1);
        if($pays_status >= 0){
            $map['pays_status'] = array('eq', $pays_status);
        }
		$lottery = $this->_request('lottery', 'intval', -1);
        if($lottery >= 0){
            $map['lottery'] = array('eq', $lottery);
        }
        ($express_type = $this->_request('express_type', 'trim')) && $map['express_type'] = array('eq', $express_type);
		$goods_name = $this->_request('goods_name', 'trim');
		$goods_sn = $this->_request('goods_sn', 'trim');
		if($goods_name || $goods_sn){
			if($goods_sn){
				$order_ids = $this->_getGoodsOrderIds($goods_name, $goods_sn);
			} else {
				$order_ids = $this->_getGoodsOrderIds($goods_name);
			}
			if($order_ids){
				$map['id'] = array('in',$order_ids);
			} else {
				$map['id'] = 0;
			}
		}
        
		($addr_name = $this->_request('addr_name', 'trim')) && $map['addr_name'] = array('like', '%'.$addr_name.'%');
		($addr_tele = $this->_request('addr_tele', 'trim')) && $map['addr_tele'] = array('eq', $addr_tele);
		($suse_time = $this->_request('suse_time', 'trim')) && $map['add_time'][] = array('egt', $suse_time);
        ($euse_time = $this->_request('euse_time', 'trim')) && $map['add_time'][] = array('elt', $euse_time);
        $this->assign('search', array(
            'orderid' => $orderid,
            'uname' => $uname,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'pays' => $pays,
            'status' => $status,
            'goods_name' => $goods_name,
            'goods_sn'	=> $goods_sn,
			'addr_name' => $addr_name,
			'addr_tele' => $addr_tele,
			'suse_time' => $suse_time,
			'euse_time' => $euse_time,
			'express_type' => $express_type,
			'pays_status'	=> $pays_status,
			'lottery'	=> $lottery,
			'dev'		=> $dev,
        ));
        //$map['is_del'] = 0;
        //print_r($map);
        return $map;
    }

	public function index()
    {
        $map = $this->_search();
		if($this->_request('export') != ''){
			$result = $this->_list($this->_mod, $map,'', '', '*', 2000);
    		$list = $result['list'];
			$this->order_down($list);
		}else{
			$result = $this->_list($this->_mod, $map);
    		$list = $result['list'];
		}
		/*if($search == '导入'){
			$handle = @fopen("./a.txt", "r");
			if ($handle) {
			    while (!feof($handle)) {
			        $buffer = fgets($handle, 4096);
			        $val = explode('	', $buffer);
			        if(strpos($val[6], ',')){
						$lng_lat = explode(',', $val['6']);
					} elseif (strpos($val[6], '，')){
						$lng_lat = explode('，', $val['6']);
					}
			        $arr = array(
						'title'		=> $val[0],
						'province'	=> $val[1],
						'city'		=> $val[2],
						'area'		=> $val[3],
						'province_id'	=> 30,
						'city_id'		=> 382,
						'area_id'		=> 3228,
						'address'	=> $val[4],
						'tele'		=> $val[5],
						'lng'		=> $lng_lat[0],
						'lat'		=> $lng_lat[1],
						'remark'	=> $val[7].','.$val[8],
					);
					D('shop')->add($arr);
			    }
			    fclose($handle);
			}
			echo '成功';die;
		}*/

		$goods_str_one = '';
		$goods_str = '';
		$goods_subtotal = 0;
		$status = $this->status;
		$pays_name = array(
			1=>'货到付款',
			2=>'网银在线',
			3=>'支付宝',
			4=>'微信支付',
			5=>'信用卡支付',
			6=>'余额支付',
		);
		$express_list = array(
			1=>'快递物流',
			2=>'门店送货',
			3=>'门店自取'
		);
		foreach($list as $k=>$v){
			/*
			if($v['quan_id']){
				$list[$k]['quan'] = D('quan')->where(array('id'=>$v['quan_id']))->find();
			}
			*/
			$goods_list = D('order_item')->where('order_id = '.$v['id'])->select();
			$goods_str_one = '';
			$goods_str = '';
			$goods_subtotal = 0;
			foreach($goods_list as $gk=>$gv){
				$goods_list[$gk]['img'] = D('item')->where('id = '.$gv['item_id'])->getField('img');
				
				if($gk == 0){
					$goods_str_one .= '<td class="order_item" align="center" width="5%">'.$gv['id'].'</td>
							<td class="order_item" align="center" width="8%"><img src="'.attach($goods_list[$gk]['img'], 'assets').'" width="40" /></td>
							<td class="order_item" width="22%">'.$gv['title'].'<br /> '.'('.$gv['skus'].')</td>';
					
					if($gv['is_refund']==1){
						$goods_str_one .= '<b style="color:red;">已申请退款</b><br />';
					}
					$goods_str_one .= '</td><td class="order_item" width="6%">￥'.$gv['price'] .'元</td>
							<td class="order_item" width="4%" align="center">'.$gv['nums'].'</td>';
				}else{
					$goods_str .= '<tr>
							<td class="order_item" align="center" width="5%">'.$gv['id'].'</td>
							<td class="order_item" align="center" width="8%"><img src="'.attach($goods_list[$gk]['img'], 'assets').'" width="40" /></td>
							<td class="order_item" width="22%">'.$gv['title'].'<br /> '.$gv['spec'].'('.$gv['attr'].')</td>
							<td class="order_item" width="8%" align="center">'.$status[$v['status']].'<br/>';
					
					if($gv['is_refund']==1){
						$goods_str .= '<b style="color:red;">已申请退款</b><br />';
					}
					$goods_str .= '</td><td class="order_item" width="6%">￥'.$gv['price'] .'元</td>
							<td class="order_item" width="6%" align="center">'.$gv['nums'].'</td>';
				}
				$goods_subtotal += $gv['price'] *$gv['nums'];
			}

			$list[$k]['goods_str_one'] = $goods_str_one;
			$list[$k]['goods_str'] = $goods_str;
			$list[$k]['goods_num'] = count($goods_list);
			$list[$k]['status_str'] = $status[$v['status']];
			$list[$k]['express_type'] = $express_list[$v['express_type']];
			$list[$k]['total_price'] = sprintf ("%01.2f", $list[$k]['prices'] + $list[$k]['express']);
			$list[$k]['pays_name'] = $pays_name[$v['pays']];
			if($v['status'] == 0){
				//3天内未付款的订单自动关闭
				$where = array(
		            'add_time' => array('elt', date('Y-m-d H:i:s', time() - 3 * 24 * 3600)),
					'status' => 0,
					'id' => $v['id'],
		        );
		        D('order')->where($where)->save(array('status' => 9));
			}
		}
        $this->assign('list', $list);
        $this->assign('list_table', true);

        $this->display();
    }

    public function order_down($list){
    	$pays = array(
			'1' => '货到付款',
			'2' => '银联支付',
			'3' => '支付宝',
			'4' => '微信支付',
			'5' => '信用卡支付'
		);
		$lottery = array(
			0 => '未抽奖',
			1 => '已抽奖',
			2 => '升级成功',
			9 => '升级失败',
		);
    	$status = $this->status;

		$data = array();
		$i=0;
	    foreach($list as $k=>$row){
	    	$order_item = D('order_item')->where(array('orderid'=>$row['orderid']))->select();
	    	$user = D('user')->field('id,username')->where(array('id'=>$row['uid']))->find();
	    	foreach($order_item as $val){
		        $data[$i]['id']    = $row['id'];
		        $data[$i]['orderid']    = $row['orderid']."(订单号)";
		        $data[$i]['uid']        = $user['id'];
		        $data[$i]['uname']      = $row['uname'];
		        $data[$i]['total']      = $row['total'];
		        $data[$i]['prices']     = $row['prices'];
		        $data[$i]['lottery_price']    = $row['lottery_price'];
		        $data[$i]['lottery_total']    = $row['lottery_total'];
		        $data[$i]['express_name']     = $row['express_name'];
		        $data[$i]['express_sn']       = $row['express_sn'];
		        $data[$i]['express_time']     = $row['express_time'];
		        $data[$i]['addr_name']        = $row['addr_name'];
		        $data[$i]['addr_tele']        = $row['addr_tele'].'(电话)';
		        $data[$i]['addr']             = $row['addr_province'].$row['addr_city'].$row['addr_area'].$row['addr_address'].$row['addr_zipcode'];
		        $data[$i]['lottery']          = $lottery[$row['lottery']];
		        $data[$i]['lottery_no']       = $row['lottery'] == 0 ? '' : ($row['lottery_no'] == 0 ? '偶' : '奇');
		        $data[$i]['lottery_expect']   = $row['lottery_expect'];
		        $data[$i]['lottery_time']     = date('Y/m/d H:i:s',$row['lottery_time']);
		        $data[$i]['item_id']          = $val['id'];
		        $data[$i]['item_title']       = $val['title'];
		        $data[$i]['item_num']         = $val['nums'];
		        $data[$i]['item_skus']         = $val['skus'];
				$data[$i]['status']           = $status[$row['status']];
				$data[$i]['add_time']         = date('Y/m/d H:i:s',strtotime($row['add_time']));
		        $i++;
	        }
	    }
        // 引入excel类文件，将订单用excel文件导出来
	    Vendor('excelClass.excelclass');
		$excel = new excelClass();
		$excel->echoOrderFile('订单'.date('YmdHis').'.xls',$data);
		exit;
    }

    public function sent_order()
    {
    	$type = $this->_request('type', 'trim', 'sent_order');
	    $this->assign(compact('type'));
    	if(IS_POST){
    		$id = $this->_request('id', 'intval');
    		$nums = $this->_request('nums', 'intval');
    		$lot = $this->_request('lot', 'trim');
    		$date = $this->_request('date', 'trim');
    		$codes = $this->_request('codes', 'trim');
    		$count = count($nums);
    		$info = D('order_item')->where(array('id'=>$id))->field('uid,item_id,order_id,orderid,spec,express_id,title,name,sn,price,attr,remark,status,sku_id,tuan_id,is_refund')->find();
    		$mod = D('order_sku');
    		for($i=0;$i<$count;$i++){
    			$data = array_merge($info, array(
		                'nums'		=> $nums[$i],
		                'lot'      	=> $lot[$i],
		                'date'    	=> $date[$i],
		                'codes'		=> $codes[$i],
		                'add_time' 	=> date('Y-m-d H:i:s'),
		            )
		        );
		        $mod->add($data);
		        unset($data);
    		}
    		$this->ajaxReturn(1, L('operation_success'), '', $type);
    	} else {
	    	$id = $this->_request('id', 'intval');
	    	$info = D('order_item')->where(array('id'=>$id))->find();
	    	if($type == 'sent_order'){
	    		$info['subtotal'] += $info['price'] * $info['nums'];
	    	} else {
	    		if($info['sku_id']){
	    			$sku_nums = D('item_sku')->where(array('id'=>$info['sku_id']))->getField('stock');
	    			if($sku_nums){
						$info['nums'] = $sku_nums * $info['nums'];
					}
				}
	    	}
	    	
	    	$this->assign(compact('info'));
	    	$response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
    	}
    }

    public function edit_order_sku()
    {
    	$id = $this->_request('id', 'intval');
	    $this->assign(compact('id'));
    	if(IS_POST){
    		$lot = $this->_request('lot', 'trim');
    		$date = $this->_request('date', 'trim');
			$codes = $this->_request('codes', 'trim');
			$data = array(
                'lot'      	=> $lot,
                'date'    	=> $date,
                'codes'		=> $codes
            );
	        D('order_sku')->where(array('id'=>$id))->save($data);
    		$this->ajaxReturn(1, L('operation_success'), '', 'edit_order_sku');
    	} else {
	    	$info = D('order_sku')->where(array('id'=>$id))->find();
	    	if($type == 'sent_order'){
	    		$info['subtotal'] += $info['price'] * $info['nums'];
	    	} else {
	    		if($info['sku_id']){
	    			$sku_nums = D('item_sku')->where(array('id'=>$info['sku_id']))->getField('stock');
	    			if($sku_nums){
						$info['nums'] = $sku_nums * $info['nums'];
					}
				}
	    	}
	    	
	    	$this->assign(compact('info'));
	    	$response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
    	}
    }

    public function _before_insert($data)
    {
		$order_uid = $this->_request('order_uid', 'intval');
		if($order_uid){
			$user = D('user')->where('id='.intval($order_uid))->field('id,username')->find();
			$data['uid'] = intval($user['id']);
			$data['uname'] = strval($user['username']);
		}
		$data['orderid'] = date('Ymd').$this->strRand(6,'number').$order_uid;
		
		if(!$data['add_time']){
			$data['add_time'] = date('Y-m-d H:i:s', time());
		}
		
		if(!$data['prices']){
			$data['prices'] = $date['prices'];
		}
		
		//扣除积分
		$score = intval($data['score']);
		if($score){
			//判断下单用户积分是否够扣除
			$user_score = D('user')->where('id='.$data['uid'])->getField('score');
			if($user_score >= $score){
				//扣除积分
				D('user')->where('id='.$data['uid'])->setDec('score', $score);
				//写记录
				$username = D('user')->where('id='.$data['uid'])->getField('username');
				
				//获取用户的总积分和贡献值
				$user = D('user')->where('id='.$data['uid'])->field('score')->find();
				
				$score_data = array(
					'uid' => $data['uid'],
					'uname' => $username,
					'action' => 'consume',
					'score' => -$score,
					'add_time' => date('Y-m-d H:i:s', time()),
					'remark' => '用户'.$username.'的积分数量'.$user['score']
				);
				D('score_log')->add($score_data);
			} else {
				$this->error('用户积分少于订单需要扣除的积分');die;
			}
		}
		
        $goods_id = $this->_request('goods_id');
        $nums = $this->_request('nums');
        $total = 0;
        foreach($goods_id as $v){
            //查询订单商品信息
            $item = D('item')->where('id='.$v)->field('id,title,price,stock,stime,etime')->find();
            //如果商品营销时间控制大于0
            if($item['sale_hours'] > 0 ){
                if(time() >= strtotime($item['stime']) && time() <= strtotime($item['etime'])){
                    if($item['stock'] > 0){
                        if($nums[$v] > $item['stock']){
                            $this->error('商品'.$item['title'].'剩余数量不足'.$nums[$v].'个！');
                        }
                        //计算订单总价格
                        $total += $item['price'] * $nums[$v];
                    } else {
                        $this->error('商品'.$item['title'].'已经在规定时间内卖完了！');
                    }
                } else {
                    //计算订单总价格
                    $total += $item['price'] * $nums[$v];
                }
            } else {
                //计算订单总价格
                $total += $item['price'] * $nums[$v];
            }
        }
        $data['prices'] = $total;
        $data['total'] = $total;
    	return $data;
    }

	/**
	 * 生成随机字符串
	 * $len 长度
	 * $type = number/letter/all/capall 纯数字/字母/混搭/混搭字母大小写
	 */
	protected function strRand($len=8,$type='capall')
	{
		$number = '0123456789';
		$letter = 'abcdefghijklmnopqrstuvwxyz';
		$caps = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		switch($type){
			case 'capall':
				$str = $number.$letter.$caps;
				break;
			case 'number':
				$str = $number;
				break;
			case 'letter':
				$str = $letter;
				break;
			case 'all':
				$str = $number.$letter;
				break;
			default:
				$str = $number.$letter.$caps;
		}
		$text = '';
		for($i=0;$i<$len;$i++){
			$n = rand(0,strlen($str));
			if($n == strlen($str)) $n--;
			$text .= substr($str,$n,1);
		}
		return $text;
	}
	
	//插入商品,子订单
    public function _after_insert($id)
    {
		$order_info = D('order')->where('id = '.$id)->find();
		$goods_id = $this->_request('goods_id');
		$nums = $this->_request('nums');
		$price = $this->_request('price');

        $total = 0;
		foreach($goods_id as $v){
			if($v){
				$data['good_id']     = $v;
				$data['order_id']    = $id;
				$data['orderid']     = $order_info['orderid'];
				$data['title']       = D('item')->where(array('id'=>$v))->getField('title');
				$data['price']       = $price[$v];
				$data['num']         = $nums[$v];
				$data['remark']      = $order_info['remark'];
				$data['add_time']    = $order_info['add_time'];
				$data['uid']         = $order_info['uid'];
				$data['uname']       = $order_info['uname'];
				D('order_item')->add($data);
                //累计用户预定数量
                D('user')->where('id='.$data['uid'])->setInc('orders');
			}
		}
    }

    public function _before_update($data)
    {
        $order_uid = $this->_request('order_uid', 'intval');
		if($order_uid){
			$user = D('user')->where('id='.intval($order_uid))->field('id,username')->find();
			$data['uid'] = intval($user['id']);
			$data['uname'] = strval($user['username']);
		}
    	return $data;
    }

    public function delete()
    {
        $mod = D($this->_name);
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
            if (false !== $mod->where(array('id'=>array('in',$ids)))->setField('is_del', 1)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'),U($this->_name.'/index'));
            }
        } else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
            $this->error(L('illegal_parameters'),U($this->_name.'/index'));
        }
    }

	/**
	 * 根据商品获得订单
	 */
	protected function _getGoodsOrderIds($goods_name, $goods_sn)
	{
		$where = array();
		if($goods_name){
			$where['name'] = array('like','%'.$goods_name.'%');
		}
		if($goods_sn){
			$where['sn'] = array('like','%'.$goods_sn.'%');
		}
		$goods_list = D('order_item')->field('id')->where($where)->select();

		$_goods_ids = array();
		foreach($goods_list as $k=>$v){
			$_goods_ids[] = $v['id'];
		}
		if($_goods_ids){
			return implode(',',$_goods_ids);
		} else {
			return '';
		}
	}
	
	/**
	 * 获取订单信息
	 */

	protected function _getOrderInfo($ids)
	{
		$order_list = D('order')->where(array('id'=>array('in',$ids)))->relation(true)->select();
		foreach($order_list as $k=>$order){
			$order_list[$k]['ratio'] = $this->_getOrderPays($order);
		}
		return $order_list;
	}

	/**
	 * 获取订单结算2014/7/11信息
	 * 订单应付金额 = 商品单价*商品数量-非退款  *（应收结算比例 - 应付结算比例 ）
	 * 订单应收金额 = 实际消费金额  * 参考结算比例
	 * 总结算金额   = 应收 +应付
	 */
	protected function _getOrderPays($order)
	{
		//初始化总额
		$total = array(
			'total_in' =>0, //订单应付金额
			'total_out'=>0, //订单应收金额
			'amount'   =>0, //总结算金额
		);
		$goods_list = $order['goods_list'];
		if(!$goods_list){
			return $total;
		}
		if($order['status'] != 3){
			return false;
		}
		foreach($goods_list as $goods){
			$ratio = D('item')->field('per,rate')->where(array('id'=>$goods['good_id']))->find();
			$rate = $ratio['rate'] * 0.01;
			$per = $ratio['per'] * 0.01;
			$total['total_out'] += $goods['nums'] * $goods['price'] * (($rate - $per) > 0 ? ($rate - $per) : ($rate - $per) * -1);
		}
		$total['total_in'] = $order['price'] ;
		return $total;
	}

    /**
     * ajax检测会员是否存在
     */
    public function ajax_check_name()
    {
        $name = $this->_get('username', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name, $id)) {
            $this->ajaxReturn(0, '该会员已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

    /**
     * ajax检测邮箱是否存在
     */
    public function ajax_check_email()
    {
        $name = $this->_get('email', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->email_exists($name, $id)) {
            $this->ajaxReturn(0, '该邮箱已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

    public function ajax_check_tele()
    {
        $tele = $this->_get('tele', 'trim');
        $id = $this->_get('id', 'intval');
        $where = compact('tele');
        if (!empty($id)) {
            $where['id'] = array('neq', $id);
        }
        if ($this->_mod->where($where)->count()) {
            $this->ajaxReturn(0, '该手机已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

	public function _before_edit()
	{
		$id = $this->_request('id','intval');
		$order_info = D('order')->where('id = '.$id)->relation(true)->find();
		$goods_list = D('order_item')->where('order_id = '.$id)->select();
		foreach($goods_list as $gk=>$gv){
			$goods_list[$gk]['price'] = $gv['price'];
			$goods_list[$gk]['subtotal'] += $gv['price'] *$gv['nums'];
			$is_sent = D('order_sku')->where(array('item_id'=>$gv['item_id'], 'order_id'=>$gv['order_id']))->count();
			$goods_list[$gk]['is_sent'] = $is_sent;

			$item = D('item')->field('title_up,price_up,img_up,img')->find($gv['item_id']);
			$goods_list[$gk]['title_up'] = $item['title_up'];
			$goods_list[$gk]['price_up'] = $item['price_up'];
			$goods_list[$gk]['img_up'] = $item['img_up'];
			$goods_list[$gk]['img'] = $item['img'];
		}
        $this->assign('goods_list', $goods_list);

        $goods_sku_list = D('order_sku')->where('order_id = '.$id)->select();
		foreach($goods_sku_list as $key=>$val){
			$goods_sku_list[$key]['price'] = $val['price'];
			$goods_sku_list[$key]['subtotal'] += $val['price'] *$val['nums'];
		}
        $this->assign('goods_sku_list', $goods_sku_list);
        $type = $this->_request('type','trim','order');
        $this->assign('type',$type);

		$userinfo = D('user')->where('id = '.intval($order_info['uid']))->find();
		$userinfo['level_name'] = D('user_level')->where(array('id'=>$userinfo['level_id']))->getField('title');
		$this->assign('userinfo', $userinfo);
		//0：未付款，1：已付款，2:已提货 3：待发货，4：已发货，5：已成功，8已退款，9：已取消
		$status = $this->status;
		$express_type_name = array(
			1=>'快递物流',
			2=>'门店送货',
			3=>'门店自取'
		);
		$addr_tele = $order_info['addr_tele'];
		//判断是否匿名
		if($order_info['addr_show'] == 1){
			//隐藏手机号部分数字
			$string = $order_info['addr_tele'];
			$pattern = "/(1\d{1,2})\d\d(\d{2,2})/";
			$replacement = "\$1****\$3";
			$addr_tele =  preg_replace($pattern, $replacement, $string);
		}

		/*if($order_info['express_name']){
			$this->assign('express_name',$order_info['express_name']);
		} else {*/
			$express_name = D('express')->where(array('code'=>$order_info['express_code']))->getField('express_name');
			$this->assign('express_name',$express_name);
		//}
		//$quan_info = D('quan')->where(array('id'=>$order_info['quan_id']))->field('id,code,type,title,price,man_price,man_sale,max')->find();
		$this->assign('quan_info', $quan_info);

		//$invoice = D('order_invoice')->where(array('order_id'=>$order_info['id']))->find();
		$this->assign('invoice', $invoice);

		$this->assign('order_info', $order_info);
		$this->assign('addr_tele', $addr_tele);
        $this->assign('status', $status);
        $this->assign('express_type_name', $express_type_name);
		$this->assign('list_table', true);
	}

	//付款
	public function order_pay()
	{
		if (IS_POST) {
			//确认基础数据
			$id = $this->_request('id', 'intval');
			$arr['pays_price']  = $this->_request('pays_price');
			$arr['pays']        = $this->_request('pays', 'intval');
			$arr['pays_status'] = $this->_request('pays_status', 'intval');
			$arr['pays_time']   = $this->_request('pays_time');
			$arr['pays_sn']     = $this->_request('pays_sn');
			//先查询此订单状态是否为已付款
			$data = $this->_mod->where('id='.$id.' AND status=0')->find();
			if (false == $data || $arr['pays_price']<1) {
				IS_AJAX && $this->ajaxReturn(0, '订单状态或消费金额错误');
				$this->error($this->_mod->getError());
			}
			//0：未付款，1：已付款，2:已发货 3：已确认，4：已评价，5：已成功，8已退款，9：已取消
			$arr['status'] = $item_arr['status'] = 1;
			if (false !== $this->_mod->where('id='.$id)->save($arr)) {
				//更新商品消费状态
				D('order_item')->where('order_id='.$id)->save($item_arr);
				$order_items = D('order_item')->where('order_id='.$id)->field('item_id,nums')->select();
                foreach($order_items as $val){
                    //库存递减
                    //D('item')->where('id='.$val['item_id'])->setDec('stock', $val['num']);
                }
				IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_pay');
				$this->success(L('operation_success'));
			} else {
				IS_AJAX && $this->ajaxReturn(0, '订单状态无法操作');
				$this->error(L('operation_failure'));
			}
        }else{
			$this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
		}
	}

	//修改订单运费
	public function order_express()
	{
		$id = $this->_request('id', 'intval');
		if (IS_POST) {
			//确认基础数据
			$arr['express']  = $this->_request('express');
			//先查询此订单状态是否为已付款
			$count = $this->_mod->where('id='.$id.' AND status=0')->count();
			if (!$count) {
				IS_AJAX && $this->ajaxReturn(0, '订单状态错误');
				$this->error($this->_mod->getError());
			}
			//得到订单商品金额修改订单总金额
			$order = $this->_mod->where(array('id'=>$id))->field('prices,score,quan_price')->find();
			$arr['total'] = $order['prices'] + $arr['express'] - $order['quan_price'] - ($order['score']/100);
			if (false !== $this->_mod->where('id='.$id)->save($arr)) {
				IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_express');
				$this->success(L('operation_success'));
			} else {
				IS_AJAX && $this->ajaxReturn(0, '订单状态无法操作');
				$this->error(L('operation_failure'));
			}
        }else{
        	$info = $this->_mod->where(array('id'=>$id))->field('express')->find();
        	$this->assign('info', $info);
			$this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
		}
	}

    //发货
	public function order_deliver()
	{
		if (IS_POST) {
			//确认基础数据
			$id = $this->_request('id', 'intval');
			$arr['express']          = $this->_request('express');
			$arr['express_time']  	 = $this->_request('express_time');
			$arr['express_sn']  	 = $this->_request('express_sn');
			$arr['express_remark']   = $this->_request('express_remark');
			$arr['express_code']   = $this->_request('express_code');
			$arr['express_name'] = D('express')->where(array('express_code'=>$arr['express_code']))->getField('express_name');
			//先查询此订单状态是否为已提货
			$data = $this->_mod->where('id='.$id.' AND (status = 2 || status = 3 || status = 4)')->find();
			if (false == $data) {
				IS_AJAX && $this->ajaxReturn(0, '订单状态或消费金额错误');
				$this->error($this->_mod->getError());
			}
			//0：未付款，1：已付款，2:已提货 3：待发货，4.已发货. 5：已成功，8已退款，9：已取消
			$arr['status'] = 4;
			if (false !== $this->_mod->where('id='.$id)->save($arr)) {
				//快递接口
				Vendor('kuaidi100.kuaidi100');
				$kuaidi = new kuaidi100();
				$kuaidi->setConfig();
				$kuaidi->subscription($arr['express_code'],$arr['express_sn'],$data['addr_province'].$data['addr_city'].$data['addr_area']);
				IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_deliver');
				$this->success(L('operation_success'));
			} else {
				IS_AJAX && $this->ajaxReturn(0, '订单状态无法操作');
				$this->error(L('operation_failure'));
			}
        }else{
        	$ex_list = D('express')->select();
        	$this->assign('ex_list', $ex_list);
        	
			$this->assign('open_validator', true);

			$id = $this->_request('id', 'intval');
			$info = D('order')->where(array('id'=>$id))->field('express, express_code,express_time, express_sn, express_remark')->find();

			$time = date('Y-m-d H:i:s', time());
			if($info['express_time']){
				$time = $info['express_time'];
			}
        	$this->assign('time', $time);

			$this->assign('info', $info);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
		}
	}
	
	/**
	 * 订单确认
	 * 1、保存确认时间
	 * 2、支付金额 + 消费金额,返还下单用户相应积分数值 如 支付金额 =20,消费金额=200,即返还用户220积分
	 * 3、支付金额 + 消费金额,返还订单表中:is_enter = 0的积分人相应的积分：公式为=用户等级/10 *3%*消费金额
	 */
	public function confirm()
	{
		if (IS_POST) {
			//确认基础数据
			$id = $this->_request('id', 'intval');
			//查询此订单状态是否运行订单关闭
			$data = $this->_mod->where('id='.$id.' AND status!=4')->find();
			if (!$data){
				IS_AJAX && $this->ajaxReturn(0, '订单状态错误');
				$this->error($this->_mod->getError());
			}
			//查询子订单状态是否运行订单关闭
			$status = true;
			$goodslist = D('order_item')->where('order_id='.$data['id'].' AND is_refund=0')->select();
			if($goodslist){
				$status = false;
				foreach($goodslist AS $k=>$v){
					$idarr[] = $v['id'];
				}
			}
			if($status){
				IS_AJAX && $this->ajaxReturn(0, '子订单错误');
				$this->error($this->_mod->getError());
			}
			//0：未付款，1：已付款，2:已发货 3：已确认，4：已评价，5：已成功，8已退款，9：已取消
			$arr['status'] = 3;
			$arr['confirm_time'] = date('Y-m-d H:i:s');
			if (false !== $this->_mod->where(array('id' => $id))->save($arr)) {
				IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'confirm');
				$this->success(L('operation_success'));
			} else {
				IS_AJAX && $this->ajaxReturn(0, '订单状态无法操作');
				$this->error(L('operation_failure'));
			}
        } else{
			$this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch('confirm_alert');
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
		}
	}

	public function order_remark(){
		$id = $this->_request('id', 'intval');

		if(IS_POST){
			$remark_flag = $this->_request('remark_flag', 'intval');
			$remark_info = $this->_request('remark_info', 'trim');
			if($remark_flag){
				$adm_sess = session('admin');
				$data = array(
					'remark_flag'	=> $remark_flag, 
					'remark_info'	=> $remark_info,
					'remark_uname'	=> $adm_sess['username'],
					'remark_time'	=> date('Y-m-d H:i:s')
				);
				D('order')->where(array('id'=>$id))->save($data);
			}
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_remark');
		} else {
			$info = D('order')->where(array('id'=>$id))->field('remark_flag,remark_info,remark_uname,remark_time')->find();
			$this->assign(compact('info'));
			$response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
		}
	}

	public function order_del(){
		$id = $this->_request('id', 'intval');

		if(IS_POST){
			$is_del = $this->_request('is_del', 'intval');
			$data = array(
				'is_del'	=> $is_del, 
			);
			D('order')->where(array('id'=>$id))->save($data);
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_del');
		} else {
			$info = D('order')->where(array('id'=>$id))->field('is_del')->find();
			$this->assign(compact('info'));
			$response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
		}
	}
	
	//退款
	public function order_tuikuan()
	{
		//确认基础数据
		$id = $this->_request('id', 'intval');
		//查询此订单状态是否运行订单退款
		$data = $this->_mod->where('id='.$id.' AND status<3')->find();
		if (!$data){
			IS_AJAX && $this->ajaxReturn(0, '订单状态错误');
			$this->error($this->_mod->getError());
		}
		
		$arr['status'] = 7;
		if (false !== $this->_mod->where('id='.$id)->save($arr)) {
			$adm_sess = session('admin');
			$arr = array(
				'uid'			=> $data['uid'],
				'uname'			=> $data['uname'],
				'order_id'		=> $data['id'],
				'orderid'		=> $data['orderid'],
				'order_price'	=> $data['total'],
				'type'			=> '其他问题',
				'info'			=> '后台操作',
				'add_time'		=> date("Y-m-d H:i:s"),
			);
			D('order_refund')->add($arr);
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_close');
			$this->success(L('operation_success'));
		} else {
			IS_AJAX && $this->ajaxReturn(0, '订单状态无法操作');
			$this->error(L('operation_failure'));
		}
	}

	//关闭
	public function order_close()
	{
		//确认基础数据
		$id = $this->_request('id', 'intval');
		//查询此订单状态是否运行订单关闭
		$data = $this->_mod->where('id='.$id.' AND status<5')->find();
		if (!$data){
			IS_AJAX && $this->ajaxReturn(0, '订单状态错误');
			$this->error($this->_mod->getError());
		}
		if (IS_POST) {
			//查询子订单状态是否运行订单关闭
			$status = false;
			$goodslist = D('order_item')->where('order_id='.$id)->select();
			if($goodslist){
				foreach($goodslist AS $k=>$v){
					if($v['status']==0){
						$status = true;
						$idarr[] = $v['id'];
					}
				}
			}
			//0：未付款，1：已付款，2:已发货 3：已确认，4：已成功，5：已成功，8已退款，9：已取消
			$arr['status'] = 9;
			$arr['close_time'] = date('Y-m-d H:i:s');
			if (false !== $this->_mod->where('id='.$id)->save($arr)) {
				IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_close');
				$this->success(L('operation_success'));
			} else {
				IS_AJAX && $this->ajaxReturn(0, '订单状态无法操作');
				$this->error(L('operation_failure'));
			}
        }else{
			$this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
		}
	}

	//退款单列表
	public function refund()
	{
        $map = $this->refund_search();
        $model = D('order_refund');
        //排序
        $mod_pk = $model->getPk();
        if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        } else if (!empty($sort_by)) {
            $sort = $sort_by;
        } else if ($this->sort) {
            $sort = $this->sort;
        } else {
            $sort = $mod_pk;
        }
        if ($this->_request("order", 'trim')) {
            $order = $this->_request("order", 'trim');
        } else if (!empty($order_by)) {
            $order = $order_by;
        } else if ($this->order) {
            $order = $this->order;
        } else {
            $order = 'DESC';
        }
		$pagesize = 10;
        //如果需要分页
        if ($pagesize) {
            $count = $model->where($map)->count($mod_pk);
            $pager = new Page($count, $pagesize);
        }
        $select = $model->field($field_list)->where($map)->order($sort . ' ' . $order);
        $this->list_relation && $select->relation(true);
        if ($pagesize) {
            $select->limit($pager->firstRow . ',' . $pager->listRows);
            $page = $pager->show();
            $this->assign("page", $page);
        }
        $list = $select->select();
        $this->assign('list', $list);
        $this->display();
    }
	
	//订单退款
	public function order_refund()
	{
		if (IS_POST) {
			$id     = $this->_request('id', 'intval');
			$remark = $this->_request('remark', 'trim');
			$data =  D('order_item')->where('id='.$id.' AND status=1')->find();
			if (!$data){
				IS_AJAX && $this->ajaxReturn(0, '订单状态错误');
				$this->error($this->_mod->getError());
			}
			$goods['is_refund'] = 1;
			//退款
			D('order_item')->where('id='.$id)->save($goods);
			//插入退款表
			$arr['item_id']    = $data['item_id'];
			$arr['order_id']   = $data['order_id'];
			$arr['gid']        = $data['id'];
			$arr['remark']     = $remark;
			$arr['add_time']   = date('Y-m-d H:i:s');
			$arr['uid']        = $data['uid'];
			$arr['uname']      = $data['uname'];
			$arr['admin_id']   = $_SESSION['admin']['id'];
			$arr['admin_name'] = $_SESSION['admin']['username'];
			D('order_refund')->add($arr);
			D('order')->where(array('id'=>$data['order_id']))->setField('status', 7);
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_refund');
			$this->success(L('operation_success'));
        }else{
			$this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
		}
	}

	public function refund_confirm()
	{
		if (IS_POST) {
			$id = $this->_request('id', 'intval');
			$data =  D('order_refund')->where('id='.$id)->find();
			if (!$data){
				IS_AJAX && $this->ajaxReturn(0, '退款单错误');
				$this->error($this->_mod->getError());
			}
			$arr['status'] = 2;
			//退款
			D('order_refund')->where('id='.$id)->save($arr);
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'order_refund');
			$this->success(L('operation_success'));
        }else{
			$this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
		}
	}

	public function refund_delete()
	{
        $mod = D('order_refund');
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
            if (false !== $mod->delete($ids)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'));
            }
        } else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
            $this->error(L('illegal_parameters'));
        }
	}

	//订单详细
	public function search_order_info()
	{
		$id = $this->_request('orderid', 'trim');
		$id = $id ? $id : 0;
		$return = $this->_mod->where('orderid = '.$id)->find();
		if(!$return){
			$this->ajaxReturn(0, '订单编号错误');
			$this->error($this->_mod->getError());
		}else{
			$goods = D('order_item')->where('order_id = '.$return['id'])->select();
			foreach($goods as $key => $val){
				$goods[$key]['price'] = $val['price'];
				$goods[$key]['subtotal'] = $goods[$key]['price'] / $val['num'];
			}
			$return['goodslist'] = $goods;
			if ($return) {
				$this->ajaxReturn(1, L('operation_success'), $return);
			} else {
				$this->ajaxReturn(0, '订单商品错误');
				$this->error($this->_mod->getError());
			}
		}
		$this->ajaxReturn(0, '订单编号错误');
	}

	//订单商品
	public function search_item()
	{
		$title = $this->_request('goodsname', 'trim');
		$where['title'] = array('like', '%'.$title.'%');
		$goods_list = D('item')->where($where)->field('id,title')->select();
		$str = '';
		if($goods_list){
			foreach($goods_list as $k=>$v){
				$str .= '<option value="'.$v['id'].'">'.$v['title'].'</option>';
			}
		}
		echo $str;
	}

	//商品详细（添加订单中的添加商品要用到）
	public function search_item_info()
	{
		$id = $this->_request('goodsid', 'trim');
		$return = D('item')->where('id = '.$id)->find();
		$return['price'] = $return['price']/100;
		
		if($return['type'] == 1){
			$return['type2'] = '券';
		} elseif($return['type'] == 2){
			$return['type2'] = '特卖';
		} elseif($return['type'] == 3){
			$return['type2'] = '返利';
		} elseif($return['type'] == 4){
			$return['type2'] = '会员';
		} elseif($return['type'] == 5){
			$return['type2'] = '团购';
		} elseif($return['type'] == 6){
			$return['type2'] = '预订';
		} elseif($return['type'] == 7){
			$return['type2'] = '折扣';
		}
		
		if ($return) {
        	$this->ajaxReturn(1, L('operation_success'), $return);
        } else {
        	$this->ajaxReturn(0, L('operation_failure'));
        }
	}

	//退款搜索
	protected function refund_search()
	{
        $map = array();
		($id = $this->_request('id', 'trim')) && $map['id'] = array('eq', $id);
        ($order_id = $this->_request('order_id', 'trim')) && $map['order_id'] = array('like', '%'.$order_id.'%');
        ($uname = $this->_request('uname', 'trim')) && $map['uname'] = array('like', '%'.$uname.'%');
        ($stime = $this->_request('stime', 'trim')) && $map['add_time'] = array('gt', $stime);
        ($etime = $this->_request('etime', 'trim')) && $map['add_time'] = array('lt', $etime);
        ($admin_name = $this->_request('admin_name', 'trim')) && $map['admin_name'] = array('eq', $admin_name);
        ($status = $this->_request('status', 'trim')) && $map['status'] = array('eq', $status);
        $this->assign('search', array(
			'id' => $id,
            'order_id' => $order_id,
            'uname' => $uname,
            'etime' => $etime,
            'stime' => $stime,
            'admin_name' => $admin_name,
            'status' => $status,
        ));
        return $map;
    }

    public function order_wuliu(){
        $id = $this->_get('id','intval',0);
		$order = D('order')->field('express_code,express_sn')->find($id);

		$params = array('id'=>C('WULIU_KEY'),'com'=>$order['express_code'],'nu'=>$order['express_sn']);

		$url = "http://api.kuaidi.com/openapi.html?" . http_build_query($params);
		$express = file_get_contents($url);		
		$express = json_decode($express,JSON_UNESCAPED_UNICODE);
		if($express['success']){
			$list = $express['data'];
			$this->assign('list', $list); 
		}
		
		$response = $this->fetch();
		$this->ajaxReturn(1, '', $response);
    }

	protected function _count_search()
	{
        ($sadd_time = $this->_request('sadd_time', 'trim')) && $map['add_time'][] = array('elt', $sadd_time);
        ($eadd_time = $this->_request('eadd_time', 'trim')) && $map['add_time'][] = array('egt', $eadd_time);
        ($spays_time = $this->_request('spays_time', 'trim')) && $map['pays_time'][] = array('elt', $spays_time);
        ($epays_time = $this->_request('epays_time', 'trim')) && $map['pays_time'][] = array('egt', $epays_time);
        ($sexpress_time = $this->_request('sexpress_time', 'trim')) && $map['express_time'][] = array('elt', $sexpress_time);
        ($eexpress_time = $this->_request('eexpress_time', 'trim')) && $map['express_time'][] = array('egt', $eexpress_time);
		$this->assign('search', array(
			'sadd_time' => $sadd_time,
            'eadd_time' => $eadd_time,
            'spays_time' => $spays_time,
            'epays_time' => $epays_time,
            'sexpress_time' => $sexpress_time,
            'eexpress_time' => $eexpress_time,
        ));
		return $map;
	}
	//统计
    public function count(){
        $map = $this->_count_search();
        $model = D($this->_name);
		$search = $this->_request('search', 'trim');
        //排序
        $mod_pk = $model->getPk();
        if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        } else if (!empty($sort_by)) {
            $sort = $sort_by;
        } else if ($this->sort) {
            $sort = $this->sort;
        } else {
            $sort = $mod_pk;
        }
        if ($this->_request("order", 'trim')) {
            $order = $this->_request("order", 'trim');
        } else if (!empty($order_by)) {
            $order = $order_by;
        } else if ($this->order) {
            $order = $this->order;
        } else {
            $order = 'DESC';
        }
		$pagesize = 20;
        //如果需要分页
        if ($pagesize) {
            $count = $model->where($map)->count($mod_pk);
            $pager = new Page($count, $pagesize);
        }
        $select = $model->where($map)->order($sort . ' ' . $order);
        $this->list_relation && $select->relation(true);
        if ($pagesize) {
            $select->limit($pager->firstRow . ',' . $pager->listRows);
            $page = $pager->show();
            $this->assign("page", $page);
        }
        $list = $select->select(); //echo $model->getLastSql();
		$goods_str_one = '';
		$goods_str = '';
		$goods_subtotal = 0;
		$status = $this->status;
		$pays_name = array(
			1=>'货到付款',
			2=>'网银在线',
			3=>'支付宝',
			4=>'微信支付',
			5=>'信用卡支付'
		);
		foreach($list as $k=>$v){
			/*
			if($v['quan_id']){
				$list[$k]['quan'] = D('quan')->where(array('id'=>$v['quan_id']))->find();
			}
			*/
			$goods_list = D('order_item')->where('order_id = '.$v['id'])->select();
			$goods_str_one = '';
			$goods_str = '';
			$goods_subtotal = 0;
			foreach($goods_list as $gk=>$gv){
				$goods_list[$gk]['img'] = D('item')->where('id = '.$gv['item_id'])->getField('img');
				
				if($gk == 0){
					$goods_str_one .= '<td class="order_item" align="center" width="5%">'.$gv['id'].'</td>
							<td class="order_item" align="center" width="8%"><img src="'.attach($goods_list[$gk]['img'], 'assets').'" width="50" /></td>
							<td class="order_item" width="22%">'.$gv['title'].'</td>
							<td class="order_item" width="8%" align="center">'.$status[$v['status']].'<br/>';
					if($gv['status']==1 && $gv['is_refund']==0){
						//$goods_str_one .= '<b><a class="J_showdialog" href="javascript:;" data-uri="./?g=admin&m=order&a=order_refund&id='.$gv['id'].'" data-title="订单退款" data-id="order_refund" data-width="400">退款</a></b><br />';
					}
					if($gv['is_refund']==1){
						$goods_str_one .= '<b style="color:red;">已申请退款</b><br />';
					}
					$goods_str_one .= '</td><td class="order_item" width="6%">'.$gv['price'] .'元</td>
							<td class="order_item" width="6%">'.$gv['nums'].'</td>';
				}else{
					$goods_str .= '<tr>
							<td class="order_item" align="center" width="5%">'.$gv['id'].'</td>
							<td class="order_item" align="center" width="8%"><img src="'.attach($gv['img'], 'assets').'" width="50" /></td>
							<td class="order_item" width="22%">'.$gv['title'].'</td>
							<td class="order_item" width="8%" align="center">'.$status[$v['status']].'<br/>';
					if($gv['status']==1 && $gv['is_refund']==0){
						//$goods_str .= '<b><a class="J_showdialog" href="javascript:;" data-uri="./?g=admin&m=order&a=order_refund&id='.$gv['id'].'" data-title="订单退款" data-id="order_refund" data-width="400">退款</a></b><br />';
					}
					if($gv['is_refund']==1){
						$goods_str .= '<b style="color:red;">已申请退款</b><br />';
					}
					$goods_str .= '</td><td class="order_item" width="6%">'.$gv['price'] .'元</td>
							<td class="order_item" width="6%">'.$gv['nums'].'</td>';
				}
				$goods_subtotal += $gv['price'] *$gv['num'];
			}
			$list[$k]['goods_str_one'] = $goods_str_one;
			$list[$k]['goods_str'] = $goods_str;
			$list[$k]['goods_num'] = count($goods_list);
			$list[$k]['status_str'] = $status[$v['status']];
			$list[$k]['prices'] = $list[$k]['prices'];
			$list[$k]['pays_name'] = $pays_name[$v['pays']];
		}
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
    }

    public function _total_search(){
		$map = array();
		$city_id = $this->_request('city_id', 'intval');
		if($city_id){
			$city = D('city')->where(array('id'=>$city_id))->field('name,spid')->find();
			if($city['spid'] == 0){
				$spid = $city_id;
			} else {
				$spid = $city['spid'].$city_id;
			}
			$where['addr_province|addr_city'] = array('like', '%'.$city['name'].'%');
			$order_list = D('order')->where($where)->select();
			foreach($order_list as $k=>$v){
				$ids[] = $v['id'];
			}
			if($ids){
				$map['order_id'] = array('in', $ids);
			} else {
				$map['order_id'] = '-1';
			}
		}
		$this->assign('search', array(
			'city_id' 	=> $city_id,
			'spid'		=> $spid,
		));
		return $map;
	}

    public function total(){
		$pagesize = 20;
		$map = $this->_total_search();
		$map['is_refund'] = 0;
		$count = D('order_item')->where($map)->count();
		$pager = new Page($count, $pagesize);
    	$order_item = D('order_item')->where($map)->order('add_time desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
    	foreach($order_item as $key=>$val){
    		$order = D('order')->where(array('id'=>$val['order_id']))->field('addr_province,addr_city,add_time')->find();
    		$order_item[$key]['add_time'] = $order['add_time'];
    		$order_item[$key]['addr_province'] = $order['addr_province'];
    		$order_item[$key]['addr_city'] = $order['addr_city'];
    		$order_item[$key]['subtotal'] += $val['price'] *$val['nums'];
    		$order_item[$key]['subtotal'] = sprintf("%1\$.2f", $order_item[$key]['subtotal']);
			
			$order_item[$key]['qual'] = D('item')->where(array('id'=>$val['item_id']))->getField('qual');
    	}
		$page = $pager->show();
		$this->assign('page', $page);
    	$this->assign(compact('order_item'));
		$this->assign('list_table', true);
		$this->display();
	}

	public function total_down(){
		$map = $this->_total_search();
		$map['is_refund'] = 0;
    	$order_item = D('order_item')->where($map)->order('add_time desc')->select();
    	$data = array();
		$i=0;
    	foreach($order_item as $key=>$val){
    		$order = D('order')->where(array('id'=>$val['order_id']))->field('orderid,addr_province,addr_city,add_time')->find();
    		$data[$i]['orderid']	= $order['orderid'];
    		$data[$i]['add_time'] 	= $order['add_time'];
    		$data[$i]['addr'] 		= $order['addr_province'].$order['addr_city'];
    		$data[$i]['name'] 		= $val['name'].' '.$val['spec'].'('.$val['attr'].')';
    		$data[$i]['price']		= $val['price'];
    		$data[$i]['nums']		= $val['nums'];
    		$data[$i]['subtotal'] += $val['price'] *$val['nums'];
    		$data[$i]['subtotal'] = sprintf("%1\$.2f", $data[$i]['subtotal']);
			$data[$i]['qual'] = D('item')->where(array('id'=>$val['item_id']))->getField('qual');
			$i++;
   		}
   		Vendor('excelClass.excelclass');
		$excel = new excelClass();
		$excel->echoOrderTotalFile('销售汇总'.date('YmdHis').'.xls',$data);
		exit;
	}

	public function _detail_search(){
		$map = array();
		($orderid = $this->_request('orderid', 'trim')) && $map['orderid'] = array('like', '%'.$orderid.'%');
		($item_id = $this->_request('item_id', 'trim')) && $map['item_id'] = $item_id;
		($name = $this->_request('name', 'trim')) && $map['name'] = array('like', '%'.$name.'%');
		
		($lot = $this->_request('lot', 'trim')) && $map['lot'] = array('like', '%'.$lot.'%');
		$uname = $this->_request('uname', 'trim');
		if($uname){
			$ids = array();
			$user_list = D('user')->where(array('username'=>array('like', '%'.$uname.'%')))->select();
			
			foreach($user_list as $k=>$v){
				$ids[] = $v['id'];
			}
			if($ids){
				$map['uid'] = array('in', $ids);
			} else {
				$map['uid'] = -1;
			}
		}
	
		($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
		($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
		
		$this->assign('search', array(
			'orderid' 	=> $orderid,
			'name'		=> $name,
			'lot'		=> $lot,
			'uname'		=> $uname,
			'stime' 	=> $stime,
			'etime' 	=> $etime,
		));
		return $map;
	}

	public function detail(){
		$map = $this->_detail_search();
		$map['is_refund'] = 0;
		$pagesize = 20;
		$count = D('order_sku')->where($map)->count();
		$pager = new Page($count, $pagesize);
		$order_sku = D('order_sku')->where($map)->order('add_time desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
    	foreach($order_sku as $key=>$val){
    		$order = D('order')->where(array('id'=>$val['order_id']))->field('addr_province,addr_city,add_time,uname')->find();
    		$order_sku[$key]['add_time'] = $order['add_time'];
    		$order_sku[$key]['addr_province'] = $order['addr_province'];
    		$order_sku[$key]['addr_city'] = $order['addr_city'];
    		$order_sku[$key]['uname'] = $order['uname'];
    		$order_sku[$key]['qual'] = D('item')->where(array('id'=>$val['item_id']))->getField('qual');

    		$item = D('item')->where(array('id'=>$val['item_id']))->field(' docs')->find();
			$order_sku[$key]['docs'] = $item['docs'];
    	}
		$page = $pager->show();
		$this->assign('page', $page);
    	$this->assign(compact('order_sku'));
		$this->assign('list_table', true);
		$this->display();
	}

	public function detail_down(){
		$map = $this->_detail_search();
		$map['is_refund'] = 0;
    	$order_sku = D('order_sku')->where($map)->order('add_time desc')->select();
    	$data = array();
		$i=0;
    	foreach($order_sku as $key=>$val){
    		$order = D('order')->where(array('id'=>$val['order_id']))->field('orderid,add_time,uname')->find();
    		$data[$i]['orderid']	= $order['orderid'];
    		$data[$i]['add_time'] 	= $order['add_time'];
    		$data[$i]['lot'] 		= $val['lot'];
    		$data[$i]['date']		= $val['date'];	
    		$data[$i]['docs'] 		= $val['docs'];
    		$data[$i]['name'] 		= $val['name'].'('.$val['attr'].')';
    		$data[$i]['price']		= $val['price'];
    		$data[$i]['nums']		= $val['nums'];
    		$data[$i]['codes']		= $val['codes'];
    		$data[$i]['uname']		= $order['uname'];
			$data[$i]['qual'] = D('item')->where(array('id'=>$val['item_id']))->getField('qual');
			$i++;
   		}
   		Vendor('excelClass.excelclass');
		$excel = new excelClass();
		$excel->echoOrderDetailFile('销售明细'.date('YmdHis').'.xls',$data);
		exit;
	}

	public function search_order(){
		$map = array();
		($orderid = $this->_request('orderid', 'trim')) && $map['orderid'] = array('like', '%'.$orderid.'%');
		($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
		($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
		$status = $this->_request('status', 'intval', '-1');
		if($status >= 0){
			$map['status'] = $status;
		}
		
		$this->assign('search', array(
			'orderid' 	=> $orderid,
			'stime' 	=> $stime,
			'etime' 	=> $etime,
			'status' 	=> $status,
		));
		$map['is_del'] = 0;
		$status = $this->status;
		$express_list = array(
			1=>'快递物流',
			2=>'门店送货',
			3=>'门店自取'
		);
		$result = $this->_list($this->_mod, $map);
		foreach($result['list'] as $key=>$val){
			$result['list'][$key]['express_type'] = $express_list[$val['express_type']];
			$result['list'][$key]['status'] = $status[$val['status']];
		}
		$this->assign('page', $result['page']);
        $this->assign('list', $result['list']);
        $this->display();
	}

	public function export(){
		$map = array();
		($orderid = $this->_request('orderid', 'trim')) && $map['orderid'] = array('like', '%'.$orderid.'%');
		($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
		($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);
		$status = $this->_request('status', 'intval', '-1');
		if($status >= 0){
			$map['status'] = $status;
		}

		$result = $this->_list($this->_mod, $map, '', '', '*' ,'');

		$status = $this->status;
		$express_list = array(
			1=>'快递物流',
			2=>'门店送货',
			3=>'门店自取'
		);
		foreach($result['list'] as $key=>$val){
			$result['list'][$key]['express_type'] = $express_list[$val['express_type']];
			$result['list'][$key]['status'] = $status[$val['status']];
		}
		$data = array();
		$i=0;
	    foreach($result['list'] as $k=>$row){
	        $data[$i]['ordid']   	= $row['orderid']."(订单号)";
	        $data[$i]['express_type']	= $row['express_type'];
	        $data[$i]['express'] 	= $row['express'];
			$data[$i]['prices']		= $row['prices'];
	        $data[$i]['pays_price'] = $row['pays_price'];
	        $data[$i]['add_time'] 	= $row['add_time'];
	        $data[$i]['addr_name'] 	= $row['addr_name'];
	        $data[$i]['addr_tele']  = $row['addr_tele'];
	        $data[$i]['addr_address']   = $row['addr_address'];
	        $data[$i]['status']		= $row['status'];
	        $i++;
	    }
	    Vendor('excelClass.excelclass');
		$excel = new excelClass();
		$excel->echoSearchOrderFile('搜索订单'.date('YmdHis').'.xls',$data);
		exit;
	}
	 
}