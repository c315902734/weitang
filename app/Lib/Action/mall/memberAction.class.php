<?php

class memberAction extends mbaseAction
{
    public function index()
    {
        $mid  = $this->_get('mid', 'intval', 0);
        $shop = D('member')->where(['id' => $mid])->find();
        if ($shop['status'] == 0) {
            $this->redirect(U('index/index'));
        }
        $this->assign(compact('shop'));
        if ($this->is_visitor_login()) {
            $is_shop_favs = D('member_favs')->where(['mid' => $mid, 'uid' => $this->get_visitor_id()])->count() > 0;
            $this->assign('is_shop_favs', $is_shop_favs);
        }

        //新品上架
        $where     = [
            'mid'    => $mid,
            'status' => 1,
        ];
        $new_items = D('item')->where($where)->limit(2)->order('ordid ASC,add_time DESC')->select();
        $this->assign(compact('new_items'));
        $this->ajax_wall();
    }

    public function ajax_wall($where = [], $order = 'id DESC', $page_size = 10)
    {
        $mod        = D('item');
        $where_init = array(
            'is_on_sale' => '1',
            'status'     => '1',
            'mid'        => $this->_get('mid', 'intval', 0),
        );

        $cate_id = $this->_get('cate_id', 'intval');
        if ($cate_id) {
            $where_init['cate_id'] = $cate_id;
        }

        $where = $where ? array_merge($where_init, $where) : $where_init;
        $count = $mod->where($where)->count('id');

        $pager = $this->_pager($count, $page_size);

        $list = $mod->where($where)->field(C('item_list_fields'))->limit($pager->firstRow . ',' . $page_size)->order('ordid DESC,add_time DESC')->select();

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
        if ($p > 1) {
            $resp = $this->fetch('public:waterfall');
            $data = array(
                'isfull' => $p >= $cp ? 0 : 1,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }
        else {
            $this->display();
        }
    }

    public function favs()
    {

        $mid = $this->_post('mid', 'intval');
        if (!$mid) {
            $this->ajaxResultError('参数错误');
        }
        $this->require_login();
        $map['uid'] = $this->get_visitor_id();
        $map['mid'] = $mid;

        if (D('member_favs')->where($map)->count('id') > 0) {
            D('member_favs')->where($map)->delete();
        }
        else {
            D('member_favs')->add(array(
                'uid' => $this->get_visitor_id(),
                'mid' => $mid,
            ));
        }
        $favs = D('member_favs')->where(compact('mid'))->count();
        D('member')->where(['id' => $mid])->save(compact('favs'));
        $this->ajaxResult(compact('favs'));
    }

    public function contact($mid)
    {
        $member             = D('member')->where(['id' => $mid])->find();
        $pre_sale_qq_list   = explode(',', $member['pre_sale_qq']);
        $after_sale_qq_list = explode(',', $member['after_sale_qq']);
        $tele_list          = explode(',', $member['servicetel']);

        if (C('ins_service_qq')) {
            $service_qq_list = explode(',', trim(C('ins_service_qq')));
        }

        if (C('ins_service_weixin')) {
            $service_weixin_list = explode(',', trim(C('ins_service_weixin')));
        }

        if (C('ins_service_tele')) {
            $service_tele_list = explode(',', trim(C('ins_service_tele')));
        }

        $this->assign(compact('member', 'pre_sale_qq_list', 'after_sale_qq_list', 'tele_list', 'service_qq_list', 'service_weixin_list', 'service_tele_list'));

        $this->display();
    }

    public function cate()
    {
        $mid = $this->_get('mid', 'intval');

        $res            = D('item')->field('cate_id')->where(['mid' => $mid])->distinct('cate_id')->select();
        $item_cate_list = [];
        foreach ($res as $key => $val) {
            $item_cate_list[] = $val['cate_id'];
            $pid              = D('item_cate')->where(['id' => $val['cate_id']])->getField('pid');
            if ($pid > 0) {
                $item_cate_list[] = $pid;
                $pid              = D('item_cate')->where(['id' => $pid])->getField('pid');
                if ($pid > 0) {
                    $item_cate_list[] = $pid;
                }
            }
        }

        $cate_where = ['status' => 1, 'pid' => 0];
        $cate_list  = D('item_cate')->where($cate_where)->select();

        foreach ($cate_list as $key => $val) {
            if (!in_array($val['id'], $item_cate_list)) {
                unset($cate_list[$key]);
            }
            else {
                $cate_where['pid'] = $val['id'];
                $sub_cate_list     = D('item_cate')->where($cate_where)->select();
                foreach ($sub_cate_list as $k => $v) {
                    if (!in_array($v['id'], $item_cate_list)) {
                        unset($sub_cate_list[$key]);
                    }
                }
                $cate_list[$key]['sub_cate_list'] = $sub_cate_list;
            }
        }
        $member = D('member')->where(['id' => $mid])->find();
        $this->assign(compact('sub_cate_list', 'member'));
        $this->display();
    }
}