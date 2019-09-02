<?php

class messageAction extends backendAction{

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('message');
    }

    public function _before_index() {
        $type = $this->_get('type','intval',1);
        if( $type==1 ){
            $big_menu = array(
                'title' => L('发送通知'),
                'iframe' => U(MODULE_NAME.'/add'),
                'id' => 'add',
                'width' => '500',
                'height' => '320'
            );
            $this->assign('big_menu', $big_menu);
        }
        $this->assign('type',$type);
    }

    protected function _search() {
        $map = array();
        ($time_start = $this->_request('time_start', 'trim')) && $map['add_time'][] = array('egt', $time_start);
        ($time_end = $this->_request('time_end', 'trim')) && $map['add_time'][] = array('elt', $time_end.' 23:59:59');
        ($keyword = $this->_request('keyword', 'trim')) && $map['info'] = array('like', '%'.$keyword.'%');
        ($from_uname = $this->_request('from_uname', 'trim')) && $map['from_uname'] = array('like', '%'.$from_uname.'%');
        ($to_uname = $this->_request('to_uname', 'trim')) && $map['to_uname'] = array('like', '%'.$to_uname.'%');
        $type = $this->_request('type', 'intval');
        if( $type ){
            if( $type==1 ){
                $map['from_uid'] = 0;
            }else if( $type==2 ){
                $map['from_uid'] = array('gt',0);
            }
        }
        $this->assign('search', array(
            'time_start' => $time_start,
            'time_end' => $time_end,
            'from_uname' => $from_uname,
            'to_uname'   => $to_uname,
            'type'  => $type,
            'keyword' => $keyword,
        ));
        return $map;
    }

    public function add() {
        if (IS_POST) {
            //内容
            $type = $this->_post('type', 'trim', 'custom');
            //用户
            $to_uname = $this->_post('to_uname');
            //发送者
            $from_user = session('admin');
            $from_uname = $from_user['username'];
            //接收者
            $to_user = array(array('id'=>'0', 'username'=>'0'));
            if ($to_uname) {
                //指定用户
                $to_uname = explode("\r\n",$to_uname);
                $to_user = D('user')->field('id,username')->where(array('username'=>array('in', $to_uname)))->select();
            }
            //内容
            if ($type == 'custom') {
                //自定义
                $info = $this->_post('info', 'trim');
                !$info && $this->ajaxReturn(0, L('message_empty'));
            } else {
                //获取模板名称
                $tpl_alias = $this->_post('tpl_alias', 'trim');
            }
            //逐条发送
            $message_tpl_mod = D('message_tpl');
            foreach ($to_user as $val) {
                $today = date('Y-m-d H:i:s',time());
                $data = array(
                    'from_uid' => 0,
                    'from_uname' => $from_uname,
                    'to_uid' => $val['id'],
                    'to_uname' => $val['username'],
                    'title' => "系统消息",
                    'info' => $info,
                    'add_time' => $today,
                );
                $this->_mod->add($data);
                //增加用户未读系统通知
                if($val['id'] > 0){
                    D('user')->where(array('id'=>$val['id']))->setInc('msg_sys');
                } else {
                    D('user')->where('1=1')->setInc('msg_sys');
                }
                unset($data);
            }
            $this->ajaxReturn(1, L('operation_success'), '', 'add');
        } else {
            //通知模版
            $tpl_list = M('message_tpl')->field('id,alias,name')->where(array('type'=>'msg', 'is_sys'=>'0'))->select();
            $this->assign('tpl_list', $tpl_list);

            $this->assign('open_validator', true);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

}