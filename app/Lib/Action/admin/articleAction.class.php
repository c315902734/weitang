<?php

class articleAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod      = D('article');
        $this->_cate_mod = D('article_cate');
    }

    public function _before_index()
    {
        $res       = $this->_cate_mod->field('id,name')->select();
        $cate_list = array();
        foreach ($res as $val) {
            $cate_list[$val['id']] = $val['name'];
        }
        $this->assign('cate_list', $cate_list);

        $p = $this->_get('p', 'intval', 1);
        $this->assign('p', $p);

        //默认排序
        $this->sort  = 'ordid';
        $this->order = 'ASC';
    }

    protected function _search()
    {
        $map = array();
        ($time_start = $this->_request('time_start', 'trim')) && $map['add_time'][] = array('egt', strtotime($time_start));
        ($time_end = $this->_request('time_end', 'trim')) && $map['add_time'][] = array('elt', strtotime($time_end) + (24 * 60 * 60 - 1));
        ($status = $this->_request('status', 'trim')) && $map['status'] = $status;
        ($keyword = $this->_request('keyword', 'trim')) && $map['title|tags|author|abst'] = array('like', '%' . $keyword . '%');
        $cate_id      = $this->_request('cate_id', 'intval');
        $selected_ids = '';
        if ($cate_id) {
            $id_arr         = $this->_cate_mod->get_child_ids($cate_id, true);
            $map['cate_id'] = array('IN', $id_arr);
            $spid           = $this->_cate_mod->where(array('id' => $cate_id))->getField('spid');
            $selected_ids   = $spid ? $spid . $cate_id : $cate_id;
        }
        $this->assign('search', array(
            'time_start'   => $time_start,
            'time_end'     => $time_end,
            'cate_id'      => $cate_id,
            'selected_ids' => $selected_ids,
            'status'       => $status,
            'keyword'      => $keyword,
        ));
        return $map;
    }

    public function _before_add()
    {
        $author = $_SESSION['pp_admin']['username'];
        $this->assign('author', $author);

        $site_name = D('setting')->where(array('name' => 'site_name'))->getField('data');
        $this->assign('site_name', $site_name);

        $first_cate = $this->_cate_mod->field('id,name')->where(array('pid' => 0))->order('ordid DESC')->select();
        $this->assign('first_cate', $first_cate);

    }

    protected function _before_insert($data)
    {
        //上传图片
        if (!empty($_FILES['img']['name'])) {
            $result       = $this->_upload($_FILES['img']);
            if ($result['error']) {
                $this->error($result['info']);
            } else {
                $data['img'] = $result['data'][0]['savePath'];
            }
        }
        return $data;
    }

    public function _after_insert($id){
        $info = $this->_mod->where(array('id'=>$id))->field('id,img,info')->find();
        if($info['img']){
            $data['type'] = 1;
            $data['article_id'] = $id;
            $data['admin_id'] = $_SESSION['admin']['id'];
            $data['admin_name'] = $_SESSION['admin']['username'];
            $data['img'] = $info['img'];
            $data['add_time'] = date('Y-m-d H:i:s');
            D('article_img')->add($data);
        }
        preg_match_all('/<img.*\/>/iUs', $info['info'], $out);
        if($out){
            $out_img = $out[0];
            $count = count($out_img);
            for($i=0;$i<$count;$i++){
                $img_path = $out_img[$i];
                $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
                preg_match_all($preg, $img_path, $imgArr);
                $img = $imgArr[1][0];

                $data['type'] = 2;
                $data['article_id'] = $id;
                $data['admin_id'] = $_SESSION['admin']['id'];
                $data['admin_name'] = $_SESSION['admin']['username'];
                $data['img'] = $img;
                $data['add_time'] = date('Y-m-d H:i:s');
                D('article_img')->add($data);
            }
        }
    }

    public function _before_edit()
    {
        $id      = $this->_get('id', 'intval');
        $article = $this->_mod->field('id,cate_id')->where(array('id' => $id))->find();
        $spid    = $this->_cate_mod->where(array('id' => $article['cate_id']))->getField('spid');
        if ($spid == 0) {
            $spid = $article['cate_id'];
        } else {
            $spid .= $article['cate_id'];
        }
        $this->assign('selected_ids', $spid);
    }

    protected function _before_update($data)
    {
        if (!empty($_FILES['img']['name'])) {
            //上传新图
            $result = $this->_upload($_FILES['img']);
            if ($result['error']) {
                $this->error($result['info']);
            } else {
                $data['img'] = $result['data'][0]['savePath'];
            }
        } else {
            unset($data['img']);
        }

        return $data;
    }

    public function _after_update($id){
        $info = $this->_mod->where(array('id'=>$id))->field('id,img,info')->find();
        if($info['img']){
            D('article_img')->where(array('article_id'=>$id,'type'=>1))->delete();
            $data['type'] = 1;
            $data['article_id'] = $id;
            $data['admin_id'] = $_SESSION['admin']['id'];
            $data['admin_name'] = $_SESSION['admin']['username'];
            $data['img'] = $info['img'];
            $data['add_time'] = date('Y-m-d H:i:s');
            D('article_img')->add($data);
        }
        preg_match_all('/<img.*\/>/iUs', $info['info'], $out);
        if($out){
            D('article_img')->where(array('article_id'=>$id,'type'=>2))->delete();
            $out_img = $out[0];
            $count = count($out_img);
            for($i=0;$i<$count;$i++){
                $img_path = $out_img[$i];
                $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
                preg_match_all($preg, $img_path, $imgArr);
                $img = $imgArr[1][0];

                $data['type'] = 2;
                $data['article_id'] = $id;
                $data['admin_id'] = $_SESSION['admin']['id'];
                $data['admin_name'] = $_SESSION['admin']['username'];
                $data['img'] = $img;
                $data['add_time'] = date('Y-m-d H:i:s');
                D('article_img')->add($data);
            }
        }
    }

    /**
     * 单页管理
     */
    public function page()
    {
        $prefix = C('DB_PREFIX');
        $sort   = $this->_request("sort", 'trim', 'ordid');
        $order  = $this->_request("order", 'trim', 'DESC');

        $tree       = new Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = $this->_cate_mod->field('id,pid,name')->join($prefix .'article_page on '.$prefix .'article_page.cate_id ='.$prefix .'article_cate.id')->where(array('type'=>1))->order($sort . ' ' . $order)->select();
        //print_r($result);die;
        $array = array();
        foreach($result as $r) {
            //是否有下一级
            if ($this->_cate_mod->where(array('pid' => $r['id']))->count('id')) {
                $r['str_manage'] = '';
            } else {
                $r['str_manage'] = '<a href="' . U('article/page_edit', array('cate_id' => $r['id'])) . '">' . L('edit') . '</a>';
            }
            $r['parentid_node'] = ($r['pid']) ? ' class="child-of-node-' . $r['pid'] . '"' : '';
            $array[]            = $r;
        }

        $str = "<tr id='node-\$id' \$parentid_node>
                <td align='center'>\$id</td>
                <td>\$spacer\$name</td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $list = $tree->get_tree(0, $str);
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 单页内容编辑
     */
    public function page_edit()
    {
        $page_mod = D('article_page');
        if (IS_POST) {
            if (false === $data = $page_mod->create()) {
                $this->error($page_mod->getError());
            }
            if (!$page_mod->where(array('cate_id' => $data['cate_id']))->count()) {
                $page_mod->add($data);
            } else {
                $page_mod->save($data);
            }
            $this->success(L('operation_success'), U('article/page'));
        } else {
            $cate_id   = $this->_get('cate_id', 'intval');
            $cate_info = $this->_cate_mod->field('id,name')->where(array('type' => 1, 'id' => $cate_id))->find();
            !$cate_info && $this->redirect('article/page');
            $this->assign('cate_info', $cate_info);
            $info = $page_mod->where(array('cate_id' => $cate_id))->find();
            $this->assign('info', $info);
            $this->display();
        }
    }


    /**
     * ajax获取标签
     */
    public function ajax_gettags()
    {
        $title = $this->_get('title', 'trim');
        if ($title) {
            $tags = D('tags')->get_tags_by_title($title);
            $tags = implode(' ', $tags);
            $this->ajaxReturn(1, L('operation_success'), $tags);
        } else {
            $this->ajaxReturn(0, L('operation_failure'));
        }
    }
}