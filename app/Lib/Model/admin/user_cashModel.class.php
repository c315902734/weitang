<?php
class user_cashModel extends baseModel
{
    protected $_link = array(
        'user' => array(
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'username,tele,price,bankname,bankid,realname',
            'class_name'     => 'user',
            'foreign_key'    => 'uid',
        ),
    );
}