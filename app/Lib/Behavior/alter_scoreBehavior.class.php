<?php

defined('THINK_PATH') or exit();

class alter_scoreBehavior extends Behavior {

    public function run(&$_data){
        $this->_alter_score($_data);
    }

    /**
     * 改变用户积分
     * 配置操作行为必须和标签名称一致
     */
    private function _alter_score($_data) {
        $score = C('ins_score_rule.'.$_data['action']); //获取积分变量
        $coin = C('ins_score_rule.coin_'.$_data['action']); //获取经验变量
        if (intval($score) == 0) return false; //积分为0
        if ($this->_check_num($_data['uid'], $_data['action'])) {
			$score_data = array('score'=>array('exp','score+'.$score), 'score_level'=>array('exp', 'score_level+'.abs($score)));
            M('user')->where(array('id'=>$_data['uid']))->setField($score_data); //改变用户积分

            if (intval($coin) > 0){
                $coin_data = array('coin'=>array('exp','coin+'.$coin));
                M('user')->where(array('id'=>$_data['uid']))->setField($coin_data); //改变用户经验
                /*----给用户定级别--------*/
                //用户经验
                $user_coin = D('user')->where(array('id'=>$_data['uid']))->getField('coin');

                //经验等级列表
                $coin_list = D('user_level')->select();
                //获取用户经验等级
                $coin_level = D('user')->where(array('id'=>$_data['uid']))->getField('level');

                $i = 0;
                foreach($coin_list as $val){
                    if($i == 0){
                        if($user_coin > $val['min'] && $user_coin <= $val['max']){
                            if($coin_level < $val['id']){
                                $data['from_uid'] = 0;
                                $data['from_uname'] = '系统管理员';
                                $data['to_uid'] = $_data['uid'];
                                $data['to_uname'] = D('user')->where('id='.$_data['uid'])->getField('username');
                                $msg = D('message_tpl')->where('id=10')->getField('content');
                                $data['info'] = sprintf($msg,$val['title']);
                                //$data['info'] = '恭喜亲成功晋级'. $val['title'] .'!积分所得积分奖励相应提升10%哦!奖励具体规则详见帮助中心。';
                                D('message')->add($data);
                            }
                            M('user')->where(array('id'=>$_data['uid']))->setField('level',$val['id']-1); //改变用户等级
                            $i = 1;
                        }
                    }
                }
            }

            //获取用户的总积分和贡献值
            $user = D('user')->where('id='.$_data['uid'])->field('score,coin')->find();

            //积分日志
            $score_log_mod = D('score_log');
            $score_log_mod->create(array(
                'uid' => $_data['uid'],
                'uname' => $_data['uname'],
                'action' => $_data['action'],
                'score' => $score,
                'coin' => $coin,
                'remark' => '用户'.$_data['uname'].'的积分数量'.$user['score'].'，贡献值数量'.$user['coin'],
            ));
            $idd = $score_log_mod->add();

            $saveData = array(
                'uid' => $_data['uid'],
                'uname' => $_data['uname'],
                'data_id' => $idd,
                'data_type' => 'score_log_add',
                'add_time' => date('Y-m-d H:i:s', time())
            );

            //$this->_auto_data(D('user_trend')->getDbFields(), $saveData);
            D('user_trend')->add($saveData);
            D('user_log')->add($saveData);
            return true;
        }
    }

    /**
     * 检查次数限制
     */
    private function _check_num($uid, $action){
        $return = false;
        $user_stat_mod = D('user_stat');
        //登录次数限制
        $max_num = C('ins_score_rule.'.$action.'_nums');
        //先检查统计信息
        $stat = $user_stat_mod->field('num,last_time')->where(array('uid'=>$uid, 'action'=>$action))->find();
        if (!$stat) {
            $user_stat_mod->create(array('uid'=>$uid, 'action'=>$action));
            $user_stat_mod->add();
        }
        $new_num = $stat['num'] + 1;
        if ($max_num == 0) {
            $return = true; //为0则不限制
        } else {
            if ($stat['last_time'] < todaytime()) {
                $new_num = 1;
                $return = true;
            } else {
                $return = $stat['num'] >= $max_num ? false : true;
            }
        }
        //更新统计
        $user_stat_mod->create(array('num'=>$new_num, 'last_time'=>time()));
        $user_stat_mod->where(array('uid'=>$uid, 'action'=>$action))->save();

        return $return;
    }

}