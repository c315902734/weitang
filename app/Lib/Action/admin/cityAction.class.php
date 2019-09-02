<?php

class cityAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('city');
    }

    public function objectToArray($e)
    {
        $e = (array)$e;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $e[$k] = $this->objectToArray($v);
            }
        }
        return $e;
    }

    //php获取中文字符拼音首字母
    public function getFirstCharter($str)
    {
        if (empty($str)) {
            return '';
        }
        $fchar = ord($str{0});
        if ($fchar >= ord('A') && $fchar <= ord('z')) {
            return strtoupper($str{0});
        }
        $s1  = iconv('UTF-8', 'gb2312', $str);
        $s2  = iconv('gb2312', 'UTF-8', $s1);
        $s   = $s2 == $str ? $s1 : $str;
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc >= -20319 && $asc <= -20284) {
            return 'A';
        }
        if ($asc >= -20283 && $asc <= -19776) {
            return 'B';
        }
        if ($asc >= -19775 && $asc <= -19219) {
            return 'C';
        }
        if ($asc >= -19218 && $asc <= -18711) {
            return 'D';
        }
        if ($asc >= -18710 && $asc <= -18527) {
            return 'E';
        }
        if ($asc >= -18526 && $asc <= -18240) {
            return 'F';
        }
        if ($asc >= -18239 && $asc <= -17923) {
            return 'G';
        }
        if ($asc >= -17922 && $asc <= -17418) {
            return 'H';
        }
        if ($asc >= -17417 && $asc <= -16475) {
            return 'J';
        }
        if ($asc >= -16474 && $asc <= -16213) {
            return 'K';
        }
        if ($asc >= -16212 && $asc <= -15641) {
            return 'L';
        }
        if ($asc >= -15640 && $asc <= -15166) {
            return 'M';
        }
        if ($asc >= -15165 && $asc <= -14923) {
            return 'N';
        }
        if ($asc >= -14922 && $asc <= -14915) {
            return 'O';
        }
        if ($asc >= -14914 && $asc <= -14631) {
            return 'P';
        }
        if ($asc >= -14630 && $asc <= -14150) {
            return 'Q';
        }
        if ($asc >= -14149 && $asc <= -14091) {
            return 'R';
        }
        if ($asc >= -14090 && $asc <= -13319) {
            return 'S';
        }
        if ($asc >= -13318 && $asc <= -12839) {
            return 'T';
        }
        if ($asc >= -12838 && $asc <= -12557) {
            return 'W';
        }
        if ($asc >= -12556 && $asc <= -11848) {
            return 'X';
        }
        if ($asc >= -11847 && $asc <= -11056) {
            return 'Y';
        }
        if ($asc >= -11055 && $asc <= -10247) {
            return 'Z';
        }
        return null;
    }

    public function _before_index()
    {
        $big_menu = array(
            'title'  => '添加城市',
            'iframe' => U('city/add'),
            'id'     => 'add',
            'width'  => '500',
            'height' => '220',
        );
        $this->assign('big_menu', $big_menu);
    }

    public function _search()
    {
        $pid        = $this->_request('pid', 'intval', 0);
        $map['pid'] = $pid;
        $spid       = D('city')->where(array('id' => $pid))->getField('spid');
        if ($spid == 0) {
            $spid = $pid . '|';
        }
        else {
            $spid .= $pid;
        }
        $this->assign('spid', $spid);
        return $map;
    }

    public function index()
    {
        $map      = $this->_search();
        $model    = D('city');
        $pagesize = 20;
        $mod_pk   = $model->getPk();
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
            $sort = 'ordid';
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
        $select = $model->where($map)->order($sort . ' ' . $order);
        $this->list_relation && $select->relation(true);
        if ($pagesize) {
            $select->limit($pager->firstRow . ',' . $pager->listRows);
            $page = $pager->show();
            $this->assign("page", $page);
        }
        $list = $select->select();
        foreach ($list as $key => $val) {
            if ($val['pid'] == 0) {
                $tname = '顶级';
            }
            else {
                $spid  = $val['spid'];
                $ex    = explode('|', $spid);
                $count = count($ex) - 1;
                $tname = '';
                for ($i = 0; $i < $count; $i++) {
                    $tname .= D('city')->get_name($ex[$i]) . '->';
                }
                $tname = substr($tname, 0, -2);
            }
            $list[$key]['tname'] = $tname;
        }
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 添加子菜单上级默认选中本栏目
     */
    public function _before_add()
    {
        $pid = $this->_get('pid', 'intval', 0);
        if ($pid) {
            $spid = $this->_mod->where(array('id' => $pid))->getField('spid');
            $spid = $spid ? $spid . $pid : $pid;
            $this->assign('spid', $spid);
        }

    }

    /**
     * 入库数据整理
     */
    protected function _before_insert($data = '')
    {
        //检测分类是否存在
        if ($this->_mod->name_exists($data['name'], $data['pid'])) {
            $this->ajaxReturn(0, L('item_cate_already_exists'));
        }
        //生成spid
        $data['spid'] = $this->_mod->get_spid($data['pid']);
        return $data;
    }

    /**
     * 修改提交数据
     */
    protected function _before_update($data = '')
    {
        $pid = $this->_get('id', 'intval', 0);
        if ($pid) {
            $spid = $this->_mod->where(array('id' => $pid))->getField('spid');
            $spid = $spid ? $spid . $pid : $pid;
            $this->assign('spid', $spid);
        }

        if ($this->_mod->name_exists($data['name'], $data['pid'], $data['id'])) {
            $this->ajaxReturn(0, L('item_cate_already_exists'));
        }
        $item_cate = $this->_mod->field('pid')->where(array('id' => $data['id']))->find();
        if ($data['pid'] != $item_cate['pid']) {
            //不能把自己放到自己或者自己的子目录们下面
            $wp_spid_arr = $this->_mod->get_child_ids($data['id'], true);
            if (in_array($data['pid'], $wp_spid_arr)) {
                $this->ajaxReturn(0, L('cannot_move_to_child'));
            }
            //重新生成spid
            $data['spid'] = $this->_mod->get_spid($data['pid']);
        }
        return $data;
    }

    public function ajax_getchilds()
    {
        $id     = $this->_get('id', 'intval');
        $return = $this->_mod->field('id,name')->where(array('pid' => $id))->order('id')->select();
        if ($return) {
            $this->ajaxReturn(1, L('operation_success'), $return);
        }
        else {
            $this->ajaxReturn(0, L('operation_failure'));
        }

    }

    public function ajax_address_list()
    {
        $id    = $this->_post('id', 'intval', 0);
        $where = [
            'pid'    => $id,
            'status' => 1,
        ];
        $list  = D('city')->where($where)->select();
        $this->ajaxResult(compact('list'));
    }
}