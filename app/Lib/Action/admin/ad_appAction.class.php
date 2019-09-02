<?php

class ad_appAction extends backendAction
{

    private $_ad_type = array('cate_id' => '分类', 'item_id' => '商品详情', 'keywords' => '商品搜索', 'html' => 'html页面','kan' => '0元砍价', 'yi' => '一元夺宝');
    private $_ad_type2 = array('限时购', '爆款', '限量版', '团购');
    public $list_relation = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->_mod         = D('ad_app');
        $this->_adboard_mod = D('adboard');
    }

    public function _search()
    {
        $map = array();
        ($start_time_min = $this->_request('start_time_min', 'trim')) && $map['start_time'][] = array('egt', strtotime($start_time_min));
        ($start_time_max = $this->_request('start_time_max', 'trim')) && $map['start_time'][] = array('elt', strtotime($start_time_max) + (24 * 60 * 60 - 1));
        ($end_time_min = $this->_request('end_time_min', 'trim')) && $map['end_time'][] = array('egt', strtotime($end_time_min));
        ($end_time_max = $this->_request('end_time_max', 'trim')) && $map['end_time'][] = array('elt', strtotime($end_time_max) + (24 * 60 * 60 - 1));
        $board_id = $this->_get('board_id', 'intval');
        $board_id && $map['board_id'] = $board_id;
        $style = $this->_request('style', 'trim');
        $style && $map['app_type'] = array('eq', $style);
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%' . $keyword . '%');
        $this->assign('search', array(
            'start_time_min' => $start_time_min,
            'start_time_max' => $start_time_max,
            'end_time_min'   => $end_time_min,
            'end_time_max'   => $end_time_max,
            'board_id'       => $board_id,
            'style'          => $style,
            'keyword'        => $keyword,
        ));
        return $map;
    }

    public function _before_index()
    {
        $big_menu = array(
            'title'  => L('ad_add'),
            'iframe' => U('ad_app/add'),
            'id'     => 'add',
            'width'  => '520',
            'height' => '300',
        );
        $this->assign('big_menu', $big_menu);

        $res        = $this->_adboard_mod->where(array('status' => 1, 'type' => 2))->field('id,name')->select();
        $board_list = array();
        foreach ($res as $val) {
            $board_list[$val['id']] = $val['name'];
        }
        $this->assign('board_list', $board_list);
        $this->assign('ad_type_arr', $this->_ad_type);
        $this->assign('ad_type_arr2', $this->_ad_type2);
    }

    public function _before_add()
    {
        $adboards = $this->_adboard_mod->where(array('status' => 1, 'type' => 2))->select();
        $this->assign('adboards', $adboards);
        $this->assign('ad_type_arr', $this->_ad_type);
        $this->assign('ad_type_arr2', $this->_ad_type2);
    }

    protected function _before_insert($data)
    {
        //判断开始时间和结束时间是否合法
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time']   = strtotime($data['end_time']);
        if ($data['start_time'] >= $data['end_time']) {
            $this->ajaxReturn(0, L('ad_endtime_less_startime'));
        }

        switch ($data['app_type']) {
            case 'cate_id':
                $data['content'] = $this->_post('cate_id', 'intval');
                break;
            case 'item_id':
                $data['content'] = $this->_post('item_id', 'intval');
                break;
            case 'keywords':
                $data['content'] = $this->_post('keywords', 'trim');
                break;
            case 'html':
                $data['content'] = $this->_post('html', 'trim');
                break;
            case 'kan':
                $data['content'] = $this->_post('kan', 'trim');
                break;
            case 'yi':
                $data['content'] = $this->_post('yi', 'trim');
                break;
            default :
                $this->ajaxReturn(0, L('ad_type_error'));
                break;
        }
        return $data;
    }

    public function _before_edit()
    {
        $id         = $this->_get('id', 'intval');
        $board_id   = $this->_mod->where(array('id' => $id))->getField('board_id');
        $board_info = $this->_adboard_mod->field('name,width,height')->where(array('id' => $board_id))->find();
        $this->assign('board_info', $board_info);
        $this->assign('ad_type_arr', $this->_ad_type);
        $this->assign('ad_type_arr2', $this->_ad_type2);
    }

    protected function _before_update($data)
    {
        //判断开始时间和结束时间是否合法
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time']   = strtotime($data['end_time']);
        if ($data['start_time'] >= $data['end_time']) {
            $this->ajaxReturn(0, L('ad_endtime_less_startime'));
        }
        switch ($data['app_type']) {
            case 'cate_id':
                $data['content'] = $this->_post('cate_id', 'intval');
                break;
            case 'item_id':
                $data['content'] = $this->_post('item_id', 'intval');
                break;
            case 'keywords':
                $data['content'] = $this->_post('keywords', 'trim');
                break;
            case 'html':
                $data['content'] = $this->_post('html', 'trim');
                break;
            case 'kan':
                $data['content'] = $this->_post('kan', 'trim');
                break;
            case 'yi':
                $data['content'] = $this->_post('yi', 'trim');
                break;
            default :
                $this->ajaxReturn(0, L('ad_type_error'));
                break;
        }
        return $data;
    }
}