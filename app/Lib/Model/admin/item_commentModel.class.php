<?php

class item_commentModel extends baseModel
{
    protected $_auto = array(
        array('uid', 'getAdminId', '1', 'callback'),
        array('uname', 'getAdminName', '1', 'callback'),
    );

	protected $_link = array(
        'user' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'user',
            'foreign_key' => 'uid',
        ),
    );

    protected function getAdminName()
    {
        return $_SESSION['admin']['username'];
    }

    protected function getAdminId()
    {
        return $_SESSION['admin']['id'];
    }

}

?>