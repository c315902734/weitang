<?php

class score_logModel extends baseModel
{
	protected $_link = array(
        'user' => array(
            'mapping_type' 	 => BELONGS_TO,
			'mapping_fields' => 'id,username',
			'class_name' 	 => 'user',
            'foreign_key' 	 => 'uid',
        ),

    );
}

?>