<?php

class user_favsModel extends baseModel
{
    protected $_link = array(
        'item' => array(
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'title,img,mprice,price',
            'class_name'     => 'item',
            'foreign_key'    => 'item_id',
        ),
    );
}
