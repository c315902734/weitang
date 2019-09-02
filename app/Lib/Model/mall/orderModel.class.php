<?php

class orderModel extends baseModel
{
    protected $_link = [
        'order_item_list' => [
            'mapping_type'   => HAS_MANY,
            'mapping_fields' => 'item_id,title,price,nums,skus',
            'class_name'     => 'order_item',
            'foreign_key'    => 'order_id',
        ],
        'member' => [
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'id,title,username',
            'class_name'     => 'member',
            'foreign_key'    => 'mid',
        ],

    ];

    protected function _parse_item($result, $_options = [])
    {
        return parent::_parse_item($result, $_options);
    }
}