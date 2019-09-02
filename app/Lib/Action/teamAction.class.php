<?php

class teamAction extends baseAction
{
    protected $visitor = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->_init_visitor();
        if ($_REQUEST['act'] == 'loadjs') {
            header("content-type:text/javascript");
            $_global = [
                'APP_DEBUG'       => APP_DEBUG == true,
                'root'            => get_siteroot() . '/',
                'static_url'      => C('CDN_URL') . '/static/',
                'upload_url'      => get_siteroot() . '/data/upload/',
                'g'               => GROUP_NAME,
                'm'               => MODULE_NAME,
                'a'               => ACTION_NAME,
                'url'             => __ROOT__ . "/?m=" . MODULE_NAME,
                'URL_ROUTER_ON'   => C('URL_ROUTER_ON'),
                'URL_HTML_SUFFIX' => C('URL_HTML_SUFFIX'),
                'wall_distance'   => C('ins_wall_distance'),
                'wall_spage_max'  => C('ins_wall_spage_max'),
            ];
            if ($this->get_visitor_id()) {
                $info                = $this->get_visitor_info();
                $_global['uid']      = $info['id'];
                $_global['username'] = $info['username'];
                $_global['tele_invalid'] = $this->visitor->info['tele_invalid'];
            }
            if (C('URL_SUFFIX_NAME') && C('URL_SUFFIX_VAL')) {
                $_global['assets_suffix'] = C('URL_SUFFIX_NAME') . '=' . C('URL_SUFFIX_VAL');
            }
            echo "var _global=" . _json_encode($_global) . ";";
            exit();
        }
        $this->uid = $this->visitor->info['id'];
        $this->assign('token', $token);

        $this->_config_seo();
        $this->assign('service_shop_qq_list', explode(',', C('ins_service_shop_qq')));

		if($this->isMobile()){
			$this->error('请在电脑上打开此页面!');
		}

		$this->tgroup = [0=>'普通用户',1=>'联席董事',2=>'官方合伙人',3=>'总经销商',4=>'经销商'];
		$this->assign('tgroup', $this->tgroup);

    }

    /**
     * 初始化访问者
     */
    private function _init_visitor()
    {
        $this->visitor = new user_visitor('user');
		if($this->is_visitor_login()){
			$tele = $this->visitor->get('tele');
			if(!preg_match("/^1[0-9][0-9]{1}[0-9]{8}$/",$tele)){    
				$this->visitor->info['tele_invalid'] = 1;
			}
			$this->visitor->info['tgroup'] = $this->visitor->get('tgroup');
		}
        $this->assign('visitor', $this->visitor->info);

		$menu_list = $this->_get_team_menu();
		$this->assign('user_menu_list', $menu_list);
        $this->assign('user_menu_curr', ACTION_NAME);
    }

    protected function is_visitor_login()
    {
        return $this->visitor->info['id'] > 0;
    }

    protected function get_visitor_id()
    {
        return $this->visitor->info['id'];
    }

    protected function get_visitor_info()
    {
        return $this->visitor->info;
    }

	private function _get_team_menu() {
        $menu = array();

		$menu['top'] = array(
            'text' => '销售管理',
            'submenu' => array(
				'team_order' => array('text'=>'消费明细', 'url' => U('user/order',array('profit'=>1))),
				'price' => array('text'=>'资金明细', 'url' => U('user/price',array('profit'=>1))),
				'price_log' => array('text'=>'收益明细', 'url' => U('user/price_log',array('profit'=>1))),
				'team' => array('text'=>'团队管理', 'url' => U('user/team')),
            )
        );

		if($this->visitor->info['tgroup'] < 4){
			$menu['top']['submenu']['add_user'] = array('text'=>'创建团队', 'url' => U('user/add_user'));
		}

		$menu['home'] = array(
            'text' => '帐户管理',
            'submenu' => array(
				'pers' => array('text'=>'收益比例', 'url' => U('user/pers')),
				'profile' => array('text'=>'个人资料', 'url' => U('user/profile')),
				'password' => array('text'=>'修改密码', 'url' => U('user/password')),
            )
        );

        return $menu;
    }

    //api转发
    private function dispatchApi()
    {
        $api = $this->_request('api', 'trim');
        if (strpos($api, '/api/') !== false) {
            $api_res = explode('/', trim($api, '/'));
            $m       = $api_res[1];
            $a       = $api_res[2];

            $data    = array('data' => html_entity_decode($this->_request('data')));
            $ch      = curl_init();
            $options = array(
                CURLOPT_HEADER         => false,
                CURLOPT_POST           => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_VERBOSE        => false,
                CURLOPT_FRESH_CONNECT  => FALSE,
                CURLOPT_URL            => __SITEROOT__ . $api,
                CURLOPT_POSTFIELDS     => http_build_query($data),
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 0,
                CURLOPT_COOKIE         => "token=" . $this->getApiSession(session('user_info')),
//                CURLOPT_COOKIE         => 'token=aWGUlZaYZWpplpqb',
            );
//            var_dump($options);exit();
            curl_setopt_array($ch, $options);
            $res      = curl_exec($ch);
            $response = _json_decode($res);
            curl_close($ch);
            header('Content-Type:text/json; charset=utf-8');
            if (in_array($m . '_' . $a, array('user_login', 'user_register', 'user_bind_callback'))) {
                session('user_info', Arr::pick($response['data'], 'id,username,img'));
            }
            if ($response) {
                $response['info'] = $response['result'];
                unset($response['result']);
                print_r(_json_encode($response));
            }
            else {
                print_r($res);
            }
            exit();
        }
    }


    /**
     * SEO设置
     */
    protected function _config_seo($seo_info = array(), $data = array())
    {
        $page_seo = array(
            'title'       => C('ins_site_name'),
            'keywords'    => C('ins_site_keyword'),
            'description' => C('ins_site_description')
        );
//        $page_seo = array_merge($page_seo, $seo_info);
//        //开始替换
//        $searchs  = array('{site_name}', '{site_title}', '{site_keywords}', '{site_description}');
//        $replaces = array(C('ins_site_name'), C('ins_site_name'), C('ins_site_keyword'), C('ins_site_description'));
//        preg_match_all("/\{([a-z0-9_-]+?)\}/", implode(' ', array_values($page_seo)), $pageparams);
//        if ($pageparams) {
//            foreach ($pageparams[1] as $var) {
//                $searchs[]  = '{' . $var . '}';
//                $replaces[] = $data[$var] ? strip_tags($data[$var]) : '';
//            }
//            //符号
//            $searchspace  = array('((\s*\-\s*)+)', '((\s*\,\s*)+)', '((\s*\|\s*)+)', '((\s*\t\s*)+)', '((\s*_\s*)+)');
//            $replacespace = array('-', ',', '|', ' ', '_');
//            foreach ($page_seo as $key => $val) {
//                $page_seo[$key] = trim(preg_replace($searchspace, $replacespace, str_replace($searchs, $replaces, $val)), ' ,-|_');
//            }
//        }
        $this->assign('page_seo', $page_seo);
    }

    /**
     * 连接用户中心
     * @param string $type
     * @return passport
     */
    protected function _user_server($type = 'user')
    {
        $passport = new passport(C('ins_integrate_code'), $type);
        return $passport;
    }

    /**
     * 前台分页统一
     */
    protected function _pager($count, $pagesize = 10)
    {
        $pager           = new Page($count, $pagesize);
        $pager->rollPage = 5;
        //$pager->setConfig('prev', '<');
        //$pager->setConfig('theme', '%upPage% %first% %linkPage% %end% %downPage%');

        if (GROUP_NAME == 'home' || GROUP_NAME == 'wap') {
            $pager->setConfig('prev', '<');
            $pager->setConfig('next', '>');
            $pager->setConfig('theme', '%upPage% %linkPage% %downPage%');
        }
        elseif (GROUP_NAME == 'seller') {
            $pager->setConfig('prev', '上一页');
            $pager->setConfig('next', '下一页');
            $pager->setConfig('theme', '%first% %upPage% %linkPage% %downPage% %end%');
        }
        else {
            $pager->setConfig('prev', '上一页');
            $pager->setConfig('next', '下一页');
            $pager->setConfig('theme', '%upPage% %downPage%');
        }
        return $pager;
    }

    /**
     * 瀑布显示
     */
    public function waterfall($where = array(), $order = 'id DESC', $field = '', $page_max = '', $target = '')
    {
        $spage_size = C('ins_wall_spage_size'); //每次加载个数
        $spage_max  = C('ins_wall_spage_max'); //每页加载次数
        $page_size  = $spage_size * $spage_max; //每页显示个数

        $item_mod   = M('item');
        $where_init = array('status' => '1');
        $where      = $where ? array_merge($where_init, $where) : $where_init;
        $count      = $item_mod->where($where)->count('id');
        //控制最多显示多少页
        if ($page_max && $count > $page_max * $page_size) {
            $count = $page_max * $page_size;
        }
        //查询字段
        $field == '' && $field = '*';
        //分页
        $pager = $this->_pager($count, $page_size);
        $target && $pager->path = $target;
        $item_list = $item_mod->field($field)->where($where)->order($order)->limit($pager->firstRow . ',' . $spage_size)->select();
        foreach ($item_list as $key => $val) {
            isset($val['comments_cache']) && $item_list[$key]['comment_list'] = unserialize($val['comments_cache']);
        }
        $this->assign('item_list', $item_list);
        //当前页码
        $p = $this->_get('p', 'intval', 1);
        $this->assign('p', $p);
        //当前页面总数大于单次加载数才会执行动态加载
        if (($count - ($p - 1) * $page_size) > $spage_size) {
            $this->assign('show_load', 1);
        }
        //总数大于单页数才显示分页
        $count > $page_size && $this->assign('page_bar', $pager->fshow());
        //最后一页分页处理
        if ((count($item_list) + $page_size * ($p - 1)) == $count) {
            $this->assign('show_page', 1);
        }
    }

    /**
     * 瀑布加载
     */
    public function wall_ajax($where = array(), $order = 'id DESC', $field = '')
    {
        $spage_size = C('ins_wall_spage_size'); //每次加载个数
        $spage_max  = C('ins_wall_spage_max'); //加载次数
        $p          = $this->_get('p', 'intval', 1); //页码
        $sp         = $this->_get('sp', 'intval', 1); //子页

        //条件
        $where_init = array('status' => '1');
        $where      = array_merge($where_init, $where);
        //计算开始
        $start    = $spage_size * ($spage_max * ($p - 1) + $sp);
        $item_mod = M('item');
        $count    = $item_mod->where($where)->count('id');
        $field == '' && $field = 'id,uid,uname,title,intro,img,price,likes,comments,comments_cache';
        $item_list = $item_mod->field($field)->where($where)->order($order)->limit($start . ',' . $spage_size)->select();
        foreach ($item_list as $key => $val) {
            //解析评论
            isset($val['comments_cache']) && $item_list[$key]['comment_list'] = unserialize($val['comments_cache']);
        }
        $this->assign('item_list', $item_list);
        $resp = $this->fetch('public:waterfall');
        $data = array(
            'isfull' => 1,
            'html'   => $resp
        );
        $count <= $start + $spage_size && $data['isfull'] = 0;
        $this->ajaxReturn(1, '', $data);
    }

    protected function ajaxReturn($status = 1, $msg = '', $data = '', $dialog = '')
    {
        parent::ajaxReturn(array(
            'status' => $status,
            'msg'    => $msg,
            'data'   => $data,
            'dialog' => $dialog,
        ));
    }

	/*判断是否手机访问,指定手机模板*/
	protected function isMobile(){
		$_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';  
		$mobile_browser = 0;
		if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) $mobile_browser++;
		if(isset($_SERVER['HTTP_ACCEPT']) && (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false)) $mobile_browser++;
		if(isset($_SERVER['HTTP_X_WAP_PROFILE'])) $mobile_browser++;
		if(isset($_SERVER['HTTP_PROFILE'])) $mobile_browser++;
		$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));  
		$mobile_agents = array(
			'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
			'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
			'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
			'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
			'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
			'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
			'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
			'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
			'wapr','webc','winw','winw','xda','xda-'
		);  
		if(in_array($mobile_ua, $mobile_agents)) $mobile_browser++;
		if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false) $mobile_browser++;
		if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false) $mobile_browser=0;
		if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false) $mobile_browser++;
		if($mobile_browser>0){
			return true;
		}else{
			return false;
		}
	}

}