<?php

/**
 * 订单管理
 * @package api
 */
class order_itemAction extends backendAction
{

    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('order_item');
    }

    protected function _search()
    {
        
    }

    public function _before_index()
    {
    }
}