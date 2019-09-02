<?php
class user_rechargeModel extends baseModel
{
    protected $_link = array(
        'user' => array(
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'username,tele,price,bankname,bankid,realname',
            'class_name'     => 'user',
            'foreign_key'    => 'uid',
        ),
		'order' => array(
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'status,id,pay_sn,pays',
            'class_name'     => 'order',
            'foreign_key'    => 'order_id',
        ),
    );
}