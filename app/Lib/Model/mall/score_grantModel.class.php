<?php

class score_grantModel extends baseModel
{
    protected $_link = [
        'from_user' => [
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'id,username,tele',
            'class_name'     => 'user',
            'foreign_key'    => 'from_uid',
        ]
    ];

    protected function _after_insert($data, $options)
    {
        if ($data['uid']) {
            UserService::update_score($data['uid']);

        }
        if ($data['from_uid']) {
            UserService::update_score($data['from_uid']);
        }
    }

    protected function _after_update($data, $options)
    {
        if ($data['uid']) {
            UserService::update_score($data['uid']);

        }
        if ($data['from_uid']) {
            UserService::update_score($data['from_uid']);
        }
    }

    protected function _parse_item($result, $_options = [])
    {
        return parent::_parse_item($result, $_options);
    }
}