<?php

class itemAction extends mbaseAction
{
    public function index()
    {
        if (is_weixin_browser()) {
            $this->load_wx_info();
        }
        $id       = $this->_get('id', 'intval', 0);
        $topic_id = $this->_get('topic_id', 'intval', 0);
        //ItemService::update_sales($id);

        $info               = D('item')->where(array('id' => $id))->find();
        $info['brand_name'] = D('item_brand')->where(array('id' => $info['brand_id']))->getField('title');
        if ($topic_id) {
            $info['stime'] = D('topic')->where(array('id' => $topic_id))->getField('stime');
            $info['etime'] = D('topic')->where(array('id' => $topic_id))->getField('etime');
        }

        $item_img = D('item_img')->where(['item_id' => $id])->field('img')->order('id asc')->select();

        $is_favs = D('item_favs')->where(['item_id' => $id, 'uid' => $this->get_visitor_id()])->count();
        if ($topic_id > 0 && $info['promotion'] > 0) {
            $info['price'] = $info['promotion'];
        }
        //店铺推荐
        $shop = D('member')->where(['id' => $info['mid']])->find();
        $this->assign(compact('shop'));

        $where          = ['mid' => $info['mid'], 'id' => ['neq', $id], 'status' => 1];
        $shop_hot_items = D('item')->where($where)->field('id,title,price,img')->limit(5)->select();
        $item_json      = [
            'id'         => $id,
            'is_favs'    => $is_favs,
            'score'      => $info['score_my'],
            'title'      => $info['title'],
            'img'        => $info['_img'],
            'url'        => full_url('item/index', [
                'invite_code' => $this->get_invite_code($this->get_visitor_id()),
                'id'          => $id
            ]),
        ];
        if ($this->is_visitor_login()) {
            $is_shop_favs              = 0; //D('member_favs')->where(['mid' => $info['mid'], 'uid' => $this->get_visitor_id()])->count() > 0;
            $item_json['is_shop_favs'] = $is_shop_favs;
        }
        else {
            $item_json['is_shop_favs'] = 0;
        }
        /*print_r($item_json);
        die;*/
        $this->assign('item_json', _json_encode($item_json));
        $comment_has_img_count = D('item_comment')->where(['item_id' => $id, 'has_img' => ['gt', 0]])->count();

        //购物流程
        $article_page = D('article_page')->where(array('id'=>10))->field('info')->find();

        $this->assign(compact('shop_hot_items', 'item_img', 'info', 'topic_id', 'comment_has_img_count', 'article_list', 'score_article', 'is_shop_favs', 'article_page'));


        $this->assign('crumb_title', $info['title'] . '-商品详情');
        $this->display();
    }

    public function ajax_item_sku()
    {
        $id = $this->_get('id', 'intval', '0');
        $this->require_login();
              
        $data = D('item')->where(compact('id'))->field('id,title,price,stock,img,score_maxs,score_times')->find();

        $data['item_sku_list'] = D('item_sku')->where(['item_id' => $id])->select();
        if(C('ins_buy_limit') == 1){
			$data['buy_limit_str'] = $this->get_buy_limit_str($id);
		}else{
			unset($data['score_maxs']);
		}
        $data['user_price'] = $this->visitor->get('price');
        $this->ajaxResult($data);
    }

    public function ajax_item_attr()
    {
        $id   = $this->_get('id', 'intval', '0');
        $item = D('item')->where(compact('id'))->field('brand_id,cate_id')->find();

        $default_attr_list = [];

        $brand_title = D('item_brand')->where(['id' => $item['brand_id']])->getField('title');
        if ($brand_title) {
            $default_attr_list[] = [
                'name' => '品牌名称',
                'val'  => $brand_title,
            ];
        }
        $cate_name = D('item_cate')->where(['id' => $item['cate_id']])->getField('name');
        if ($cate_name) {
            $default_attr_list[] = [
                'name' => '商品分类',
                'val'  => $cate_name,
            ];
        }

        $item_attr_list = D('item_attr')->where(['item_id' => $id])->field('name,val')->select();

        $item_attr_list = array_merge($default_attr_list, (array)$item_attr_list);
        $this->ajaxResult(compact('item_attr_list'));
    }

    public function ajax_item_comment_lists()
    {
        $item_id = $this->_get('item_id', 'intval', 0);
        $where   = [
            'item_id' => $item_id,
            'status'  => 1,
        ];
        if ($this->_get('type') == 'img') {
            $where['has_img'] = ['gt', 0];
        }

        $count  = D('item_comment')->where($where)->count();
        $pager  = $this->_pager($count);
        $list   = D('item_comment')->relation(true)->where($where)->limit($pager->firstRow, $pager->listRows)->select();
        $isfull = count($list) == $pager->listRows;
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['uname'] = safe_tele($v['uname']);
            }
        }
        $data = compact('list', 'isfull');
        $this->ajaxResult($data);
    }

    public function ajax_hot_item_lists()
    {
        $item_id = $this->_get('item_id', 'intval', 0);
        $item    = D('item')->where(['id' => $item_id])->field('cate_id')->find();
        $where   = [
            'cate_id' => $item['cate_id'],
            'id'      => ['neq', $item_id],
            'is_hots' => 1,
        ];
        $count   = D('item')->where($where)->count();
        $pager   = $this->_pager($count);
        $list    = D('item')->field('id,title,img,price,mprice')->where($where)->limit($pager->firstRow, $pager->listRows)->select();

        $isfull = count($list) == $pager->listRows;
        $this->assign('list', $list);
        $html = $this->fetch('public:waterfall');
        $data = compact('isfull', 'html');

        $this->ajaxResult($data);
    }

    public function cates()
    {
        $cate_list = D('item_cate')->where(array('pid' => 0))->order('ordid desc,id desc')->select();
        $this->assign(compact('cate_list'));
        $this->assign('crumb_title', '分类');
        $this->display();
    }

    public function lists()
    {
        $cate_id   = $this->_request('cate_id', 'intval');
        $where     = [
            'status'        => 1,
            'is_on_sale'    => 1,
            'member_status' => 1
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

    public function favs()
    {
        $id = $this->_post('id', 'intval');
        if (!$id) {
            $this->ajaxResultError('参数错误');
        }
        if (!$this->visitor->is_login) {
            $this->ajaxResultError('请登录');
        }
        $map['uid']     = $this->visitor->info['id'];
        $map['item_id'] = $id;

        if (D('item_favs')->where($map)->count('id') > 0) {
			D('user')->where(['id' => $this->get_visitor_id()])->setDec('favs',1);
            D('item_favs')->where($map)->delete();
            $this->ajaxResultSuccess();
        }
        else {
			D('user')->where(['id' => $this->get_visitor_id()])->setInc('favs',1);
            D('item_favs')->add(array(
                'uid'      => $this->get_visitor_id(),
                'item_id'  => $id,
                'add_time' => date('Y-m-d H:i:s'),
            ));
            $this->ajaxResultSuccess();
        }
    }

    /*
     * 购买人数
     * */
    public function buy_list()
    {
        $item_id = $this->_get('item_id', 'intval');

        if (IS_AJAX) {
            $where = [
                "item_id"      => $item_id,
                'order_status' => ['in', [1, 2, 5, 6]],
            ];

            $count = D('order_item_view')->where($where)->count();
            $pager = $this->_pager($count, 20);
            $list  = D('order_item_view')->field('uname,add_time,nums')
                ->where($where)
                ->limit($pager->firstRow, $pager->listRows)
                ->select();
            foreach ($list as $key => $val) {
                $list[$key]['uname'] = safe_tele($val['uname']);
            }

            $isfull = count($list) == $pager->listRows;
            $this->ajaxResult(compact('list', 'isfull'));
        }
        else {

            $this->display();
        }
    }

    public function screen()
    {
        $this->display();
    }

    public function get_buy_limit_str($id)
    {
        $info = D('item')->where(compact('id'))->field('score_times,score_maxs')->find();
        if ($info['score_times'] == 0 && $info['score_maxs'] > 0) {
            return "本商品限购$info[score_maxs]件";
        }
        if ($info['score_times'] > 0 && $info['score_maxs'] == 0) {
            return "本商品$info[score_times]天限购1件";
        }
        if ($info['score_times'] > 0 && $info['score_maxs'] > 0) {
            return "本商品$info[score_times]天限购$info[score_maxs]件";
        }
        return "";
    }
}