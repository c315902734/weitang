<?php

/**
 * 订单管理
 * @package api
 */
class order_refundAction extends backendAction
{

    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('order_refund');
		$this->list_relation = true;
    }

    protected function _search()
    {
        $map = array();
        ($orderid = $this->_request('orderid', 'trim')) && $map['orderid'] = array('like','%'.$orderid.'%');
        ($uname = $this->_request('uname', 'trim')) && $map['uname'] = array('like','%'.$uname.'%');
    	($stime = $this->_request('stime', 'trim')) && $map['add_time'][] = array('egt', $stime);
		($etime = $this->_request('etime', 'trim')) && $map['add_time'][] = array('elt', $etime);

        $ids = array();
        $sn = $this->_request('sn', 'trim');
        $name = $this->_request('name', 'trim');
        if($name || $sn){
            if($sn){
                $where['sn'] = array('like', array('%'.$sn.'%'));
            }
            if($name){
                $where['name|title'] = array('like', array('%'.$name.'%'));
            }
            $order_item = D('order_item')->where($where)->field('order_id')->select();
            foreach($order_item as $key=>$val){
                $ids[$i] = $val['order_id'];
                $i++;
            }
            if($ids){
                $map['id'] = array('in',$ids);
            } else {
                $map['id'] = 0;
            }
        }

        $status = $this->_request('status', 'intval','-1');
        if($status >= 0){
            $map['status'] = $status;
        }
    	$this->assign('search', array(
    			'stime'      => $stime,
                'etime'      => $etime,
    			'orderid'    => $orderid,
                'uname'      => $uname,
                'status'     => $status,
                'name'       => $name,
                'sn'         => $sn,
    	));
    	return $map;
    }

    public function _before_edit()
    {
        $id = $this->_request('id', 'intval');
        $info = $this->_mod->where(array('id'=>$id))->field('refund_price, order_price')->find();
        $refund_price = $info['refund_price'];
        if($info['refund_price'] == 0){
            $refund_price = $info['order_price'];
        }
        $this->assign('refund_price', $refund_price);
    }

    public function _before_update($data){
        if($data['remark']){
            $data['admin_time'] = date('Y-m-d H:i:s');
            $adm_sess = session('admin');
            $data['admin_id']   = $adm_sess['id'];
            $data['admin_name'] = $adm_sess['username'];
        }
        return $data;
    }

    public function _after_update($id){
        $info = $this->_mod->where(array('id'=>$id))->field('status,order_id')->find();
        if($info['status'] == 2){
			$order = D('order')->where(array('id'=>$info['order_id']))->find();
			//修正中奖后退款差价
			$r_price = $order['lottery']==2 ? $order['lottery_total'] : $order['total'];
			D('user')->where(array('id'=>$order['uid']))->setInc('price',$r_price);
			D('price_log')->add(array(
				'uid' => $order['uid'],
				'uname' => $order['uname'],
				'price' => $r_price,
				'action' => 'order_refund',
				'add_time' => date('Y-m-d H:i:s'),
				'remark' => '订单'.$order['orderid'].'退款',
				'key_id' => $id,
			));
            D('order')->where(array('id'=>$info['order_id']))->setField('status', 8);
        }
    }

    public function check(){
        $id = $this->_request('id', 'intval');
        $this->assign('id', $id);
        if(IS_POST){
            $this->ajaxReturn(1, L('operation_success'), '', 'check');
        } else {
            $info = D('order_refund')->where(array('id'=>$id))->find();
            $order_sku = D('order_sku')->where(array('orderid'=>$info['orderid']))->select();
            $this->assign(compact('info', 'order_sku'));
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

    public function down_xls(){
        $map = $this->_search();
        $result = $this->_list($this->_mod, $map, '', '', '*' ,'');

        $status = array(
            0=>'未处理',
            1=>'已处理'
        );
        foreach($result['list'] as $key=>$val){
            if($val['admin_time'] == '0000-00-00 00:00:00'){
                $result['list'][$key]['admin_time'] = '';
            }
            $result['list'][$key]['status'] = $status[$val['status']];
        }
        $data = array();
        $i=0;
        foreach($result['list'] as $k=>$row){
            $data[$i]['orderid']    = $row['orderid']."(订单号)";
            $data[$i]['uname']      = $row['uname'];
            $data[$i]['order_price']= $row['order_price'];
            $data[$i]['type']       = $row['type'];
            $data[$i]['info']       = $row['info'];
            $data[$i]['add_time']   = $row['add_time'];
            $data[$i]['admin_name'] = $row['admin_name'];
            $data[$i]['admin_time'] = $row['admin_time'];
            $data[$i]['status']     = $row['status'];
            $i++;
        }
        Vendor('excelClass.excelclass');
        $excel = new excelClass();
        $excel->echoOrderRefundFile('退款订单'.date('YmdHis').'.xls',$data);
        exit;
    }
}