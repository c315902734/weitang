<?php

class payAction extends mbaseAction
{
    public function index()
    {
        $order_id = $this->_get('order_id', 'intval');
        $order    = D('order')->where(array('id' => $order_id))->find();
        $this->assign('order', $order);
        $this->display();
    }

    public function browser()
    {
        $this->assign('page', 'weixin_browser_page');
        $this->display();
    }

    public function alipay_index()
    {
        $order_id = $this->_request('order_id', 'intval', 0);

        if (is_weixin_browser()) {
            $this->assign('page', 'weixin_browser_page');
            $this->display('browser');
        }
        else {
            $order_info = D('order')->where(array('id' => $order_id))->find();
            $total      = $order_info['prices'] - $order_info['score'];
            $orderid    = $order_info['orderid'];

            if ($order_id == 0) {
                $this->_404();
            }

            $notify_url = full_url('pay/alipay_notify');
            $return_url = full_url('pay/alipay_return');
            //创建并调用支付宝支付行为
            vendor('alipay_v3.alipay');
            $alipay = new publicAlipayV3();
            $alipay->setConfig();
            $alipay->doPay($orderid, "订单" . $orderid, $total, $notify_url, $return_url);
        }
    }

    public function alipay_notify()
    {
        vendor('alipay_v3.alipay');
        $alipay = new publicAlipayV3();
        $alipay->setConfig();
        $resp    = $_POST;
        $orderid = $resp['out_trade_no']; //本地订单号
        if ($resp != false && !empty($orderid)) {
            $order = D('order')->where('orderid = ' . $orderid)->find();
            if ($order['status'] == 0) {
                $data['status']      = 1; //支付成功
                $data['pays']        = 3;
                $data['pays_time']   = date('Y-m-d H:i:s');
                $data['pays_status'] = 1;
                $data['pays_data']   = serialize($resp);
                $data['pays_price']  = $resp['total_fee'];
                $data['pays_sn']     = $resp['trade_no'];
                D('order')->where(array('orderid' => $orderid))->save($data);
                D('order_item')->where(array('orderid' => $orderid))->save(array('status' => 1));

                $res = D('order_item')->where(array('orderid' => $orderid))->select();
                foreach ($res as $key => $val) {
                    ItemService::update_stock($val['item_id'], $val['sku_id'], $val['nums']);
                }
            }
        }
        die('success');
    }

    public function alipay_return()
    {
        $info = D('order')->where(array('id' => $_GET['out_trade_no']))->find();
        $this->assign('order_sn', $info['orderid']);
        $this->assign('price', $info['total']);
        if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
            $status  = 1; //成功
            $orderid = $_GET['out_trade_no']; //本地订单号
            $order   = D('order')->where('orderid = ' . $orderid)->find();
            $this->assign('order', $order);
            if ($order['status'] == 0) {
                $data['status']      = 1; //支付成功
                $data['pays']        = 3;
                $data['pays_time']   = date('Y-m-d H:i:s');
                $data['pays_status'] = 1;
                $data['pays_data']   = _json_encode($_GET);
                $data['pays_price']  = $_GET['total_fee'];
                $data['pays_sn']     = $_GET['trade_no'];
                D('order')->where(array('orderid' => $orderid))->save($data);
                D('order_item')->where(array('orderid' => $orderid))->save(array('status' => 1));

                $res = D('order_item')->where(array('orderid' => $orderid))->select();
                foreach ($res as $key => $val) {
                    ItemService::update_stock($val['item_id'], $val['sku_id'], $val['nums']);
                }
            }
        }
        else {
            $status = 0; //失败
        }
        $this->assign('status', $status);
        $this->display();
    }

    public function user_apply_exchange_notify()
    {
        $batch_no = $_REQUEST['batch_no'];

        $success_data_list = $this->parse_pay_return_data($_REQUEST['success_details']);
        $fail_data_list    = $this->parse_pay_return_data($_REQUEST['fail_details']);
        $res               = array_merge($success_data_list, $fail_data_list);

        foreach ($res as $key => $val) {
            $where = [
                'id'       => $val['id'],
                'apply_sn' => $batch_no,
                'status'   => 0,
            ];
            if (D('user_apply')->where($where)->count() > 0) {
                $save_data            = [
                    'pay_status' => $val['return_status'] == "S" ? 1 : 2,
                    'pay_msg'    => $val['return_msg'],
                    'pay_sn'     => $val['pay_sn'],
                    'pay_time'   => $val['pay_time'],
                ];
                $save_data['status']  = $save_data['pay_status'];
                $save_data['pay_msg'] = $save_data['pay_status'] == 1 ? '积分兑换' : $val['return_msg'];

                D('user_apply')->where($where)->save($save_data);
                $this->handle_user_apply($val['id'], $save_data['pay_status']);
            }
        }
        exit('success');
    }

    public function member_apply_exchange_notify()
    {
        $batch_no = $_REQUEST['batch_no'];

        $success_data_list = $this->parse_pay_return_data($_REQUEST['success_details']);
        $fail_data_list    = $this->parse_pay_return_data($_REQUEST['fail_details']);
        $res               = array_merge($success_data_list, $fail_data_list);

        foreach ($res as $key => $val) {
            $where = [
                'id'       => $val['id'],
                'apply_sn' => $batch_no,
                'status'   => 0,
            ];
            if (D('member_apply')->where($where)->count() > 0) {
                $save_data            = [
                    'pay_status' => $val['return_status'] == "S" ? 1 : 2,
                    'pay_msg'    => $val['return_msg'],
                    'pay_sn'     => $val['pay_sn'],
                    'pay_time'   => $val['pay_time'],
                ];
                $save_data['status']  = $save_data['pay_status'];
                $save_data['pay_msg'] = $save_data['pay_status'] == 1 ? '货款提现' : $val['return_msg'];

                D('member_apply')->where($where)->save($save_data);
                $this->handle_member_apply($val['id'], $save_data['pay_status']);
            }
        }
        exit('success');
    }

    protected function parse_pay_return_data($data)
    {
        $result = [];
        $res    = explode('|', $data);
        foreach ($res as $val) {
            if (empty($val)) {
                continue;
            }
            $item          = explode('^', $val);
            $id            = $item[0];
            $pay_account   = $item[1];
            $realname      = $item[2];
            $price         = $item[3];
            $return_status = $item[4];
            $return_msg    = $item[5];
            $pay_sn        = $item[6];
            $pay_time      = date('Y-m-d H:i:s', strtotime($item[7]));
            $result[]      = compact('id', 'pay_account', 'realname', 'price', 'return_status', 'return_msg', 'pay_sn', 'pay_time');
        }
        return $result;
    }

    public function test()
    {
        require_once(APP_PATH . '/Lib/Inslib/pingpp/init.php');
        \Pingpp\Pingpp::setApiKey(C('pingxx.app_key'));
        \Pingpp\Pingpp::setPrivateKeyPath(APP_PATH . '/Lib/Inslib/pingpp/key/rsa_private_key.pem');

        $ch = \Pingpp\Charge::create(
            [
                'order_no'  => '123456789',
                'app'       => ['id' => C('pingxx.app_id')],
                'channel'   => 'alipay',
                'amount'    => 100,
                'client_ip' => '127.0.0.1',
                'currency'  => 'cny',
                'subject'   => 'Your Subject',
                'body'      => 'Your Body',
            ]
        );
        print_r($ch);
    }
}