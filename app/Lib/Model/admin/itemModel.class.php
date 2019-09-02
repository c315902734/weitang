<?php

class itemModel extends baseModel
{
    protected $_validate = array(
        array('title', 'require', '标题名称不能为空'),
    );

    protected $_link = array(
        'item_img' => array(
            'mapping_type' => HAS_MANY,
            'class_name' => 'item_img',
            'foreign_key' => 'item_id',
            'mapping_fields'=> 'id,img,add_time,status',
        ),
    );
}
