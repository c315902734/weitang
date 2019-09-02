<?php
class user_authAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('user');
    }

    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['uid'] = array('IN',$this->_get_uids($keyword,'username'));

        if ($_GET['is_auth'] == null) {
            $is_auth = 9;
        } else {
            $is_auth = intval($_GET['is_auth']);
        }
        $is_auth >= 0 && $map['is_auth'] = array('eq', $is_auth);

        $this->assign('search', array(
			'keyword' => $keyword,
            'realname' => $realname,
            'is_auth' => $is_auth,
        ));
        return $map;
    }

 

	public function index(){
        $map = $this->_search();
        $pagesize = 20;
		$sort = 'id';
		$order = 'DESC';
        //如果需要分页
        $select = $this->_mod->where($map)->order($sort . ' ' . $order);
        if ($pagesize) {
            $array = $select->select();
            $count = count($array);
            $pager = new Page($count, $pagesize);
            $select = $this->_mod->where($map)->order($sort . ' ' . $order);
            $select->limit($pager->firstRow . ',' . $pager->listRows);
            $page = $pager->show();
            $this->assign("page", $page);
        }
        $list = $select->select();
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }

    public function edit(){
		$id = $this->_request('id', 'intval');
        if(IS_POST){
			$data = [
				'is_auth' => $this->_post('is_auth', 'intval', 0) //认证
            ];
			D('user')->where(['id' => $id])->save($data);
            
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'edit');
            $this->success(L('operation_success'));
        } else {
            
            $info = $this->_mod->where(array('id' => $id))->find();
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
	
	public function edit_info(){
		$id = $this->_request('id', 'intval');
        if(IS_POST){
			$data = [
				'is_auth' => $this->_post('is_auth', 'intval', 0),
				'realname' => $this->_post('realname', 'trim', ''),
				'bankname' => $this->_post('bankname', 'trim', ''),
				'bankid' => $this->_post('bankid', 'trim', ''),
				'tele' => $this->_post('tele', 'trim', ''),
				'sex' => $this->_post('sex', 'intval', 0),
				'company' => $this->_post('company', 'trim', ''),
				'receive_erweima' => $this->_post('receive_erweima', 'trim', ''),
            ];
			D('user')->where(['id' => $id])->save($data);
			IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'edit_info');
            $this->success(L('operation_success'));
        } else {
            
            $info = $this->_mod->where(array('id' => $id))->find();
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



}