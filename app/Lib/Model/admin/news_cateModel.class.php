<?php
class news_cateModel extends RelationModel
{
	/**
	 * 获取所有名称返回数组
	 */
	public function get_name_to_array($cate_id) {
        if(!$cate_id) {
            return '';
        }
        $spid = D('news_cate')->where(array('id' => $cate_id))->getField('spid');
		$spid = $spid.$cate_id;
		$spid_array = explode('|',$spid);
		$data = array();
		foreach($spid_array as $val){
			$data[$val] = $this->get_name(intval($val));
		}
        return $data;
    }

	/**
     * 根据ID获取分类名称
     */
	public function get_name($cate_id) {
        //分类数据
        return $this->where(array('id'=>$cate_id))->getField('name');
    }

    /**
     * 生成spid 
     * 
     * @param int $pid 父级ID
     */
    public function get_spid($pid) {
        if (!$pid) {
            return 0; 
        }
        $pspid = $this->where(array('id'=>$pid))->getField('spid');
        if ($pspid) {
            $spid = $pspid . $pid . '|';
        } else {
            $spid = $pid . '|';
        }
        return $spid;
    }
    
    /**
     * 获取分类下面的所有子分类的ID集合
     * 
     * @param int $id
     * @param bool $with_self
     * @return array $array 
     */
    public function get_child_ids($id, $with_self=false) {
        $spid = $this->where(array('id'=>$id))->getField('spid');
        $spid = $spid ? $spid .= $id .'|' : $id .'|';
        $id_arr = $this->field('id')->where(array('spid'=>array('like', $spid.'%')))->select();
        $array = array();
        foreach ($id_arr as $val) {
            $array[] = $val['id'];
        }
        $with_self && $array[] = $id;
        return $array;
    }
    
    /**
     * 检测分类是否存在
     * 
     * @param string $name
     * @param int $pid
     * @param int $id
     * @return bool 
     */
    public function name_exists($name, $pid, $id=0) {
        $where = "name='" . $name . "' AND pid='" . $pid . "' AND id<>'" . $id . "'";
        $result = $this->where($where)->count('id');
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 写入缓存
     */
    public function cate_cache() {
        $news_list = array();
        $cate_data = $this->field('id,pid,name')->where('status=1')->order('ordid')->select();
        foreach ($cate_data as $val) {
            if ($val['pid'] == '0') {
                $news_list['p'][$val['id']] = $val;
            } else {
                $news_list['s'][$val['pid']][] = $val;
            }
        }
        F('news_list', $news_list);
        return $news_list;
    }

    /**
     * 更新则删除缓存
     */
    protected function _before_write(&$data) {
        F('news_list', NULL);
    }

    /**
     * 删除也删除缓存
     */
    protected function _after_delete($data, $options) {
        F('news_list', NULL);
    }
}