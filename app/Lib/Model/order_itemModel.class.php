<?php

class order_itemModel extends baseModel
{
    protected $_link = array(
        'item'=>array(
            'mapping_type'   =>BELONGS_TO,
            'class_name'     => 'item',
            'foreign_key'    => 'item_id',
        ),
    );

    protected function _parse_item($result,$_options)
    {
        return $result;
    }
}