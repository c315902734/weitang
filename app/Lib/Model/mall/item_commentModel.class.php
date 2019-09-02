<?php

class item_commentModel extends baseModel
{
    protected $_link = [
        'item'     => [
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'title,img',
            'class_name'     => 'item',
            'foreign_key'    => 'item_id',
        ],
        'img_list' => [
            'mapping_type'   => HAS_MANY,
            'mapping_fields' => 'img',
            'class_name'     => 'item_comment_img',
            'foreign_key'    => 'comment_id',
        ],
    ];

    protected function _parse_item($result, $_options = [])
    {
        return parent::_parse_item($result, $_options);
    }
}