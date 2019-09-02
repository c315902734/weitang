<?php

class userModel extends baseModel
{
    protected $_link = [
        'level' => [
            'mapping_type'   => BELONGS_TO,
            'mapping_fields' => 'title,img',
            'class_name'     => 'user_level',
            'foreign_key'    => 'level_id',
        ]
    ];

    public function get($uid, $fields = '*', $except = false)
    {
        if (intval($uid) <= 0) {
            return false;
        }

        $where = [
            'id' => $uid,
        ];

        $user  = D('user')->field($fields, $except)
            ->where($where)
            ->relation(true)
            ->find();
        
        return $user;
    }

    public function get_realname($uid)
    {
        $user = $this->get($uid, 'username,realname');
        return empty($user['realname']) ? $user['username'] : $user['realname'];
    }

    protected function _parse_item($result, $_options = array())
    {
        return parent::_parse_item($result, $_options);
    }

}
