<?php

class item_commentModel extends baseModel
{
    protected $_auto = array(
        array('uid', 'getAdminId', '1', 'callback'),
        array('uname', 'getAdminName', '1', 'callback'),
    );

	protected $_link = array(
        'item'   => array(
            'mapping_type'   => BELONGS_TO,
            'class_name'     => 'item',
            'foreign_key'    => 'item_id',
            'mapping_fields' => 'id,img,title,price,stock,score',
        ),
		'order'   => array(
            'mapping_type'   => BELONGS_TO,
            'class_name'     => 'order',
            'foreign_key'    => 'order_id',
            'mapping_fields' => 'id,orderid',
        )
    );

    protected function getAdminName()
    {
        return $_SESSION['admin']['username'];
    }

    protected function getAdminId()
    {
        return $_SESSION['admin']['id'];
    }
	function parse($info)
    {
		return $info;
	}

}

?>