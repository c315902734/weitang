<?php

class base_item_skuModel extends baseModel
{
    protected function _parse_item($result, $_options = [])
    {
        if (isset($result['price']) && isset($result['item_id'])) {
            if ($result['price'] <= 0) {
                $result['price'] = D('item')->where(['id' => $result['item_id']])->getField('price');
                $this->where(['id' => $result['id']])->save(['price' => $result['price']]);
            }
        }
        return parent::_parse_item($result, $_options);
    }
}