<?php

class itemModel extends baseModel
{
    protected $_link = [
        'member' => [
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'id,username,tele,title',
            'class_name'     => 'member',
            'foreign_key'    => 'mid',
        ]
    ];

    protected function _parse_item($info)
    {
        if ($info['info']) {
            $info['info'] = parse_editor_info($info['info'], true);
        }
        return $info;
    }
}
