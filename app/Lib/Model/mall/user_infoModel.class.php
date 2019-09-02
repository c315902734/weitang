<?php

class user_infoModel extends baseModel
{
    public function save($data = '', $options = array())
    {
        $uid = $this->options['where']['uid'];
        $res = parent::save($data, $options);
        if ($res == 0 && $uid > 0) {
            $data['uid'] = $uid;
            $res         = $this->add($data);
        }
        return $res;
    }
}
