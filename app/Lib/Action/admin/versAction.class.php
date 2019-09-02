<?php
class versAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('vers');
    }

    protected function _search() {
        $map = array();
        ($cate_id = $this->_request('cate_id', 'trim')) && $map['cate_id'] = array('eq', $cate_id);
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
        $this->assign('search', array(
            'keyword' => $keyword,
            'cate_id' => $cate_id,
        ));
        return $map;
    }

    public function _before_index() {
        $big_menu = array(
            'title' => '添加版本',
            'iframe' => U('vers/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '260'
        );
        $this->assign('big_menu', $big_menu);
    }

    public function _before_add() {
        
    }

    public function _before_edit()
    {
       
    }
}