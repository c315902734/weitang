<?php

class attrModel extends baseModel
{
    protected $_link = array(
        'attr_val'   => array(
            'mapping_type'   => HAS_MANY,
            'class_name'     => 'attr_val',
            'foreign_key'    => 'attr_id',
        ),
    );
    /**
     * 生成spid
     *
     * @param int $pid 父级ID
     */
    public function get_spid($pid)
    {
        if (!$pid) {
            return 0;
        }
        $pspid = D('item_cate')->where(array('id' => $pid))->getField('spid');
        if ($pspid) {
            $spid = $pspid . $pid . '|';
        } else {
            $spid = $pid . '|';
        }
        return $spid;
    }

    /**
     * 根据商品分类id获取分类全名称
     */
    public function get_all_name($cate_id) {
        if(!$cate_id) {
            return '';
        }
        $cate_name = $this->get_name($cate_id);
        $pid = D('item_cate')->where(array('id' => $cate_id))->getField('pid');
        $all_cate_name = $this->get_name($pid) .'-'.$cate_name;
        return $all_cate_name;
    }


    /**
     * 获取分类下面的所有子分类的ID集合
     *
     * @param int $id
     * @param bool $with_self
     * @return array $array
     */
    public function get_child_ids($id, $with_self = false)
    {
        $spid = D('item_cate')->where(array('id' => $id))->getField('spid');
        $spid = $spid ? $spid .= $id . '|' : $id . '|';
        $id_arr = D('item_cate')->field('id')->where(array('spid' => array('like', $spid . '%')))->select();
        $array = array();
        foreach ($id_arr as $val) {
            $array[] = $val['id'];
        }
        $with_self && $array[] = $id;
        return $array;
    }

    /**
     * 根据ID获取分类名称
     */
    public function get_name($id)
    {
        //分类数据
        if (false === $cate_data = F('cate_data')) {
            $cate_data = $this->cate_data_cache();
        }
        return $cate_data[$id]['name'];
    }
    /**
     * 根据ID获取属性值
     */
    public function get_attr_val($id){
        $attr_list = D('attr_val')->where(array('attr_id'=>$id))->select();
        $attr_val = '';
        foreach($attr_list as $key=>$val){
            $attr_val .= $val['name']."<a  href='javascript:;' class='J_confirmurl' data-acttype='ajax' data-msg='". sprintf(L('confirm_delete_one'),$val['name'])."' data-uri='". U('attr/del_val', array('id'=>$val['id']))."'>[删除]</a><br />";
        }
        return $attr_val;
    }

    /**
     * 获取标签分类紧接上级实体分类
     */
    public function get_pentity_id($id)
    {
        $pentity_id = 0;
        if (false === $cate_data = F('cate_data')) {
            $cate_data = $this->cate_data_cache();
        }
        $spid = array_reverse(explode('|', trim($cate_data[$id]['spid'], '|')));
        foreach ($spid as $val) {
            if ($cate_data[$val]['type'] == 0) {
                $pentity_id = $val;
                break;
            }
        }
        return $pentity_id;
    }

    /**
     * 读取写入缓存(有层级的分类数据)
     */
    public function cate_cache()
    {
        $cate_list = array();
        $cate_data = $this->field('id,pid,name')->where('status=1')->order('ordid')->select();
        foreach ($cate_data as $val) {
            if ($val['pid'] == '0') {
                $cate_list['p'][$val['id']] = $val;
            } else {
                $cate_list['s'][$val['pid']][$val['id']] = $val;
            }
        }
        F('cate_list', $cate_list);
        return $cate_list;
    }

    /**
     * 读取写入缓存(无层级分类列表)
     */
    public function cate_data_cache()
    {
        $cate_data = array();
        $result = D('item_cate')->field('id,pid,spid,name')->where('status=1')->order('ordid')->select();
        foreach ($result as $val) {
            $cate_data[$val['id']] = $val;
        }
        F('cate_data', $cate_data);
        return $cate_data;
    }

    /**
     * 分类关系读取写入缓存
     */
    public function relate_cache()
    {
        $cate_relate = array();
        $cate_data = D('item_cate')->field('id,pid,spid')->where('status=1')->order('ordid')->select();
        foreach ($cate_data as $val) {
            $cate_relate[$val['id']]['sids'] = $this->get_child_ids($val['id']); //子孙
            if ($val['pid'] == '0') {
                $cate_relate[$val['id']]['tid'] = $val['id']; //祖先
            } else {
                $cate_relate[$val['id']]['tid'] = array_shift(explode('|', $val['spid'])); //祖先
            }
        }
        F('cate_relate', $cate_relate);
        return $cate_relate;
    }

    /**
     * 检测分类是否存在
     *
     * @param string $name
     * @param int $pid
     * @param int $id
     * @return bool
     */
    public function name_exists($name, $id = 0)
    {
        $where = array();
        if($name)$where['name'] = $name;
        if($id)$where['id'] = array('neq', $id);
        $result = $this->where($where)->count('id');
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更新则删除缓存
     */
    protected function _before_write(&$data)
    {
        F('cate_data', NULL);
        F('cate_list', NULL);
        F('cate_relate', NULL);
        F('index_cate_list', NUll);
    }

    /**
     * 删除也删除缓存
     */
    protected function _after_delete($data, $options)
    {
        F('cate_data', NULL);
        F('cate_list', NULL);
        F('cate_relate', NULL);
        F('index_cate_list', NUll);
    }
}

?>