<?php 
/**
* ZhiPHP 值得买模式的海淘网站程序
* ====================================================================
* 版权所有 杭州言商网络有限公司，并保留所有权利。
* 网站地址: http://www.zhiphp.com
* 交流论坛: http://bbs.pinphp.com
* --------------------------------------------------------------------
* 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
* 使用；不允许对程序代码以任何形式任何目的的再发布。
* ====================================================================
* Author: brivio 
* 授权技术支持: 1142503300@qq.com
*/
class oauthAction extends backendAction {
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('oauth');
    }
    public function index() {
        $list = $this->_mod->get_installed();
        $opdir = dir(LIB_PATH . 'Inslib/oauth');
        while (false !== ($entry = $opdir->read())) {
            if ($entry{0} == '.') {
                continue;
            }
            if (!isset($list[$entry])) {
                $info = $this->_mod->get_file_info($entry);
                $info['status'] = '-1';
                $info['ordid'] = '0';
                $info['id'] = '0';
                $list[$entry] = $info;
            }
        }
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }
    public function edit() {
        if (IS_POST) {
            if (false === $data = $this->_mod->create()) {
                $this->ajaxReturn(0, $this->_mod->getError());
            }
            $info = $this->_mod->get_file_info($data['code']);
            foreach ($info['config'] as $key=>$val) {
                $config[$key] = $this->_post($key);
            }
            $this->_mod->config = serialize($config);
            if (false !== $this->_mod->save()) {
                $this->ajaxReturn(1, L('operation_success'), '', 'edit');
            } else {
                $this->ajaxReturn(0, L('operation_failure'), '', 'edit');
            }
        } else {
            $id = $this->_get('id', 'intval');
            $info = $this->_mod->find($id);
            $info['config'] = unserialize($info['config']);
            $this->assign('info', $info);
            $file_info = $this->_mod->get_file_info($info['code']);
            $this->assign('file_info', $file_info);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }
    public function install() {
        if (IS_POST) {
            if (false === $data = $this->_mod->create()) {
                $this->ajaxReturn(0, $this->_mod->getError());
            }
            $info = $this->_mod->get_file_info($data['code']);
            foreach ($info['config'] as $key=>$val) {
                $config[$key] = $this->_post($key);
            }
            $this->_mod->config = serialize($config);
            if ($this->_mod->add()) {
                $this->ajaxReturn(1, L('install_success'), '', 'install');
            } else {
                $this->ajaxReturn(0, L('install_failure'), '', 'install');
            }
        } else {
            $code = $this->_get('code', 'trim');
            $info = $this->_mod->get_file_info($code);
            $this->assign('info', $info);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }
}