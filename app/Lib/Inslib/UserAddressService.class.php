<?php
class UserAddressService
{
    public static function set_order_address_id($id)
    {
        session('direct_buy_address_id', $id);
    }

    public static function get_order_address_id()
    {
        return session('direct_buy_address_id');
    }
}