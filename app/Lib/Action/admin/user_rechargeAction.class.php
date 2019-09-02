<?php
class user_rechargeAction extends backendAction
{
	protected $pk = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('user_recharge');
		$this->list_relation = true;
		$pays_name = array(
			1=>'货到付款',
			2=>'网银在线',
			3=>'支付宝',
			4=>'微信支付',
			5=>'信用卡支付',
			6=>'余额支付',
		);
		$this->assign('pays_name',$pays_name);
    }

    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($keyword,'username'));
        ($tele = $this->_request('tele', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($tele,'tele'));
        ($realname = $this->_request('realname', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($realname,'realname'));

        if ($_GET['status'] == null) {
            $status = -1;
        }
        else {
            $status = intval($_GET['status']);
        }
        $status >= 0 && $map['status'] = array('eq', $status);
		$type = intval($_GET['type']);
		$type = $type ? array('eq', $type) : array('eq', 1);
		$map['type'] = $type;

        $this->assign('search', array(
			'keyword' => $keyword,
            'tele' => $tele,
            'realname' => $realname,
            'status' => $status,
			'type' => $type[1],
        ));
        return $map;
    }

	public function _get_uids($keyword,$field){
		$where[$field] = array('like', '%'.$keyword.'%');
		$uidarr = D('user')->where($where)->field('id')->select();
		$_idarr = array();
		foreach($uidarr as $v){
			$_idarr[] = $v['id'];
		}
		return implode(',',$_idarr);
	}
	
	public function edit(){
		$id = $this->_request('id', 'intval');
        if(IS_POST){
			$info = D('user_recharge')->find($id);
			$data = [
				'remark' => $this->_post('remark', 'trim'),
				'status' => $this->_post('status', 'intval')
            ];
			if($data['status']!=0){
				$this->_mod->where(['id' => $id])->save($data);
				//不通过情况下返回资金并把记录插入log表
				if($data['status']==2){
					D('user')->where(array('id'=>$info['uid']))->setInc('price',$info['price']);
					D('price_log')->add(array(
						'uid' => $info['uid'],
						'uname' => $info['uname'],
						'price' => $info['price'],
						'action' => 'cash',
						'add_time' => date('Y-m-d H:i:s'),
						'remark' => '提现申请失败,返还余额',
						'key_id' => $id,
					));
				}
			}
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'edit');
            $this->success(L('operation_success'));
        } else {
            $info = $this->_mod->where(array('id' => $id))->find();
			$info['user'] = D('user')->where(array('id' => $info['uid']))->find();
            $this->assign('info', $info);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            }
            else {
                $this->display();
            }
        }
    }

	/**
     * 删除
     */
    public function delete()
    {
        $mod = D('user_recharge');
        $pk  = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
			$list = D('user_recharge')->where(array('IN',$ids))->select();
            if (false !== $mod->delete($ids)) {
				foreach($list as $info){
					if($info['status'] == 0){
						D('user')->where(array('id'=>$info['uid']))->setInc('price',$info['price']);
						D('price_log')->where(array('key_id'=>$info['id'],'action'=>'cash'))->delete();
					}
				}
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
                $this->success(L('operation_success'));
            }
            else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'), U($this->_name . '/index'));
            }
        }
        else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
            $this->error(L('illegal_parameters'), U($this->_name . '/index'));
        }
    }

}