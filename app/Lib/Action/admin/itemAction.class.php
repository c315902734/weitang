<?php

class itemAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod      = D('item');
        $this->_cate_mod = D('item_cate');
    }


    protected function _search()
    {
        $map = array();
        ($stime = $this->_request('stime', 'trim')) && $map[C('DB_PREFIX') . 'item.add_time'][] = array('egt', $stime);
        ($etime = $this->_request('etime', 'trim')) && $map[C('DB_PREFIX') . 'item.add_time'][] = array('elt', $etime);
        ($title = $this->_request('title', 'trim')) && $map[C('DB_PREFIX') . 'item.title'] = array('like', '%' . $title . '%');
        ($brand_id = $this->_request('brand_id', 'trim')) && $map[C('DB_PREFIX') . 'item.brand_id'] = $brand_id;

        $cate_id = $this->_request('cate_id', 'intval');
        if ($cate_id) {
            $id_arr                               = $this->_cate_mod->get_child_ids($cate_id, true);
            $map[C('DB_PREFIX') . 'item.cate_id'] = array('IN', $id_arr);
            $spid                                 = $this->_cate_mod->where(array('id' => $cate_id))->getField('spid');
            if ($spid == 0) {
                $spid = $cate_id;
            }
            else {
                $spid .= $cate_id;
            }
        }
        if ($_GET['status'] == null) {
            $status = -1;
        }
        else {
            $status = intval($_GET['status']);
        }
        $status >= 0 && $map[C('DB_PREFIX') . 'item.status'] = array('eq', $status);
        ($keyword = $this->_request('keyword', 'trim')) && $map[C('DB_PREFIX') . 'item.title'] = array('like', '%' . $keyword . '%');
        ($is_hots = $this->_request('is_hots', 'trim')) && $map[C('DB_PREFIX') . 'item.is_hots'] = $is_hots;
        ($is_new = $this->_request('is_new', 'trim')) && $map[C('DB_PREFIX') . 'item.is_new'] = $is_new;
        if ($is_hots == 2) {
            $map[C('DB_PREFIX') . 'item.is_hots'] = 0;
        }
        if ($is_new == 2) {
            $map[C('DB_PREFIX') . 'item.is_new'] = 0;
        }
        $type = $this->_request('type', 'intval');
        if ($type) {
            $map[C('DB_PREFIX') . 'item.type'] = $type;
        }
        $this->assign('search', array(
            'stime'        => $stime,
            'etime'        => $etime,
            'status'       => $status,
            'selected_ids' => $spid,
            'cate_id'      => $cate_id,
            'title'        => $title,
            'keyword'      => $keyword,
            'is_hots'      => $is_hots,
            'is_new'       => $is_new,
            'brand_id'     => $brand_id,
            'type'         => $type,
        ));

        return $map;
    }

    public function _before_index()
    {
        $cate_list = D('item_cate')->select();
        foreach ($cate_list as $key => $val) {
            $clist[$val['id']] = $val['name'];
        }
        $this->assign('cate_list', $clist);

        $brand_list = D('item_brand')->where(array('status' => 1))->select();
        $blist      = array();
        foreach ($brand_list as $key => $val) {
            $blist[$val['id']] = $val['title'];
        }
        $this->assign('brand_list', $blist);

        $this->assign('sbrand_list', D('item_brand')->where(array('status' => 1))->select());
    }

    public function _before_add()
    {
        $brand_list = D('item_brand')->where(array('status' => 1))->select();
        $this->assign('brand_list', $brand_list);
        //发货方式
        $express_list = array();
        foreach (C('EXPRESS_LIST') as $key => $val) {
            $express_list[] = array(
                'title'        => $val['title'],
                'tip'          => $val['tip'],
                'express_type' => $key
            );
        }
        $cate_tree = $this->_get_cate_tree(get_cate_tree(D("item_cate")));
        $this->assign('cate_tree', $cate_tree);
        $this->assign('express_list', $express_list);

    }

    public function _before_edit()
    {
        $id = $this->_get('id', 'intval');
        //获取积分分类
        $articleCate = $this->_mod->field('id,cate_id')->where(array('id' => $id))->find();
        $scatepid    = D('item_cate')->where(array('id' => $articleCate['cate_id']))->getField('spid');

        if ($scatepid == 0) {
            $scatepid = $articleCate['cate_id'];
        }
        else {
            $scatepid .= $articleCate['cate_id'];
        }
        $this->assign('selected_cate_ids', $scatepid);

        $id = $this->_get('id', 'intval');
        //相册
        $img_list = D('item_img')->where(array('item_id' => $id))->select();
        //print_r($img_list);die;
        $this->assign('img_list', $img_list);
        //发货方式
        $express_list = array();
        foreach (C('EXPRESS_LIST') as $key => $val) {
            $express_list[] = array(
                'title'        => $val['title'],
                'tip'          => $val['tip'],
                'express_type' => $key
            );
        }
        $where = array('item_id' => $id);
        
        $sku_list = D('item_sku')->where(array('item_id' => $id))->select();
        $this->assign('sku_list', $sku_list);

        $attr_list = D('item_attr')->where(array('item_id' => $id))->select();
        $this->assign('attr_list', $attr_list);

        $this->assign('express_list', $express_list);

        $brand_list = D('item_brand')->where(array('status' => 1))->select();
        $this->assign('brand_list', $brand_list);
        
        $brand_id = D('item')->where(['id' => $id])->getField('brand_id');
        $this->assign('brand', D('item_brand')->where(['id' => $brand_id])->find());
    }

    public function delete_img()
    {
        $album_mod = D('item_img');
        $album_id  = $this->_get('album_id', 'intval');
        $album_img = $album_mod->where('id=' . $album_id)->getField('img');
        if ($album_img) {
            $ext    = array_pop(explode('.', $album_img));
            $sm_img = C('ins_attach_path') . 'assets' . str_replace('.' . $ext, '_small.' . $ext, $album_img);

            is_file($sm_img) && @unlink($sm_img);

            $mid_img = C('ins_attach_path') . 'assets' . str_replace('.' . $ext, '_middle.' . $ext, $album_img);
            is_file($mid_img) && @unlink($mid_img);

            $img = C('ins_attach_path') . 'assets' . str_replace('.' . $ext, '_big.' . $ext, $album_img);
            is_file($img) && @unlink($img);

            $big_img = C('ins_attach_path') . 'assets' . $album_img;
            is_file($big_img) && @unlink($big_img);

            $album_mod->delete($album_id);
        }
        echo '1';
        exit;
    }

    //商品分类树
    protected function _get_cate_tree($list, $checked_ids = array())
    {
        $html = "";
        foreach ($list as $key => $val) {
            $margin_left = $val['depth'] * 20;
            $html .= "<div style='margin-left:" . $margin_left . "px;'>
                <input type='checkbox'";
            if (in_array($val['id'], $checked_ids)) {
                $html .= " checked='checked' ";
            }
            $html .= " name='cate_id[]' value='$val[id]'/>&nbsp;&nbsp;$val[name]</div>";
            $html .= $this->_get_cate_tree($val['child'], $checked_ids);
        }
        return $html;
    }

    public function favs()
    {
        $user_list = D('user')->select();
        foreach ($user_list as $k => $v) {
            $user_list[$v['id']] = $v;
        }
        $this->assign('user_list', $user_list);
        $item_list = D('item')->select();
        foreach ($item_list as $k => $v) {
            $item_list[$v['id']] = $v;
        }
        $this->assign('item_list', $item_list);
        $this->_list(D('item_favs'), $this->_search_favs());
        $this->display();
    }

    public function _search_favs()
    {
        $map = array();
        ($item_name = $this->_request('item_name', 'trim')) && $map['item_id'] = array('in', $this->_get_iids($item_name));
        ($user_name = $this->_request('user_name', 'trim')) && $map['uid'] = array('in', $this->_get_uids($user_name));

        $this->assign('search', array(
            'item_name' => $item_name,
            'user_name' => $user_name
        ));
        return $map;
    }

    /**
     * 删除
     */
    public function like_delete()
    {
        $mod = D('item_favs');
        $pk  = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
            if (false !== $mod->delete($ids)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
                $this->success(L('operation_success'));
            }
            else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'));
            }
        }
        else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
            $this->error(L('illegal_parameters'));
        }
    }

    public function _get_uids($uname)
    {
        $where['username'] = array('like', '%' . $uname . '%');
        $uidarr            = D('user')->where($where)->field('id')->select();
        $_idarr            = array();
        foreach ($uidarr as $v) {
            $_idarr[] = $v['id'];
        }
        return implode(',', $_idarr);
    }

    public function _get_iids($iname)
    {
        $where['title'] = array('like', '%' . $iname . '%');
        $uidarr         = D('item')->where($where)->field('id')->select();
        $_idarr         = array();
        foreach ($uidarr as $v) {
            $_idarr[] = $v['id'];
        }
        return implode(',', $_idarr);
    }

    public function search_item()
    {
        $goodsname = $this->_request('goodsname', 'trim');
        $item_list = D('item')->where('title like "%' . $goodsname . '%"')->field('id,title')->select();
        $str       = '';
        if ($item_list) {
            foreach ($item_list as $k => $v) {
                $str .= '<option value="' . $v['id'] . '">' . $v['title'] . '</option>';
            }
        }
        echo $str;
    }

    /**
     * 入库数据整理
     */
    protected function _before_insert($data = '')
    {
        $data['add_time'] = date('Y-m-d H:i:s');

        //标签
        $tags = $this->_post('tags', 'trim');
        if (!isset($tags) || empty($tags)) {
            $tag_list = D('tags')->get_tags_by_title($data['intro']);
        }
        else {
            $tag_list = explode(' ', $tags);
        }
        if ($tag_list) {
            $tag_mod = M('tags');
            foreach ($tag_list as $_tag_name) {
                $tag_id = $tag_mod->where(array('name' => $_tag_name))->getField('id');
                !$tag_id && $tag_id = $tag_mod->add(array('name' => $_tag_name)); //标签入库
            }
        }

        $tags         = $this->_post('tags', 'trim');
        $data['tags'] = implode(' ', $tags);
        if (empty($data['official_price'])) {
            $data['official_price'] = $data['mprice'];
        }
        return $data;
    }

    /**
     * 修改提交数据
     */
    protected function _before_update($data = '')
    {
        //修改商品的分类
        $id      = $this->_post('id');
        //echo'<pre>';print_r($result);exit;
        //标签
        $tags = $this->_post('tags', 'trim');
        if (!isset($tags) || empty($tags)) {
            $tag_list = D('tags')->get_tags_by_title($data['intro']);
        }
        else {
            $tag_list = explode(' ', $tags);
        }
        if ($tag_list) {
            $tag_mod = M('tags');
            foreach ($tag_list as $_tag_name) {
                $tag_id = $tag_mod->where(array('name' => $_tag_name))->getField('id');
                !$tag_id && $tag_id = $tag_mod->add(array('name' => $_tag_name)); //标签入库
            }
        }
        $admin_uid = $this->_request('admin_uid', 'intval');
        if ($admin_uid) {
            $admin               = D('admin')->where('id=' . intval($admin_uid))->field('id,username')->find();
            $data['admin_uid']   = intval($admin['id']);
            $data['admin_uname'] = strval($admin['username']);
        }
        $tags = $this->_post('tags', 'trim');
        if (is_array($tags) && count($tags) > 0) {
            $data['tags'] = implode(' ', $tags);
        }
        if (empty($data['official_price'])) {
            $data['official_price'] = $data['mprice'];
        }
        return $data;
    }

    public function _after_update($id)
    {
        //添加sku
        if ($_POST['sku_name'] && $_POST['sku_val'] && $_POST['sku_price'] && $_POST['sku_stock']) {
            $count = count($_POST['sku_name']);

            $sku   = [];
            $stock = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($_POST['sku_name'][$i] && $_POST['sku_val'][$i]) {
                    $sku[] = array(
                        'item_id' => $id,
                        'name'    => $_POST['sku_name'][$i],
                        'val'     => $_POST['sku_val'][$i],
                        'price'   => $_POST['sku_price'][$i],
                        'stock'   => $_POST['sku_stock'][$i]
                    );
                    $stock += $_POST['sku_stock'][$i];
                }
            }
            if ($sku) {
                D('item_sku')->where(['item_id' => $id])->delete();
                D('item_sku')->addAll($sku);
                D('item')->where(['id' => $id])->save(['stock' => $stock]);
            }
        }

        //添加attr
        if ($_POST['attr_name'] && $_POST['attr_val']) {
            $count = count($_POST['attr_name']);

            $sku   = [];
            $stock = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($_POST['attr_name'][$i] && $_POST['attr_val'][$i]) {
                    $sku[] = array(
                        'item_id' => $id,
                        'name'    => $_POST['attr_name'][$i],
                        'val'     => $_POST['attr_val'][$i],
                    );
                }
            }
            if ($sku) {
                D('item_attr')->where(['item_id' => $id])->delete();
                D('item_attr')->addAll($sku);
            }
        }

        $file_imgs = array();
        foreach ($_FILES['imgs']['name'] as $key => $val) {
            if ($val) {
                $file_imgs['name'][]     = $val;
                $file_imgs['type'][]     = $_FILES['imgs']['type'][$key];
                $file_imgs['tmp_name'][] = $_FILES['imgs']['tmp_name'][$key];
                $file_imgs['error'][]    = $_FILES['imgs']['error'][$key];
                $file_imgs['size'][]     = $_FILES['imgs']['size'][$key];
            }
        }
        if ($file_imgs) {
            $result = $this->_upload($file_imgs, 'assets', array(
                'width'  => C('ins_item_bimg.width') . ',' . C('ins_item_simg.width'),
                'height' => C('ins_item_bimg.height') . ',' . C('ins_item_simg.height'),
                'suffix' => '_b,_s',
            ));
            if ($result['error']) {
                $this->error($result['info']);
            }
            else {
                foreach ($result['data'] as $key => $val) {
                    $item_imgs[] = array(
                        'img'      => $val['savePath'],
                        'item_id'  => $id,
                        'add_time' => date('Y-m-d H:i:s'),
                        'status'   => 1
                    );
                    //添加水印
                    if (@file_exists(C('ins_attach_path') . 'water.png')) {
                        $img_name = explode('.', $val['savename']);
                        Image::water($val['savename'], C('ins_attach_path') . 'water.png');
                        Image::water($val['savepath'] . $img_name[0] . '_b.' . $img_name[1], C('ins_attach_path') . 'water.png');
                        Image::water($val['savepath'] . $img_name[0] . '_s.' . $img_name[1], C('ins_attach_path') . 'water.png');
                    }
                }
            }
        }
        $item_imgs && D('item_img')->addAll($item_imgs);
    }

    public function _after_insert($id)
    {
        //添加sku
        if ($_POST['sku_name'] && $_POST['sku_val'] && $_POST['sku_price'] && $_POST['sku_stock']) {
            $count = count($_POST['sku_name']);

            $sku   = [];
            $stock = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($_POST['sku_name'][$i] && $_POST['sku_val'][$i]) {
                    $sku[] = array(
                        'item_id' => $id,
                        'name'    => $_POST['sku_name'][$i],
                        'val'     => $_POST['sku_val'][$i],
                        'price'   => $_POST['sku_price'][$i],
                        'stock'   => $_POST['sku_stock'][$i]
                    );
                    $stock += $_POST['sku_stock'][$i];
                }
            }
            if ($sku) {
                D('item_sku')->where(['item_id' => $id])->delete();
                D('item_sku')->addAll($sku);
                D('item')->where(['id' => $id])->save(['stock' => $stock]);
            }
        }

        $item_info = D('item')->field('id,img')->where(array('id' => $id))->find();
        
        //上传相册
        $date_dir  = date('/Y/m/d/'); //上传目录
        $item_imgs = array(); //相册
        if ($item_info['img']) {
            $item_imgs[] = array(
                'img'      => $item_info['img'],
                'item_id'  => $id,
                'add_time' => date('Y-m-d H:i:s'),
                'status'   => 1
            );
        }
        $file_imgs = array();
        foreach ($_FILES['imgs']['name'] as $key => $val) {
            if ($val) {
                $file_imgs['name'][]     = $val;
                $file_imgs['type'][]     = $_FILES['imgs']['type'][$key];
                $file_imgs['tmp_name'][] = $_FILES['imgs']['tmp_name'][$key];
                $file_imgs['error'][]    = $_FILES['imgs']['error'][$key];
                $file_imgs['size'][]     = $_FILES['imgs']['size'][$key];
            }
        }
        if ($file_imgs) {
            $result = $this->_upload($file_imgs, 'assets' . $date_dir, array(
                'width'  => C('ins_item_bimg.width') . ',' . C('ins_item_simg.width'),
                'height' => C('ins_item_bimg.height') . ',' . C('ins_item_simg.height'),
                'suffix' => '_b,_s',
            ));
            if ($result['error']) {
                $this->error($result['info']);
            }
            else {
                foreach ($result['info'] as $key => $val) {
                    $item_imgs[] = array(
                        'img'      => $date_dir . $val['savename'],
                        'item_id'  => $id,
                        'add_time' => date('Y-m-d H:i:s'),
                        'status'   => 1
                    );
                    //添加水印
                    if (@file_exists(C('ins_attach_path') . 'water.png')) {
                        $img_name = explode('.', $val['savename']);
                        Image::water(C('ins_attach_path') . 'item/' . $date_dir . $val['savename'], C('ins_attach_path') . 'water.png');
                        Image::water(C('ins_attach_path') . 'item/' . $date_dir . $img_name[0] . '_b.' . $img_name[1], C('ins_attach_path') . 'water.png');
                        Image::water(C('ins_attach_path') . 'item/' . $date_dir . $img_name[0] . '_s.' . $img_name[1], C('ins_attach_path') . 'water.png');
                    }
                }
            }
        }
        $item_imgs && D('item_img')->addAll($item_imgs);

    }

    public function verify()
    {

        $mod = D('item');
        $pk  = $mod->getPk();
        if (IS_POST) {
            if (false === $data = $mod->create()) {
                IS_AJAX && $this->ajaxReturn(0, $mod->getError());
                $this->error($mod->getError());
            }
            $data['admin_id']   = $_SESSION['admin']['id'];
            $data['admin_name'] = $_SESSION['admin']['username'];
            $data['admin_time'] = date('Y-m-d H:i:s');

            if (false !== $mod->save($data)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'edit');
                $this->success(L('operation_success'));
            }
            else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'));
            }
        }
        else {
            $id   = $this->_get($pk, 'intval');
            $info = $mod->find($id);
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

    public function search_admin()
    {
        $username   = $this->_request('adminname', 'trim');
        $admin_list = D('admin')->where('username like "%' . $username . '%"')->field('id,username')->select();
        $str        = '';
        if ($admin_list) {
            foreach ($admin_list as $k => $v) {
                $str .= '<option value="' . $v['id'] . '">' . $v['username'] . '</option>';
            }
        }
        echo $str;
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

    /**
     * ajax获取标签
     */
    public function ajax_gettags()
    {
        $title    = $this->_get('title', 'trim');
        $tag_list = D('tags')->get_tags_by_title($title);

        $tags = implode(' ', $tag_list);

        //排重
        $array = explode(' ', $tags);
        $count = count($array);

        $arr = array_unique($array);
        for ($i = 0; $i < $count; $i++) {
            $sub_count = substr_count($tags, $arr[$i]);
            if ($sub_count) {
                $c[$i] = $sub_count;
            }
        }

        asort($c, SORT_NUMERIC);
        foreach ($c as $key => $val) {
            $tag[] = $arr[$key];
        }
        $tags = implode(' ', $tag);

        $this->ajaxReturn(1, L('operation_success'), $tags);
    }

    public function ajax_gettopic()
    {
        $topiclist = D('topic')->field('id,title')->select();
        $this->ajaxReturn(1, L('operation_success'), $topiclist);
    }

    public function topic()
    {
        if (IS_POST) {
            $ids      = $this->_post('ids', 'trim');
            $topic_id = $this->_post('topic_id', 'intval');
            $idsarr   = explode(',', $ids);
            if (!is_array($idsarr)) {
                $this->ajaxReturn(0, L('operation_failure'));
            }
            foreach ($idsarr as $v) {
                if (intval($v) < 1) {
                    continue;
                }
                D("topic_item")->where(array('item_id' => $v, 'topic_id' => $topic_id))->delete();
                $return = D('topic_item')->add([
                    'topic_id' => $topic_id,
                    'item_id'  => $v,
                    'add_time' => current_date()
                ]);
            }
            $this->ajaxReturn(1, L('operation_success'), $return, 'topic');
        }
        else {
            $ids = $this->_get('ids', 'trim');
            $this->assign('ids', $ids);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

    public function search_topic()
    {
        $cate_id        = $this->_post('cate_id', 'intval');
        $id_arr         = D('topic_cate')->get_child_ids($cate_id, true);
        $map['cate_id'] = array('IN', $id_arr);
        $topic_list     = D('topic')->field('id,title')->where($map)->select();
        $this->ajaxReturn(1, '', $topic_list);
    }

    public function search_tags()
    {
        $ids = $this->_post('ids', 'trim');
        if ($ids == '') {
            $this->ajaxReturn(0, '', '');
        }
        $list = D('tags')->where(array('cate_id' => array('in', $ids), 'status' => 1))->order('ordid asc')->select();
        $str  = '';
        foreach ($list as $key => $val) {
            $str .= '&nbsp;<lable><input value="' . $val['name'] . '" name="tags[]" type="checkbox"> ' . $val['name'] . '</label>';
        }
        $this->ajaxReturn(1, '', $str);
    }

    public function erweima()
    {
        $text       = $this->_request('text', 'trim'); //echo $text;die;
        $ex         = explode('=', $text);
        $id         = $ex[1];
        $item_title = D('item')->where(array('id' => $id))->getField('title');
        $this->assign(compact('text', 'item_title'));
        $this->display();
    }

    public function setting()
    {
        $id = $this->_request('id', 'intval');
        if (IS_POST) {
            $data = D('item')->create();
            D('item')->where(array('id' => $id))->save($data);
            $this->ajaxReturn(1, L('operation_success'), $data, 'setting');
        }
        else {
            if ($id) {
                $info = D('item')->where(array('id' => $id))->find();
                $this->assign(compact('info', 'id'));
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            }
        }
    }

    public function change_mid()
    {
        if (IS_POST) {
            $ids = $this->_post('ids', 'trim');
            $mid = $this->_post('mid');
            if (D('member')->where(['id' => $mid])->count() == 0) {
                $this->ajaxResultError('mid不存在!');
            }
            $ids = explode(',', $ids);
            if ($ids) {
                D('item')->where(['id' => ['in', $ids]])->save(compact('mid'));
            }
            $this->ajaxResultSuccess('操作成功', ['dialog' => ACTION_NAME]);
        }
        else {
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

    public function change_type()
    {
        if (IS_POST) {
            $ids  = $this->_post('ids', 'trim');
            $type = $this->_post('type');

            $ids = explode(',', $ids);
            if ($ids) {
                D('item')->where(['id' => ['in', $ids]])->save(compact('type'));
            }
            $this->ajaxResultSuccess('操作成功', ['dialog' => ACTION_NAME]);
        }
        else {
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

    public function change_cate()
    {
        if (IS_POST) {
            $ids  = $this->_post('ids', 'trim');
            $cate_id = $this->_post('cate_id');

            $ids = explode(',', $ids);
            if ($ids) {
                D('item')->where(['id' => ['in', $ids]])->save(compact('cate_id'));
            }
            $this->ajaxResultSuccess('操作成功', ['dialog' => ACTION_NAME]);
        }
        else {
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }
}