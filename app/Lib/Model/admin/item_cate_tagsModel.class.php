<?php

class item_cate_tagsModel extends baseModel
{

    protected $_link = array(
        'tags' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'tags',
            'foreign_key' => 'tags_id',
            'mapping_fields'=> 'name',
        ),
    );
}
