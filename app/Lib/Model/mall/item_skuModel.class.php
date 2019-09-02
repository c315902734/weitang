<?php

class item_skuModel extends baseModel
{

    protected function _parse_item($result, $_options = [])
    {
        if ($result['item_id'] > 0 && isset($result['price'])) {
            $result['price'] = D('item')->where(['id' => $result['item_id']])->getField('price');
        }
        return parent::_parse_item($result, $_options);
    }
}