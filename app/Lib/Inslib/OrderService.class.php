<?php

class OrderService
{
    var $params;

    public function OrderService($params)
    {
        $this->params = $params;
    }

    public function run()
    {
        $this->add_pools();
        $this->sync_score();
        $this->sync_user_level();
    }

    /*
     * 库存积分
     * */
    protected function add_pools()
    {
        $id = $this->params['order_id'];

        $order = D('order')->where(compact('id'))->find();
        if (D('score_pools')->where(['orderid' => $order['orderid']])->count() > 0) {
            return;
        }
        $level_id = D('user')->where(['id' => $order['uid']])->getField('level_id');
        if ($level_id == 1) {
            D('user')->where(['id' => $order['uid']])->save([
                'level_id' => 99,
            ]);
        }

        $score_total_field = $order['type'] == 1 ? 'score_pools_total' : 'score_days_total';

        if ($order['is_pools'] == 0) {
            $score_pools_total = D('setting')->where(['name' => $score_total_field])->getField('data');
            D('score_pools')->add([
                'order_id' => $order['id'],
                'orderid'  => $order['orderid'],
                'prices'   => $order['prices'],
                'total'    => $score_pools_total,
                'score'    => $order['score_pools'],
                'add_time' => current_date(),
                'type'     => $order['type'],
            ]);
            $score_pools_total += $order['score_pools'];
            D("setting")->where(['name' => $score_total_field])->save([
                'data' => $score_pools_total,
            ]);
            D('order')->where(compact('id'))->save([
                'is_pools'   => 1,
                'pools_time' => current_date(),
            ]);
        }
    }

    /*
     * 赠送1~20级的积分
     * */
    protected function sync_score()
    {
        $id    = $this->params['order_id'];
        $order = D('order')->where(compact('id'))->find();
        //赠送上线1-3级的积分
        $buy_user = D('user')->where(['id' => $order['uid']])->find();

        $log_data                  = [];
        $username                  = empty($buy_user['realname']) ? $buy_user['username'] : $buy_user['realname'];
        $log_data['title']         = "会员($username)消费,订单" . $order['orderid'] . "赠送";
        $log_data['type']          = '1';
        $log_data['order_id']      = $order['id'];
        $log_data['order_orderid'] = $order['orderid'];
        $log_data['order_prices']  = $order['prices'];
        $log_data['score_time']    = current_date();
        $log_data['add_time']      = current_date();

        for ($i = 1; $i <= 3; $i++) {
            $user = D('user')->field('username,tele')->where(['id' => $order["invite_u$i"]])->find();

            if (empty($user)) {
                continue;
            }
            $log_data['type']  = 2;
            $log_data['uid']   = $order["invite_u$i"];
            $log_data['uname'] = $user['username'];
            $log_data['tele']  = $user['tele'];
            $log_data['score'] = $this->rakeback_money($id, "score_$i");
            if ($log_data['score'] > 0) {
                $log_data['score_status'] = 2;
                D('score_logs')->add($log_data);
            }
        }

        //省市县
        $log_data['title'] = "区域订单" . $order['orderid'] . '赠送';
        $agent_type        = 1;
        foreach (['province', 'city', 'area'] as $key => $val) {
            $agent_user = D('agent_search')
                ->where(['agent_type' => $agent_type, 'agent_' . $val => $order['addr_' . $val]])->find();

            if ($agent_user) {
                $score = $this->rakeback_money($id, 'score_' . $val);

                D('user')->where(['id' => $agent_user['id']])
                    ->setInc('score_agent', $score);

                $log_data['type']  = 4;
                $log_data['uid']   = $agent_user['id'];
                $log_data['uname'] = $agent_user['username'];
                $log_data['tele']  = $agent_user['tele'];
                $log_data['score'] = $score;
                if ($log_data['score'] > 0) {
                    $log_data['score_status'] = 2;
                    D('score_logs')->add($log_data);
                }
            }
            $agent_type++;
        }

        //市场补贴
        $user_info          = D('user_info')->where(['uid' => $order['uid']])->find();
        $rakebac_level_list = $this->get_rakebac_level_list();
        for ($i = 4; $i <= 20; $i++) {
            $invite_uid = $user_info["invite_u$i"];
            if (empty($invite_uid)) {
                break;
            }
            $invite_level_id = D('user')->where(['id' => $invite_uid])->getField('level_id');
            if ($rakebac_level_list[$invite_level_id] >= $i) {
                D('user')->where(['id' => $invite_uid])->setInc('score_sales', $this->rakeback_money($id, "score_$i"));
            }
        }
        D('order')->where(compact('id'))->save([
            'distribute_status' => 1
        ]);
    }

    public function sync_user_level()
    {
        $uid = $this->params['uid'];

        $fields       = ['invite_u1', 'invite_u2', 'invite_u3'];
        $user         = D('user')->field(implode(',', $fields))->where(['id' => $uid])->find();
        $res          = D('user_level')->where(['status' => 1])->order('id asc')->select();
        $max_level_id = $res[count($res) - 1]['id'];

        foreach ($fields as $key => $field) {
            $invite_uid = $user[$field];
            if ($invite_uid <= 0) {
                break;
            }
            $invite_level_id = D('user')->where(['id' => $invite_uid])->getField('level_id');
            if ($invite_level_id >= $max_level_id) {
                continue;
            }
            $match_level = $invite_level_id < 100 ? 99 : $invite_level_id - 1;
            $next_level  = D('user_level')->where(['id' => ['gt', $invite_level_id < 100 ? 99 : $invite_level_id]])->order('id asc')->find();

            $sql        = "
select u.id,
	(select count(id) from ins_user as iu where iu.invite_u1= u.id and iu.level_id=$match_level) as level_1_num,
	(
        (select count(id) from ins_user as iu where iu.invite_u1=u.id and iu.level_id=$match_level)
        +
        (select count(id) from ins_user as iu where iu.invite_u2=u.id and iu.level_id=$match_level)
        +
        (select count(id) from ins_user as iu where iu.invite_u3=u.id and iu.level_id=$match_level)
     ) as level_3_num,
	(select sum(prices) from ins_order as o where o.uid=u.id) as consume_amount
from ins_user as u where u.id=$invite_uid;";
            $res        = D('user')->query($sql);
            $count_info = $res[0];
            if ($count_info['level_1_num'] >= $next_level['level_1_num']
                && $count_info['level_3_num'] >= $next_level['level_3_num']
                && $count_info['consume_amount'] >= $next_level['consume_amount']
            ) {
                D('user')->where(['id' => $invite_uid])->save([
                    'level_id' => $next_level['id'],
                ]);
            }
        }
    }

    /*获取积分兑换时的支付宝付款批次号*/
    public static function get_score_exchange_batch_no()
    {
        return (new OrderService([]))->get_alipay_batch_no('score_exchange');
    }

    /*获取订单退款时的支付宝付款批次号*/
    public static function get_order_refund_batch_no()
    {
        return (new OrderService([]))->get_alipay_batch_no('order_refund');
    }

    protected function get_alipay_batch_no($type)
    {
        //批次号
        $date  = intval(S($type . '_date'));
        $index = intval(S($type . '_index'));

        if ($date < strtotime(date('Y-m-d 00:00:00'))) {
            $index = 1;
        }
        else {
            $index++;
        }

        S($type . '_date', strtotime(date('Y-m-d 00:00:00')));
        S($type . '_index', $index);
        return date('Ymd') . str_pad($index, 3, '0', STR_PAD_LEFT);
    }

    protected function get_rakebac_level_list()
    {
        $res    = D('user_level')->where(['status' => 1])->select();
        $result = [];
        foreach ($res as $key => $val) {
            $result[$val['id']] = $val['rakebac_level'];
        }
        return $result;
    }

    protected function rakeback_money($id, $level)
    {
        $order_item_list = D('order_item')->where(['order_id' => $id])->select();
        $result          = 0;
        foreach ($order_item_list as $val) {
            $rate = D('item')->where(['id' => $val['item_id']])->getField($level);
            $result += $val['nums'] * $val['price'] * floatval($rate);
        }
        return price_format($result);
    }
}