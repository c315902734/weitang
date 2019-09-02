<?php
class ssc_logModel extends baseModel
{
    protected $_link = array(
        'user' => array(
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'username,tele',
            'class_name'     => 'user',
            'foreign_key'    => 'uid',
        ),
		'order' => array(
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'orderid,id',
            'class_name'     => 'order',
            'foreign_key'    => 'order_id',
        ),
    );
}