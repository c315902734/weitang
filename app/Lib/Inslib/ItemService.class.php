<?php

class ItemService
{
    public static function update_stock($item_id, $sku_id = 0, $num = 0)
    {
        if ($num > 0 && $sku_id > 0) {
            D('item_sku')->where(['id' => $sku_id])->setDec('stock', $num);
        }
        if (D('item_sku')->where(['item_id' => $item_id])->count() > 0) {
            D('item_sku')->where(['item_id' => $item_id, 'stock' => ['lt', 0]])->save(['stock' => 0]);
            D('item')->where(['id' => $item_id])->save([
                'stock' => D('item_sku')->where(['item_id' => $item_id])->sum('stock'),
            ]);
        }
        else if ($num > 0) {
            $stock = D('item')->where(['id' => $item_id])->getField('stock');
            if ($stock - $num < 0 || $stock < 0) {
                $stock = 0;
            }
            else {
                $stock -= $num;
            }
            D('item')->where(['id' => $item_id])->save(compact('stock'));
        }
        ItemService::update_sales($item_id);
    }

    public static function update_sales($item_id)
    {
        D('item')->where(['id' => $item_id])->save([
            'sales' => D('order_item_view')->where([
                'item_id'      => $item_id,
                'order_status' => ['in', [1, 2, 5, 6]]
            ])->sum('nums'),
        ]);
    }
}