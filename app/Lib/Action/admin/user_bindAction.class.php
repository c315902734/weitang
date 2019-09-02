<?php
class user_bindAction extends backendAction
{
	protected $pk = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('user_bind');
    }

    protected function _search() {
        $map = array();
        ($uname = $this->_request('uname', 'trim')) && $map['uid'] = array('in',$this->_get_uids($uname));
        ($type = $this->_request('type', 'trim')) && $map['type'] = array('eq', $type);

        $this->assign('search', array(
			'uname' => $uname,
            'type' => $type
        ));
        return $map;
    }

    public function _before_index() {
        $sub_menu = array(
			0=>array(
				'name' => '添加绑定',
				'action_name' => 'add',
				'module_name' => 'user_bind',
				'class' => 'add'
			)
        );
        $this->assign('sub_menu', $sub_menu);

        $this->list_relation = true;
        $this->_before_add();
		//默认排序
        $this->sort = 'uid';
        $this->order = 'ASC';
        $this->assign('img_dir',$this->_get_imgdir());
		$type = array(
			"qq"=>'QQ',
			"weibo"=>'微博',
			"taobao"=>'淘宝',
            "weixin"=>'微信',
		);
        $this->assign('type',$type);

    }
	

	public function _before_add() {
        //$cate_list = D('flink_cate')->where(array('status'=>1))->select();
        //$this->assign('cate_list',$cate_list);
    }
    public function _before_edit()
    {
        $this->_before_add();
        $this->assign('img_dir',$this->_get_imgdir());
		$id = $this->_get('id', 'intval');
		$info = $this->_mod->find($id);
		$uname = D('user')->where(array('id'=>$info['uid']))->getField('username');
		$uid = D('user')->where(array('id'=>$info['uid']))->getField('id');

        $this->assign('uname',$uname);
        $this->assign('uid',$uid);

    }

    public function ajax_check_name()
    {
        $name = $this->_get('name', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name, $id)) {
            $this->ajaxReturn(0, '链接名称已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

    /**
     * 友情链接图片上传目录
     *
     * @staticvar null $dir
     * @return string
     */
    private function _get_imgdir() {
        static $dir = null;
        if ($dir === null) {
            $dir = './data/upload/flink/';
        }
        return $dir;
    }

	public function _get_uids($uname){
		$where['username'] = array('like', '%'.$uname.'%');
		$uidarr = D('user')->where($where)->field('id')->select();
		$_idarr = array();
		foreach($uidarr as $v){
			$_idarr[] = $v['id'];
		}
		return implode(',',$_idarr);
	}
}