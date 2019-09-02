<?php

class member_expressModel extends baseModel
{
    protected $_link = [
        'express' => [
            'mapping_type' => BELONGS_TO,
            'class_name'   => 'express',
            'foreign_key'  => 'express_id',
        ]
    ];

    protected function _parse_item($result, $_options = [])
    {
        return parent::_parse_item($result, $_options);
    }
}