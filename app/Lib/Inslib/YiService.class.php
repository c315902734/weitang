<?php

class YiService
{
    public static function update($id)
    {
        $where         = compact('id');
        $field         = 'id,total,haved,code_status';
        $info          = D('yi')->where($where)->field($field)->find();
        $info['haved'] = D('yi_code')->where(['yi_id' => $id, 'yi_order_id' => ['gt', 0]])->count();
        if ($info['code_status'] == 0 && $info['total'] == $info['haved'] && $info['haved'] > 0) {
            $info['code_status'] = 1;
        }
        D('yi')->where($where)->save([
            'haved'       => $info['haved'],
            'code_status' => $info['code_status'],
        ]);
    }
}