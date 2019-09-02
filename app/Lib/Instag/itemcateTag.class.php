<?php

/**
 * 商品分类标签解析
 *
 * @author andery
 */
class itemcateTag
{
    public function __construct()
    {
        $this->mod = D('item_cate');
    }

    /**
     * 分类标签解析
     * @param array $options
     */
    public function lists($options)
    {
        $options['field'] = isset($options['field']) ? trim($options['field']) : '*';
        $options['where'] = isset($options['where']) ? trim($options['where']) : '';
        $options['num']   = isset($options['num']) ? intval($options['num']) : 0;
        $pk               = $this->mod->getPk();
        $options['order'] = isset($options['order']) ? trim($options['order']) : $pk . ' DESC';

        $select = $this->mod->field($options['field']); //字段
        $map    = array('status' => '1');
        intval($options['cateid']) && $map['pid'] = $options['cateid'];
        $options['where'] && $map['_string'] = $options['where'];
        $select->where($map); //条件
        $select->order($options['order']); //排序
        intval($options['num']) && $select->limit($options['num']); //个数
        $data = $select->select();
        return $data;
    }

    public function all()
    {
        $status    = 1;
        $pid       = array('neq', 0);
        $is_hots=1;
        $hots_list = array(
            array(
                'name'  => '热门推荐',
                'items' => $this->mod->where(compact('status', 'pid','is_hots'))->select()
            )
        );
        $pid  = 0;
        $list = $this->mod->relation(true)->where(compact('status', 'pid'))->select();
        $data = array_connect($hots_list, $list);
        return $data;
    }
}