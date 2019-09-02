<?php

class orderModel extends baseModel
{
    protected $_validate = array(
        array('uid', 'require', '用户不能为空'), //不能为空
        array('orderid', 'require', '订单编号不能为空'), //不能为空
        array('total', 'require', '总金额不能为空'), //不能为空
        array('pays', 'require', '支付方式不能为空'), //不能为空
        array('orderid', '', '订单编号已存在', 0, 'unique', 1), //新增的时候检测重复
    );

    protected $_link = array(
        'items' => array(
            'mapping_type' => HAS_MANY,
            'class_name' => 'order_item',
            'foreign_key' => 'order_id',
        ),
    );
}