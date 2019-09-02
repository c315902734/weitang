<?php

/**
 * 后台控制器基类
 *
 * @author andery
 */
class backendAction extends baseAction
{
    protected $_name = '';  // 控制器名称
    protected $menuid = 0;  // 菜单id

    public function _initialize()
    {
        parent::_initialize();
        // 获取控制器名称
        $this->_name = $this->getActionName();  // backend  即数据库的 表名 tablename
        $this->check_priv();
        // request请求，清除两边空格，默认值为 0
        $this->menuid = $this->_request('menuid', 'trim', 0);
        // 若menuid不为 0，
        if ($this->menuid) {
            // 得到一个父分类下的子菜单
            $sub_menu = D('menu')->sub_menu($this->menuid, $this->big_menu);
            //print_r($sub_menu);
            //echo D('menu')->getLastSql();die;
            // SELECT * FROM `ins_menu` WHERE pid=6 and display=1 ORDER BY ordid
            $selected = '';
            foreach ($sub_menu as $key => $val) {
                $sub_menu[$key]['class'] = '';
                if (MODULE_NAME == $val['module_name'] && ACTION_NAME == $val['action_name'] && strpos(__SELF__, $val['data'])) {
                    $sub_menu[$key]['class'] = $selected = 'on';
                }
            }
            if (empty($selected)) {
                foreach ($sub_menu as $key => $val) {
                    if (MODULE_NAME == $val['module_name'] && ACTION_NAME == $val['action_name']) {
                        $sub_menu[$key]['class'] = 'on';
                        break;
                    }
                }
            }

            $this->assign('sub_menu', $sub_menu);
        }
        if ((IS_POST && MODULE_NAME != 'index' && MODULE_NAME != 'score') || ACTION_NAME == 'ajax_edit') {
            //增加用户行为记录
            Vendor('adminlog.adminlogs');
            $_log_obj = new AdminLog(array('onoff' => 1));
            $_log_obj->doLog($this->menuid, MODULE_NAME, ACTION_NAME, $this->admin_log_sign);
        }
        $this->assign('menuid', $this->menuid);
    }

    /**
     * 列表页面
     */
    public function index()
    {
        $map = $this->_search();
        $mod = D($this->_name);
        if (!empty($mod)) {
            $result = $this->_list($mod, $map);
            if (method_exists($this, '_af_index')) {
                $result['list'] = $this->_af_index($result['list']);
            }
            $this->assign('page', $result['page']);
            $this->assign('list', $result['list']);
        }
        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {
        $mod = D($this->_name);
        if (IS_POST) {
            if (false === $data = $mod->create()) {
                IS_AJAX && $this->ajaxReturn(0, $mod->getError());
                $this->error($mod->getError(), U($this->_name . '/add'));
            }

            if (method_exists($this, '_before_insert')) {
                $data = $this->_before_insert($data);
            }
            if ($mod->add($data)) {
                if (method_exists($this, '_after_insert')) {
                    $id = $mod->getLastInsID();
                    $this->_after_insert($id);
                }
                // protected function ajaxReturn($status = 1, $msg = '', $data = '', $dialog = '')
                //                                 状态       提示信息      返回数据      会话
                // 若方法是ajax请求的话，就用ajaxReturn 返回响应的结果
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'add');
                //  若不是，则调用TP的success方法返回结果
                $this->success(L('operation_success'));
            }
            else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'), U($this->_name . '/add'));
            }
        }
        else {
            // ？？？这个assign什么作用
            $this->assign('open_validator', true);
            if (IS_AJAX) {
                /**
                 *  获取输出页面内容
                 * 调用内置的模板引擎fetch方法，
                 * @access protected
                 * @param string $templateFile 指定要调用的模板文件
                 * 默认为空 由系统自动定位模板文件
                 * @return string
                 */
                /*protected function fetch($templateFile = '')
                {
                    $this->initView();
                /**
                * 解析和获取模板内容 用于输出
                * @access public
                * @param string $templateFile 模板文件名
                * @param string $content 模板输出内容
                * @return string
                 * 此处fetch方法是系统View.class.php里面的，用于解析和获取模板内容--输出
                return $this->view->fetch($templateFile);
                }*/
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            }
            else {
                $this->display();
            }
        }
    }
    // 自动完成数据
    public function _auto_data($fields, &$data)
    {
        //时间自动完成
        $list = array('add_time', 'update_time');
        foreach ($list as $val) {
            if (in_array($val, $fields) && !isset($data[$val])) {
                $data[$val] = date('Y-m-d H:i:s', time());
            }
        }
        //用户名自动完成
        $list = array(
            array('uid', 'uname'),
            array('from_uid', 'from_uname'),
            array('to_uid', 'to_uname'),
        );
        foreach ($list as $val) {
            if (in_array($val[1], $fields) && !isset($data[$val[1]])) {
                if ($val[0] > 0) {
                    $count = D('user')->where(array('id' => $data[$val[0]]))->count();
                    if ($count) {
                        $data[$val[1]] = D('user')->where(array('id' => $data[$val[0]]))->getField('username');
                    }
                    else {
                        $this->ajaxReturnError($val[0] . '不存在');
                    }
                }
            }
        }
    }

    /**
     * 修改
     */
    public function edit()
    {
        $mod = D($this->_name);
        // 获取主键名称
        $pk  = $mod->getPk();
        if (IS_POST) {
            if (false === $data = $mod->create()) {
                IS_AJAX && $this->ajaxReturn(0, $mod->getError());
                $this->error($mod->getError(), U($this->_name . '/edit'));
            }
            if (method_exists($this, '_before_update')) {
                $data = $this->_before_update($data);
            }

            if (false !== $mod->save($data)) {
                if (method_exists($this, '_after_update')) {
                    $id = $data['id'];
                    $this->_after_update($id);
                }
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'edit');
                if ($this->_name == 'item' || $this->_name == 'member') {
                    $p           = $this->_request('p', 'intval', 1);
                    $member_type = $this->_request('member_type', 'intval', 1);
                    $retail      = $this->_request('retail', 'intval', 0);
                    $this->success(L('operation_success'), U($this->_name . '/index', array('p' => $p, 'member_type' => $member_type, 'retail' => $retail)));
                }
                else {
                    $this->success(L('operation_success'));
                }
            }
            else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'), U($this->_name . '/edit'));
            }
        }
        else {
            $id   = $this->_get($pk, 'intval');
			$this->list_relation && $mod->relation(true);
            $info = $mod->find($id);
            if (method_exists($this, '_after_edit')) {
                $info = $this->_after_edit($info);
            }
            if ($this->_name == 'item' || $this->_name == 'member') {
                $p = $this->_request('p', 'intval', 1);
                $this->assign('p', $p);
                $member_type = $this->_request('member_type', 'intval', 1);
                $this->assign('member_type', $member_type);
                $retail = $this->_request('retail', 'intval', 0);
                $this->assign('retail', $retail);
            }
            $this->assign('info', $info);
            $this->assign('open_validator', true);
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
     * ajax修改单个字段值
     */
    public function ajax_edit()
    {
        //AJAX修改数据
        $mod   = D($this->_name);
        $pk    = $mod->getPk(); // 获取主键名称
        $id    = $this->_get($pk, 'intval');
        $field = $this->_get('field', 'trim');
        $val   = $this->_get('val', 'trim');
        //允许异步修改的字段列表  放模型里面去 TODO
        $mod->where(array($pk => $id))->setField($field, $val);
        $this->ajaxReturn(1);
    }

    /**
     * 删除
     */
    public function delete()
    {
        $mod = D($this->_name);
        $pk  = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
            $this->_delete_attach($ids);
            if (false !== $mod->delete($ids)) {
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

    public function _delete_attach($ids)
    {
        $rule       = array();
        $table_list = array(
            'item', 'item_img', 'spec_item', 'spec_tags', 'ad', 'tuan',
        );
        if (empty($ids)) {
            return;
        }
        if (!in_array($this->_name, $table_list)) {
            return;
        }
        $res = $this->_mod->where("id in($ids)")->select();
        //$dirname=$this->_name;
        $dirname = 'assets';
        if (!empty($rule[$this->_name]['dir'])) {
            $dirname = $rule[$this->_name]['dir'];
        }
        foreach ($res as $val) {
            if (empty($val)) {
                continue;
            }
            !empty($val['img']) && @unlink(C('ins_attach_path') . $dirname . '/' . $val['img']);
            !empty($val['extimg']) && @unlink(C('ins_attach_path') . $dirname . '/' . $val['extimg']);
            $editor_imgs = array();
            if (!empty($val['info'])) {
                $editor_imgs = array_merge($editor_imgs, parse_editor_img($val['info']));
            }
            if (!empty($val['intro'])) {
                $editor_imgs = array_merge($editor_imgs, parse_editor_img($val['intro']));
            }
            foreach ($editor_imgs as $img_src) {
                if (is_url($img_src)) {
                    continue;
                }
                @unlink(substr($img_src, strpos($img_src, C('ins_attach_path'))));
            }
        }
    }

    /**
     * 获取请求参数生成条件数组
     */
    protected function _search()
    {
        //生成查询条件
        $mod = D($this->_name);
        $map = array();
        foreach ($mod->getDbFields() as $key => $val) {
            if (substr($key, 0, 1) == '_') {
                continue;
            }
            if ($this->_request($val)) {
                $map[$val] = $this->_request($val);
            }
        }
        return $map;
    }

    /**
     * 列表处理
     *
     * @param obj $model 实例化后的模型
     * @param array $map 条件数据
     * @param string $sort_by 排序字段
     * @param string $order_by 排序方法
     * @param string $field_list 显示字段
     * @param intval $pagesize 每页数据行数
     */
    protected function _list($model, $map = array(), $sort_by = '', $order_by = '', $field_list = '*', $pagesize = 20)
    {
        //排序
        $mod_pk = $model->getPk();
        if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        }
        else if (!empty($sort_by)) {
            $sort = $sort_by;
        }
        else if ($this->sort) {
            $sort = $this->sort;
        }
        else {
            $sort = $mod_pk;
        }
        if ($this->_request("order", 'trim')) {
            $order = $this->_request("order", 'trim');
        }
        else if (!empty($order_by)) {
            $order = $order_by;
        }
        else if ($this->order) {
            $order = $this->order;
        }
        else {
            $order = 'DESC';
        }
        $result = array();
        //如果需要分页
        if ($pagesize) {
            $count = $model->where($map)->count($mod_pk);
            $pager = new Page($count, $pagesize);
        }
        $select = $model->field($field_list)->where($map)->order($sort . ' ' . $order);
        $this->list_relation && $select->relation(true);
        if ($pagesize) {
            $select->limit($pager->firstRow . ',' . $pager->listRows);
            $page           = $pager->show();
            $result['page'] = $page;
            $this->assign("page", $page);
        }
        $list           = $select->select();
        $result['list'] = $list;
        //$this->assign('list', $list);
        $this->assign('list_table', true);
        return $result;
    }
    // 核对权限
    public function check_priv()
    {
        //
        if (MODULE_NAME == 'attachment') {
            return true;
        }
        $adm_sess = session('admin');
        // 若session_id为空或 方法不是login，verify_code 则跳转到登陆页面
        // 重新登录获取session数组
        if ((!$adm_sess) && !in_array(ACTION_NAME, array('login', 'verify_code'))) {
            $this->redirect('index/login');
        }
        if ($adm_sess['role_id'] == 1 || in_array(MODULE_NAME, explode(',', 'index'))) {// || substr(ACTION_NAME, 0, 5) == 'ajax_'
            return true;
        }
        $menu_mod = M('menu');
        $menu_arr = $menu_mod->where(array('module_name' => MODULE_NAME, 'action_name' => ACTION_NAME))->select();
        //var_dump($menu_arr);
        foreach ($menu_arr as $val) {
            $menu_id[] = $val['id'];
        }
        // 若menu_id 为空，则将get传过来的menu_id赋给$menu_id
        if (!$menu_id) {
            if ($_GET['menuid']) {
                $menu_id = $_GET['menuid'];
            }
        }
        if ($menu_id) {
            $where['menu_id'] = array('in', $menu_id);
        }
        $where['role_id'] = $adm_sess['role_id'];

        $priv_mod = D('admin_auth');
        $r        = $priv_mod->where($where)->count();
        if (!$r) {
            if (IS_AJAX) {
                $this->ajaxReturn(0, L('_VALID_ACCESS_'));  // L('_VALID_ACCESS_') 没有权限
            }
            else {
                $this->error(L('_VALID_ACCESS_'));          // L('_VALID_ACCESS_') 没有权限
            }
            return false;
        }
    }

    protected function update_config($new_config, $config_file = '')
    {
        !is_file($config_file) && $config_file = CONF_PATH . 'home/config.php';
        if (is_writable($config_file)) {
            $config = require $config_file;
            $config = array_merge($config, $new_config);
            file_put_contents($config_file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
            @unlink(RUNTIME_FILE);
            return true;
        }
        else {
            return false;
        }
    }
    // ajax方法返回的响应结果
    protected function ajaxReturn($status = 1, $msg = '', $data = '', $dialog = '')
    {
        parent::ajaxReturn(array(
            'status' => $status,
            'msg'    => $msg,
            'data'   => $data,
            'dialog' => $dialog,
        ));
    }

    public function search_user()
    {
        $username  = $this->_request('username', 'trim');
        $user_list = D('user')->where('username like "%' . $username . '%"')->field('id,username')->select();
        $str       = '';
        if ($user_list) {
            foreach ($user_list as $k => $v) {
                $str .= '<option value="' . $v['id'] . '">' . $v['username'] . '</option>';
            }
        }
        echo $str;
    }

    public function ajax_upload_img()
    {
        //上传图片
        if (!empty($_FILES['img']['name'])) {
            $result = $this->_upload($_FILES['img']);
            if ($result['error']) {
                $this->error($result['info']);
            }
            else {
                $data['img'] = $result['data'][0]['savePath'];
                $this->ajaxReturn(1, L('operation_success'), $data['img']);
            }
        }
        else {
            $this->ajaxReturn(0, L('illegal_parameters'));
        }
    }
}
