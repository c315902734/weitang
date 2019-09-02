<?php

class user_bindModel extends baseModel
{
	protected $_link = array(
        'user' => array(
            'mapping_type' 	 => BELONGS_TO,
			'mapping_fields' => 'username,sex',
			'class_name' 	 => 'user',
            'foreign_key' 	 => 'uid',
        ),

    );
}

?>