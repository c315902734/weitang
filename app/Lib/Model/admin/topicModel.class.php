<?php

class topicModel extends baseModel
{
    protected $_link = array(
        'topic_item'  => array(
            'mapping_type' => HAS_MANY,
            'class_name'   => 'topic_item',
            'foreign_key'  => 'topic_id',
        ),
    );

    protected $_validate = array(
        array('title', 'require', '标题名称不能为空'),
    );

}
