<?php

/**
 * 用户信息管理
 */
class user_levelAction extends backendAction
{

    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('user_level');
		$big_menu = array(
            'title' => '添加会员等级',
            'iframe' => U('user_level/add'),
            'id' => 'add',
            'width' => '400',
            'height' => '100',
        );
        $this->assign('big_menu', $big_menu);
    }

    protected function _search()
    {
        
    }

    public function _before_index()
    {
		
    }

    public function _before_insert($data)
    {
        
    }

    public function _after_insert($id)
    {
    }

    public function _before_update($data)
    {
        
    }

    public function _after_update($id)
    {
        
    }

}