<?php

class UserService
{
    public static function update_score($uid)
    {
        $saveData = [];

        $score_where = ['uid' => $uid, 'score_status' => 2];
        //赠送红积分
        $score_where['type']    = 1;
        $saveData['score_logs'] = D('score_logs')->where($score_where)->sum('score');

        //1~3级
        $score_where['type']      = 2;
        $saveData['score_invite'] = D('score_logs')->where($score_where)->sum('score');

        //4~20级
        $score_where['type']           = 3;
        $saveData['score_sales_total'] = D('score_logs')->where($score_where)->sum('score');

        //省市县代理
        $score_where['type']           = 4;
        $saveData['score_agent_total'] = D('score_logs')->where($score_where)->sum('score');

        //订单购买积分
        $score_where['type']      = 5;
        $saveData['score_orders'] = abs(D('score_logs')->where($score_where)->sum('score'));

        //订单无效退还积分
        $score_where['type']             = 6;
        $saveData['score_orders_cancel'] = abs(D('score_logs')->where($score_where)->sum('score'));

        //手动处理积分
        $score_where['type']    = 9;
        $saveData['score_hand'] = D('score_logs')->where($score_where)->sum('score');

        //白积分
        $score_where['type']          = 10;
        $score_days_total             = D('score_days')->where(['uid' => $uid, 'status' => 1])->sum('score');
        $saveData['score_days']       = D('score_logs')->where($score_where)->sum('score') - $score_days_total;
        $saveData['score_days_total'] = $score_days_total;

        //积分兑换
        $saveData['score_apply']      = D('user_apply')->where(['uid' => $uid, 'status' => 1])->sum('score');
        $saveData['score_apply_fail'] = D('user_apply')->where(['uid' => $uid, 'status' => 2])->sum('score');
        $saveData['score_frozen']     = D('user_apply')->where(['uid' => $uid, 'status' => 0])->sum('score');

        //积分转赠
        $saveData['score_to']   = D('score_grant')->where(['uid' => $uid, 'status' => 1])->sum('price');
        $saveData['score_from'] = D('score_grant')->where(['from_uid' => $uid, 'status' => 1])->sum('score');

        foreach ($saveData as $key => $val) {
            $saveData[$key] = abs($val);
        }

        D('user')->where(['id' => $uid])->save($saveData);
        $sql = "update " . C('DB_PREFIX') . "user set score=score_logs+score_invite+score_to-score_from+score_agent_total+score_sales_total-score_orders+score_orders_cancel-score_apply+score_hand-score_frozen+score_days_total where id=$uid;";
        (new Model())->query($sql);
    }
}