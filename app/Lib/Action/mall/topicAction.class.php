<?php

class topicAction extends mbaseAction
{
    public function index()
    {
        $id    = $this->_get('id');
        $where = array('topic_id' => $id);
        $info  = D('topic')->where(array('id' => $id))->find();

        $sort       = $this->_request('sort', 'trim', 'id');
        $sort_order = $this->_request('sort_order', 'trim', 'desc');
        if ($sort == 'sales') {
            $order = $sort . ' ' . $sort_order;
            $this->assign('sales_sort_order', $sort_order == 'desc' ? 'asc' : 'desc');
        }
        else if ($sort == 'price') {
            $order = $sort . ' ' . $sort_order;
            $this->assign('price_sort_order', $sort_order == 'desc' ? 'asc' : 'desc');
        }
        else {
            $order = "id desc";
        }

        if (!$this->get('sales_sort_order')) {
            $this->assign('sales_sort_order', 'desc');
        }
        if (!$this->get('price_sort_order')) {
            $this->assign('price_sort_order', 'asc');
        }

        $this->assign('crumb_title', $info['title']);
        $this->assign('info', $info);
        $this->assign('topic_json', _json_encode(Arr::pick($info, 'stime,etime')));
        $this->ajax_wall($where, $order);

    }

    public function ajax_wall($where = array(), $order = 'id DESC', $page_size = 5)
    {
        $id  = $this->_get('id');
        $mod = D('topic_item_search');

        $count = $mod->where($where)->count('id');
        $pager = $this->_pager($count);

        $topic_item_list = $mod->where($where)->field('item_id')->limit($pager->firstRow, $pager->listRows)->order($order)->select();

        $list = [];
        foreach ($topic_item_list as $val) {
            $list[] = D('item')->where(['id' => $val['item_id']])->find();
        }

        $this->assign('topic_id', $id);
        $this->assign('list', $list);
        //当前页码
        $p  = $this->_get('p', 'intval', 1);
        $cp = ceil($count / $page_size);
        $this->assign('p', $p);
        $this->assign('cp', $cp);
        if ($p < $cp) {
            $this->assign('show_load', 1);
            $this->assign('show_page', 1);
        }
        if (IS_AJAX) {
            $resp = $this->fetch('public:waterfall');
            $data = array(
                'isfull' => $p >= $cp ? 0 : 1,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }
        else {
            $this->show_footer();
            $this->display();
        }
    }

    public function lists()
    {
        $list = D('topic')->where(['status' => 1,])
                          ->field('title,img,id,adv,prices')
                          ->select();
        
        $this->assign(compact('list'));
        $this->show_footer();

        $this->display();
    }
}