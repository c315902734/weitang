<?php

class coinAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod      = D('coin');
    }

    public function _before_index()
    {
        $sub_menu = array(
            0 => array(
                'name'        => '添加记录',
                'action_name' => 'add',
                'module_name' => 'coin',
                'class'       => 'add'
            )
        );
        $this->assign('sub_menu', $sub_menu);

        $p = $this->_get('p', 'intval', 1);
        $this->assign('p', $p);
    }

    protected function _search()
    {
        $map = array();
        $uid = $this->_request('uid', 'intval', 0);
        if($uid > 0){
            $map['uid'] = $uid;
        }
        $status = $this->_request('status', 'intval', '-1');
        if($status >= 0){
            $map['status'] = $status;
        }
        $this->assign('search', array(
            'status'       => $status,
            'uid'          => $uid,
        ));
        //print_r($map);
        return $map;
    }

    public function _before_add(){
        $uid = $this->_request('uid', 'intval');
        $info['uid'] = $uid;
        $info['uname'] = D('user')->where(array('id'=>$uid))->getField('username');
        $this->assign('info', $info);
    }

    public function _before_insert($data){
        $data['add_time'] = $data['pays_time'] = date('Y-m-d H:i:s');
        if(!$data['pays_price']){
            $data['pays_price'] = $data['money'];
        }
        $data['uname'] = D('user')->where(array('id'=>$data['uid']))->getField('username');

        return $data;
    }

    public function _after_insert($id){
        $info = $this->_mod->where(compact('id'))->field('uid,coin')->find();
        //求uid的coin总和
        $coin_sum = $this->_mod->where(array('uid'=>$info['uid']))->sum('coin');
    
        D('user')->where(array('id'=>$info['uid']))->setField('coins',$coin_sum);
    }

    public function _after_update($id){
        $info = $this->_mod->where(compact('id'))->field('uid,coin')->find();
        //求uid的coin总和
        $coin_sum = $this->_mod->where(array('uid'=>$info['uid']))->sum('coin');
    
        D('user')->where(array('id'=>$info['uid']))->setField('coins',$coin_sum);
    }

}