<?php
class price_logModel extends baseModel
{
    protected $_link = array(
        'user' => array(
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'username,tele,price',
            'class_name'     => 'user',
            'foreign_key'    => 'uid',
        ),
    );
}