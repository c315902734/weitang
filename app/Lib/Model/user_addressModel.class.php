<?php

class user_addressModel extends baseModel
{
	protected $_link = array(
        'user' => array(
            'mapping_type' 	 => BELONGS_TO,
			'mapping_fields' => 'sex',
			'class_name' 	 => 'user',
            'foreign_key' 	 => 'uid',
        ),

    );
}