<?php

class promotionAction extends mbaseAction
{

    public function index()
    {
        $cate_id   = $this->_request('cate_id', 'intval');
        $where     = [
            'status'        => 1,
            'is_on_sale'    => 1,
            'member_status' => 1,
            'is_hots' => 1,
        ];
        $item_type = $this->_request('item_type', 'intval');
        if ($item_type > 0) {
            $where['type'] = $item_type;
        }
        if ($cate_id) {
            $this->assign('cate_id', $cate_id);
            //获取分类下所有的子分类
            $spid     = D('item_cate')->where(array('id' => $cate_id))->getField('spid');
            $spid     = $spid ? $spid .= $cate_id . '|' : $cate_id . '|';
            $id_arr   = D('item_cate')->field('id')->where(array('spid' => array('like', $spid . '%')))->select();
            $cate_ids = array();
            foreach ($id_arr as $val) {
                $cate_ids[] = $val['id'];
            }
            $cate_ids[]       = $cate_id;
            $where['cate_id'] = ['in', $cate_ids];
        }
        $keywords = $this->_get('keywords', 'trim');
        if ($keywords) {
            $where['title'] = ['like', "%$keywords%"];
            $this->assign(compact('keywords'));
        }
        ($sprice = $this->_request('sprice', 'trim')) && $where['price'][] = array('egt', $sprice);
        ($eprice = $this->_request('eprice', 'trim')) && $where['price'][] = array('elt', $eprice);

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
        else if ($sort == 'add_time') {
            $order = $sort . ' ' . $sort_order;
            $this->assign('time_sort_order', $sort_order == 'desc' ? 'asc' : 'desc');
        }
        else {
            $order = "is_hots desc";
        }

        if (!$this->get('sales_sort_order')) {
            $this->assign('sales_sort_order', 'desc');
        }
        if (!$this->get('price_sort_order')) {
            $this->assign('price_sort_order', 'asc');
        }

        $this->assign('sort');
        if (!empty($sort)) {
            $order .= ',ordid asc';
        }
        $this->assign('crumb_title', '商品列表');
        $this->ajax_wall($where, $order);
    }

    public function ajax_wall($where = array(), $order = 'id desc', $page_size = 10)
    {
        $mod   = D('item_search');
        $count = $mod->where($where)->count('id');

        $pager = $this->_pager($count);

        $list = $mod->where($where)->field(C('item_list_fields'))->limit($pager->firstRow . ',' . $page_size)->order($order)->select();

        $this->assign('list', $list);
        //当前页码
        $p  = $this->_get('p', 'intval', 1);
        $cp = ceil($count / $page_size);
        $this->assign(compact('p', 'cp'));
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

}