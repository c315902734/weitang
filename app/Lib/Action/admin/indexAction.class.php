<?php

class indexAction extends backendAction
{

    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('menu');
    }

    public function index()
    {
        // $this->_mod 为模型对象
        $top_menus = $this->_mod->admin_menu(0);
        $this->assign('top_menus', $top_menus);
        $my_admin = array('username' => $_SESSION['admin']['username'],
                          'rolename' => D("admin_role")->where("id=" . intval($_SESSION['admin']['role_id']))->getField("name"));
        $this->assign('my_admin', $my_admin);
        $this->assign('menu_data', json_encode($this->_mod->get_menu_data()));
        $this->display();
    }

    public function panel()
    {
        $message = array();
        if (is_dir('./install')) {
            $message[] = array(
                'type'    => 'error',
                'content' => "您还没有删除 install 文件夹，出于安全的考虑，我们建议您删除 install 文件夹。",
            );
        }
        if (APP_DEBUG == true) {
            $message[] = array(
                'type'    => 'error',
                'content' => "您网站的 DEBUG 没有关闭，出于安全考虑，我们建议您关闭程序 DEBUG。",
            );
        }
        if (!function_exists("curl_getinfo")) {
            $message[] = array(
                'type'    => 'error',
                'content' => "系统不支持 CURL ,将无法采集商品数据。",
            );
        }
        $this->assign('message', $message);
        $mysql_info  = get_mysql_info();
        $system_info = array(
            'pinphp_version'      => PIN_VERSION . ' RELEASE ' . PIN_RELEASE . ' [<a href="http://www.pinphp.com/" class="blue" target="_blank">查看最新版本</a>]',
            'server_domain'       => $_SERVER['SERVER_NAME'] . ' [ ' . gethostbyname($_SERVER['SERVER_NAME']) . ' ]',
            'server_os'           => PHP_OS,
            'web_server'          => $_SERVER["SERVER_SOFTWARE"],
            'php_version'         => PHP_VERSION,
            'mysql_version'       => $mysql_info['version'],
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_execution_time'  => ini_get('max_execution_time') . '秒',
            'safe_mode'           => (boolean)ini_get('safe_mode') ? L('yes') : L('no'),
            'zlib'                => function_exists('gzclose') ? L('yes') : L('no'),
            'curl'                => function_exists("curl_getinfo") ? L('yes') : L('no'),
            'timezone'            => function_exists("date_default_timezone_get") ? date_default_timezone_get() : L('no')
        );
        $this->assign('system_info', $system_info);

        $suse_time = date('Y-m-d') . ' 00:00:00';
        $euse_time = date('Y-m-d') . ' 23:59:59';

        //统计
        $order                 = array();
        $order['status_1']     = D('order')->where(array('status' => 1))->count('id');
        $order['status_0_day'] = D('order')->where(array('status' => 0, 'add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->count('id');
        $order['status_1_day'] = D('order')->where(array('status' => 1, 'add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->count('id');
        $order['total_day']    = D('order')->where(array('add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->sum('total');//今日订单总额
        $order['total_day']    = $order['total_day'] ? $order['total_day'] : 0.00;
        $order['up_total_day']    = D('order')->where(array('add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->sum('total');//今日升级订单总额
        $order['up_total_day']    = $order['up_total_day'] ? $order['up_total_day'] : 0.00;
        $order['number_day']   = D('order')->where(array('add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->count('id');//今日订单总数
        $order['up_number_day']   = D('order')->where(array('add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->count('id');//今日升级订单总数
        $order['status_9']     = D('order')->where(array('status' => 9))->count('id');
        $order['status_5']     = D('order')->where(array('status' => array('in', '3,4,5')))->count('id');
        $order['total']        = D('order')->sum('total');//订单总额
        $order['total']        = $order['total'] ? $order['total'] : 0.00;
        $order['up_total']        = D('order')->where(array('lottery' => array('in', '1,2,9')))->sum('total');//升级订单总额
        $order['up_total']        = $order['up_total'] ? $order['up_total'] : 0.00;
        $order['number']       = D('order')->count('id');//订单总数
        $order['up_number']       = D('order')->where(array('lottery' => array('in', '1,2,9')))->count('id');//升级订单总数
        $this->assign('order', $order);
        $this->assign(compact('suse_time', 'euse_time'));

        $item             = array();
        $item['status_0'] = D('item')->where(array('status' => 0, 'is_del' => 0))->count('id');
        $item['status_1'] = D('item')->where(array('status' => 1, 'is_del' => 0))->count('id');
        $item['number']   = D('item')->where(array('is_del' => 0))->count('id');
        $item['otc']      = D('item')->where(array('type_id' => array('lt', '3'), 'is_del' => 0))->count('id');
        $item['otc_n']    = D('item')->where(array('type_id' => array('eq', '3'), 'is_del' => 0))->count('id');
        $item['ylqx']     = D('item')->where(array('type_id' => 11, 'is_del' => 0))->count('id');
        $this->assign('item', $item);
		
		$m_suse_time = date('Y-m-d',strtotime("-30 days")) . ' 00:00:00';
        $m_euse_time = date('Y-m-d H:is');

		$recharge['total_day'] = D('user_recharge')->where(array('status' => 1,'type'=>1, 'add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->sum('price');
		$recharge['total_month'] = D('user_recharge')->where(array('status' => 1,'type'=>1, 'add_time' => array('elt', $m_euse_time), 'add_time' => array('egt', $m_suse_time)))->sum('price');
		$recharge['total'] = D('user_recharge')->where(array('status' => 1,'type'=>1))->sum('price');
        $this->assign('recharge', $recharge);

		$cash['total_day'] = D('user_recharge')->where(array('status' => 1,'type'=>2, 'add_time' => array('elt', $euse_time), 'add_time' => array('egt', $suse_time)))->sum('price');
		$cash['total_month'] = D('user_recharge')->where(array('status' => 1,'type'=>2, 'add_time' => array('elt', $m_euse_time), 'add_time' => array('egt', $m_suse_time)))->sum('price');
		$cash['total'] = D('user_recharge')->where(array('status' => 1,'type'=>2))->sum('price');
        $this->assign('cash', $cash);


        $this->display();
    }
    // 后台登陆
    public function login()
    {
        if (IS_POST) {
            $username    = $this->_post('username', 'trim');
            $password    = $this->_post('password', 'trim');
            $verify_code = $this->_post('verify_code', 'trim');
            if (session('verify') != md5($verify_code)) {
                $this->error(L('verify_code_error'));// 验证码错误
            }
            // 直接获取登陆用户的个人信息（包括用户名，密码，登陆时间，ip地址）
            $admin = M('admin')->where(array('username' => $username, 'status' => 1))->find();
            if (!$admin) {
                $this->error(L('admin_not_exist'));  // 帐号不存在或已禁用！
            }
            if ($admin['password'] != md5($password)) {
                $this->error(L('password_error'));   // 密码错误
            }
            // 登录成功，将登录后台成功的管理员id，管理员名字以及角色id一起赋给session，进行跨页面调用
            session('admin', array(
                'id'       => $admin['id'],
                'role_id'  => $admin['role_id'],
                'username' => $admin['username'],
            ));
            // 将管理员最后登录的时间和最后登录的ip地址，全部保存到数据库里
            M('admin')->where(array('id' => $admin['id']))->save(array('last_time' => date('Y-m-d H:i:s'), 'last_ip' => get_client_ip()));
            $this->success(L('login_success'), U('index/index'));
        }
        else {
            $this->display();
        }
    }
    // 退出登陆
    public function logout()
    {
        session('admin', null);
        // 退出登陆成功，跳转到后台登陆页面
        $this->success(L('logout_success'), U('index/login'));
        exit;
    }
    // 生成验证码
    public function verify_code()
    {
        // 返回的是一个加密了的 session 的会话变量
        Image::buildImageVerify(4, 1, 'png', '50', '24');
    }
    // 后台左侧菜单栏
    public function left()
    {
        $menuid = $this->_request('menuid', 'intval', 0);
        if ($menuid) {
            // 此处的 $this->_mod = D('menu');  菜单 menu 的实例对象
            // 通过每个头部顶级菜单的id获取其显示在左侧栏的所有二级菜单
            $left_menu = $this->_mod->admin_menu($menuid);
            /*$menuid =70 name=会员管理（顶级菜单名称）
              $left_menu =D('menu')->admin_menu(70);
              $left_menu =array(
                                0=>array('id'=>117,'name'=>'会员管理')
                                1=>array('id'=>864,'name'=>'签到管理'));
             * */
            foreach ($left_menu as $key => $val) {
                // $left_menu[$key]['sub'] = D('menu')->admin_menu($val['id']);
                // 获取每个二级菜单下的三级菜单
                $left_menu[$key]['sub'] = $this->_mod->admin_menu($val['id']);
            }
        }
        else {
            // 若menuid 为 0 ，将“常用功能”作为顶级菜单  后台首页作为二级菜单
            $left_menu[0]        = array('id' => 0, 'name' => L('common_menu')); // L('common_menu') 常用功能
            $left_menu[0]['sub'] = array();
            //       $r= D('menu')->where('often=1')->select()
            if ($r = $this->_mod->where(array('often' => 1))->select()) {
                $left_menu[0]['sub'] = $r;
            }
            //
            //函数用于向数组插入新元素。新数组的值将被插入到数组的开头。array_unshift函数会返回数组中元素的个数。
            array_unshift($left_menu[0]['sub'], array('id' => 0, 'name' => '后台首页'));
        }
        // 选中左侧菜单栏后，$topid (实际就是menuid),就会出现在地址栏上，也即头部菜单的id
        $this->assign('topid', $menuid);
        $this->assign('left_menu', $left_menu);
        $this->display();
    }

    public function often()
    {
        if (isset($_POST['do'])) {
            $id_arr = isset($_POST['id']) && is_array($_POST['id']) ? $_POST['id'] : '';
            $this->_mod->where(array('often' => 1))->save(array('often' => 0));
            $id_str = implode(',', $id_arr);
            $this->_mod->where('id IN(' . $id_str . ')')->save(array('often' => 1));
            $this->success(L('operation_success'));
        }
        else {
            $r    = $this->_mod->admin_menu(0);
            $list = array();
            foreach ($r as $v) {
                $v['sub'] = $this->_mod->admin_menu($v['id']);
                foreach ($v['sub'] as $key => $sv) {
                    $v['sub'][$key]['sub'] = $this->_mod->admin_menu($sv['id']);
                }
                $list[] = $v;
            }
            $this->assign('list', $list);
            $this->display();
        }
    }

    public function map()
    {
        // 获取menuid 为 0 的所有顶级菜单
        $r    = $this->_mod->admin_menu(0);
        $list = array();
        foreach ($r as $v) {
            $v['sub'] = $this->_mod->admin_menu($v['id']);
            foreach ($v['sub'] as $key => $sv) {
                $v['sub'][$key]['sub'] = $this->_mod->admin_menu($sv['id']);
            }
            $list[] = $v;
        }
        $this->assign('list', $list);
        $this->display();
    }
}