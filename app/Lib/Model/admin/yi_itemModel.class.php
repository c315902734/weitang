<?php

class yi_itemModel extends baseModel
{
    protected $_validate = array(
        array('title', 'require', '标题名称不能为空'),
    );

    protected $_link = array(
        'item_img' => array(
            'mapping_type' => HAS_MANY,
            'class_name' => 'yi_item_img',
            'foreign_key' => 'item_id',
        ),
        'user' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'user',
            'foreign_key' => 'uid',
            'mapping_fields'=> 'id,username,tele,province,city,area',
        ),
    );
}
