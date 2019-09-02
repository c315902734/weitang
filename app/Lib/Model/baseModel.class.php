<?php

class baseModel extends RelationModel
{
    var $attach_fields = array('img', 'extimg', 'health', 'card_bg', 'card_fr', 'cover', 'banner', 'animation_img');
    var $editor_fields = array('info', 'abst');
    var $default_page_size = 15;

    protected function _parse_item($result, $_options = array())
    {
        return $result;
    }

    protected function parse($info)
    {
        $this->attach_fields = array_merge($this->attach_fields, C('ATTACH_FIELDS'));

        foreach ($this->attach_fields as $val) {
            if (array_key_exists($val, $info)) {
                if (in_array(GROUP_NAME, C('API_MODULES'))) {
                    if (empty($info[$val])) {
                        continue;
                    }
                    if (in_array($this->name, array('member', 'user')) && $val == 'img') {
                        $info[$val] = avatar($info[$val]);
                    }
                    else {
                        $info[$val] = attach($info[$val], 'assets', true);
                    }
                }
                else {
                    $info['_' . $val] = attach($info[$val], 'assets', true);
                }
            }
        }
        foreach ($this->editor_fields as $val) {
            if (array_key_exists($val, $info)) {
                $info[$val] = parse_editor_info($info[$val]);
            }
        }
        return $info;
    }

    function getCurrentTime()
    {
        return date('Y-m-d H:i:s', time());
    }

    function parse_where($params, $fields)
    {
        $res   = explode(',', $fields);
        $where = array();
        foreach ($res as $key => $val) {
            if (isset($params[$val])) {
                $where[$val] = $params[$val];
            }
        }
        return $where;
    }

    protected function fieldExists($params)
    {
        $result = $this->where($params['where'])->count() > 0;
        return $result;
    }

    public function find($options = array())
    {
        $_options = $this->options;
        $result   = (array)parent::find($options);
        if (method_exists($this, '_parse_item')) {
            $result = $this->_parse_item($result, $_options);
        }
        $result = $this->parse($result);
        return $result;
    }

    public function select($options = array())
    {
        $fields = $this->getDbFields();
        if (empty($this->options['order'])) {
            $order = array();
            if (in_array('ordid', $fields)) {
                $order[] = "ordid asc";
            }
            if (in_array('id', $fields)) {
                $order[] = "id desc";
            }
            $this->options['order'] = implode(',', $order);
        }
        $_options = $this->options;
        $result   = (array)parent::select($options);
        foreach ($result as $key => $val) {
            if (method_exists($this, '_parse_item')) {
                $result[$key] = $this->_parse_item($val, $_options);
            }
            $result[$key] = $this->parse($result[$key]);
        }

        return $result;
    }

    public function save($data = '', $options = array())
    {
        $data = $this->autoData($data, C('UPDATE_TIME_FIELDS'));
        return parent::save($data, $options);
    }

    public function add($data = '', $options = array(), $replace = false)
    {
        $fields = $this->getDbFields();
        $data   = $this->autoData($data, C('ADD_TIME_FIELDS'));
        if (in_array('id', $fields) && $data['id']) {
            unset($data['id']);
        }
        return parent::add($data, $options, $replace);
    }

    protected function autoData($data, $time_list = array())
    {
        $fields = $this->getDbFields();
        //过滤字段
        if (is_array($data)) {
            $data = Arr::pick($data, $fields);
        }
        //时间自动完成
        foreach ($time_list as $key => $val) {
            if (!empty($data[$val])) {
                continue;
            }
            if (!in_array($val, $fields)) {
                continue;
            }
            if ($this->fields['_type'][$val] == 'datetime') {
                $data[$val] = date('Y-m-d H:i:s');
            }
            else {
                $data[$val] = time();
            }
        }
        //用户名自动完成
        $list     = array(
            array('uid', 'uname'),
            array('item_uid', 'item_uname'),
            array('from_uid', 'from_uname'),
            array('to_uid', 'to_uname'),
            array('mid', 'mname'),
        );
        $user_mod = D('user');
        foreach ($list as $val) {
            if (in_array($val[1], $fields) && !isset($data[$val[1]]) && isset($data[$val[0]])) {
                $data[$val[1]] = $user_mod->where(array('id' => $data[$val[0]]))->getField('username');
            }
        }
        return $data;
    }

    public function setDec($field, $step = 1)
    {
        if ($step == 0) {
            return false;
        }
        $result = false;
        try {
            $result = $this->setField($field, array('exp', $field . '-' . $step));
        } catch (Exception $e) {
            $result = $this->setField($field, 0);
        }
        return $result;
    }

    public function setInc($field, $step = 1)
    {
        if ($step == 0) {
            return false;
        }
        try {
            $result = $this->setField($field, array('exp', $field . '+' . $step));
        } catch (Exception $e) {
            $result = $this->setField($field, 0);
        }
        return $result;
    }

    public function query($sql, $parse = false)
    {
        $res = parent::query($sql, $parse);
        if (method_exists($this, '_parse_item')) {
            foreach ($res as $key => $val) {
                $res[$key] = $this->_parse_item($val);
            }
        }
        return $res;
    }

    public function field($fields, $except = false)
    {
        $query_fields = [];
        $fields       = strtolower($fields);

        if ($fields != '*') {
            $fields       = explode(',', $fields);
            $table_fields = $this->getDbFields();
            foreach ($fields as $key => $val) {
                $name = $val;
                if (strpos($val, ' as ')) {
                    $query_fields[] = $val;
                }
                else if (in_array(trim($name), $table_fields)) {
                    $query_fields[] = $val;
                }
            }
        }

        return parent::field(implode(',', $query_fields), $except);
    }

    public function sum($field)
    {
        return floatval(parent::sum($field));
    }
}