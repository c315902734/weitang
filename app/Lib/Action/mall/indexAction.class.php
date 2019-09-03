<?php

class indexAction extends mbaseAction
{
    public function index()
    {
        $ad_fields = 'id,img,app_type,name,content';
        $ad_list    = D('ad_app')->where(['board_id' => 1, 'status' => 1])->field($ad_fields)->select();
        $ad_list2   = D('ad_app')->where(['board_id' => 2, 'status' => 1])->field($ad_fields)->select();
        $ad_list3   = D('ad_app')->where(['board_id' => 3, 'status' => 1])->field($ad_fields)->select();
        $ad_list4   = D('ad_app')->where(['board_id' => 4, 'status' => 1])->field($ad_fields)->select();

        foreach($ad_list as $key=>$val){
            if($val['app_type'] == 'html'){
                $ad_list[$key]['url'] = $val['content'];
            } elseif($val['app_type'] == 'item_id'){
                $ad_list[$key]['url'] = U('item/index', array('id'=>$val['content']));
            } elseif($val['app_type'] == 'cate_id'){
                $ad_list[$key]['url'] = U('item_cate/index', array('pid'=>$val['content']));
            } elseif($val['app_type'] == 'keywords'){
                $ad_list[$key]['url'] = U('item/lists', array('keywords'=>$val['content']));
            }
        }
        foreach($ad_list2 as $key=>$val){
            if($val['app_type'] == 'html'){
                $ad_list2[$key]['url'] = $val['content'];
            } elseif($val['app_type'] == 'item_id'){
                $ad_list2[$key]['url'] = U('item/index', array('id'=>$val['content']));
            } elseif($val['app_type'] == 'cate_id'){
                $ad_list2[$key]['url'] = U('item_cate/index', array('pid'=>$val['content']));
            } elseif($val['app_type'] == 'keywords'){
                $ad_list2[$key]['url'] = U('item/lists', array('keywords'=>$val['content']));
            } elseif($val['app_type'] == 'topic'){
                $ad_list2[$key]['url'] = U('topic/lists');
            } elseif($val['app_type'] == 'keys'){
                if($val['content'] == 'new'){
                    $ad_list2[$key]['url'] = U('item/lists', array('sort_order'=>'desc', 'sort'=>'add_time'));
                }
            }
        }
        foreach($ad_list3 as $key=>$val){
            if($val['app_type'] == 'html'){
                $ad_list3[$key]['url'] = $val['content'];
            } elseif($val['app_type'] == 'item_id'){
                $ad_list3[$key]['url'] = U('item/index', array('id'=>$val['content']));
            } elseif($val['app_type'] == 'cate_id'){
                $ad_list3[$key]['url'] = U('item_cate/index', array('pid'=>$val['content']));
            } elseif($val['app_type'] == 'keywords'){
                $ad_list3[$key]['url'] = U('item/lists', array('keywords'=>$val['content']));
            }
        }
        foreach($ad_list4 as $key=>$val){
            if($val['app_type'] == 'html'){
                $ad_list4[$key]['url'] = $val['content'];
            } elseif($val['app_type'] == 'item_id'){
                $ad_list4[$key]['url'] = U('item/index', array('id'=>$val['content']));
            } elseif($val['app_type'] == 'cate_id'){
                $ad_list4[$key]['url'] = U('item_cate/index', array('pid'=>$val['content']));
            } elseif($val['app_type'] == 'keywords'){
                $ad_list4[$key]['url'] = U('item/lists', array('keywords'=>$val['content']));
            }
        }

        $article_list = D('article')->where(['cate_id' => 9])->field('id,title')->limit(10)->select();
        foreach ($article_list as $key => $val) {
            $article_list[$key]['url'] = full_url('api/assets/article', ['id' => $val['id']]);
        }

        $topic_list = D('topic')->where(['status' => 1,])
                                ->field('title,img,id,adv,prices')
                                ->limit(30)
                                ->select();
        foreach ($topic_list as $key => $val) {
            $topic_list[$key]['url'] = full_url('h5/topic/index', ['id' => $val['id']]);
        }
        $this->assign(compact('ad_list', 'ad_list2', 'ad_list3', 'ad_list4', 'topic_list'));
        $this->show_footer();
        $this->ajax_wall();
    }

    public function ajax_wall($where = [])
    {
        $mod        = D('item_search');
        $where_init = ['is_hots' => '1', 'status' => '1'];
        $where      = $where ? array_merge($where_init, $where) : $where_init;
        $count      = $mod->where($where)->count('id');
        if (C('ins_index_hot_item_num')) {
            $count = $count > C('ins_index_hot_item_num') ? C('ins_index_hot_item_num') : $count;
        }
        $pager = $this->_pager($count);

        $list = $mod->where($where)->field(C('item_list_fields'))->limit($pager->firstRow, $pager->listRows)->select();
        $this->assign('list', $list);
		
        if (count($list) == $pager->listRows) {
            $this->assign('show_load', 1);
            $this->assign('show_page', 1);
        }
        if (IS_AJAX) {
            $resp = $this->fetch('public:waterfall');
            $data = array(
                'isfull' => $this->_get('p') < $pager->totalPages,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }
        else {
            $this->display();
        }
    }


     public function ajax_index()
    {

        $where=[];
        // echo "string";die;
        $mod        = D('item_search');
        $where_init = ['is_hots' => '1', 'status' => '1'];
        $where      = $where ? array_merge($where_init, $where) : $where_init;
        $count      = $mod->where($where)->count('id');
        if (C('ins_index_hot_item_num')) {
            $count = $count > C('ins_index_hot_item_num') ? C('ins_index_hot_item_num') : $count;
        }
       $pager = $this->_pager($count);
        
        $list = $mod->where($where)->field(C('item_list_fields'))->limit($pager->firstRow, $pager->listRows)->select();
         $this->ajaxReturn($list);
      //  echo "string";die;
/*        $this->assign('list', $list);
        
        if (count($list) == $pager->listRows) {
            $this->assign('show_load', 1);
            $this->assign('show_page', 1);
        }
        
            $resp = $this->fetch('public:waterfall');
            $data = array(
                'isfull' => $this->_get('p') < $pager->totalPages,
                'html'   => $resp
            );*/

            //echo json_encode($list);die;
           
        
       
    }
}