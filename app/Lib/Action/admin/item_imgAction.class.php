<?php

class item_imgAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('item_img');

    }

    protected function _search()
    {
        $map = array();
        ($item_id = $this->_request('item_id', 'trim')) && $map['item_id'] = array('eq', $item_id);
        ($keyword = $this->_request('keyword', 'trim')) && $map['info'] = array('like', '%' . $keyword . '%');
        $this->assign('search', array(
            'keyword' => $keyword,
            'item_id' => $item_id,
        ));

        return $map;
    }

    public function _before_index()
    {
        $this->list_relation = true;
    }

    public function delete()
    {
        $id  = $this->_get('id', 'trim');
        $ids = explode(',', $id);
        foreach ($ids as $k => $v) {
            $album_img = $this->_mod->where('id=' . $v)->getField('img');
            if ($album_img) {
                $ext           = array_pop(explode('.', $album_img));
                $album_min_img = C('ins_attach_path') . 'item/' . str_replace('.' . $ext, '_s.' . $ext, $album_img);
                is_file($album_min_img) && @unlink($album_min_img);
                $album_img = C('ins_attach_path') . 'item/' . $album_img;
                is_file($album_img) && @unlink($album_img);
                $this->_mod->delete($v);
            }
        }
        $this->ajaxReturn(1, '', '');
    }
}