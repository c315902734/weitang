<?php
/**
 * 用户控制器基类
 *
 * @author andery
 */
class userbaseAction extends frontendAction {

	public $email_error = '';
    public function _initialize(){
        parent::_initialize();
        //访问者控制
        if (!$this->visitor->is_login && !in_array(ACTION_NAME, array('login', 'register', 'binding', 'ajax_check'))) {
            IS_AJAX && $this->ajaxReturn(0, L('login_please'));
            $this->redirect('passport/login');
        }
        
        $this->mod = D(GROUP_NAME == 'seller' ? 'member' : 'user');
        $this->uid = $this->visitor->info['id'];
        if(GROUP_NAME == 'seller'){
            /*$this->member_type = $this->mod->where(array('id'=>$this->uid))->getField('member_type');
            
            $this->assign('member_type', $this->member_type);*/
        }

        $this->_curr_menu(ACTION_NAME);
    }

    protected function _curr_menu($menu = 'index') {
        if(GROUP_NAME == 'seller'){
            $menu_list = $this->_get_seller_menu();
        } elseif (GROUP_NAME == 'buyer'){
            $menu_list = $this->_get_buyer_menu();
        }
        
        $this->assign('user_menu_list', $menu_list);
        $this->assign('user_menu_curr', $menu);
    }

    private function _get_seller_menu()
    {
        $menu        = array();
        $str         = '';
        $menu['top'] = array(
            'text'    => '商品管理',
            'submenu' => array(
                'order_list'       => array('text' => '订单管理', 'url' => U('user/order_list')),
                'order_import'     => array('text' => '导入快递信息', 'url' => U('user/order_import')),
                'item_list'        => array('text' => '商品管理', 'url' => U('user/item_list')),
                'add_item'         => array('text' => '发布商品', 'url' => U('user/add_item')),
                'apply_log'        => array('text' => '提现记录', 'url' => U('user/apply_log')),
                'apply_give'       => array('text' => '申请提现', 'url' => U('user/apply_give')),
                'brand_list'       => array('text' => '品牌管理', 'url' => U('user/brand_list')),
                'express'          => array('text' => '我的快递', 'url' => U('user/express')),
            )
        );

        $info = D('member')->where(array('id' => $this->uid))->find();
        if ($info['is_lock']) {
            unset($menu['top']['submenu']['apply_give']);
        }
        if ($info['title']) {
            $str .= '店铺：' . $info['title'];
            $str .= '<p>商家编号：' . $info['id'] . '</p>';
        }
        $menu['home'] = array(
            'text'    => '个人中心',
            'submenu' => array(
                'profile'  => array('text' => '个人信息', 'url' => U('user/profile')),
                'service'  => array('text' => '客服电话', 'url' => U('user/service')),
                'setting'  => array('text' => '账号设置', 'url' => U('user/setting')),
                'password' => array('text' => '修改密码', 'url' => U('user/password')),
            )
        );
        $this->assign('str', $str);
        return $menu;
    }

    private function _get_buyer_menu() {
        $menu = array();
        $menu['home'] = array(
            'text' => '个人中心',
            'submenu' => array(
                'profile' => array('text'=>'个人信息', 'url'=>U('user/profile')),
                'password' => array('text'=>'修改密码', 'url'=>U('user/password')),
            )
        );
        return $menu;
    }

	//资料完整度
	protected function getUserIntegrity($uid,$type='profile'){
		$profile = array('nickname', 'birthday', 'province', 'city', 'area', 'img', 'address', 'sex','history','jobs','info');
		$safe = array('tele', 'email');
		if($type == 'profile'){
			$field = $profile;
		}elseif($type == 'safe'){
			$field = $safe;
		}
		$total = count($field);
		$userinfo = D('user')->field(implode(',',$field))->find($uid);
		$num = 0;
		foreach($userinfo as $key=>$val){
			if($val != ''){
				$num++;
			}
		}
		$per = intval($num/$total*100);
		$per = $per > 100 ? 100 : $per;
		return $per;
	}

	
	//用户发送邮件
	protected function sendEmail($email,$title,$info,$is_exist=false,$uid=0){
		$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
		if(!preg_match( $pattern, $email ) ){
			$this->email_error = '邮箱格式不正确';
			return false;
		}
		if($is_exist == true){
			$is_has_email = D('user')->where(array('id'=>array('neq',$uid),'email'=>$email))->count();
			if($is_has_email > 0){
				$this->email_error = '邮箱已存在';
				return false;
			}
		}
		$mailer = mailer::get_instance();
		$result = $mailer->send($email, $title, $info);
		if($result){
			return true;
		}else{
			$this->email_error = '发送邮箱失败';
			return false;
		}
	}
}