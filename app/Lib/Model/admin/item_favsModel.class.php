<?php

class item_favsModel extends baseModel
{
    protected $_link = array(
        'user' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'user',
            'foreign_key' => 'uid',
            'mapping_fields'=> 'username,level,img',
        ),
    );
}
