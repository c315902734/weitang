<?php

class settingModel extends baseModel
{

    /**
     * 获取配置信息写入缓存
     */
    public function setting_cache()
    {
        $setting = array();
        $res     = $this->getField('name,data');
        foreach ($res as $key => $val) {
            $setting['ins_' . $key] = unserialize($val) ? unserialize($val) : $val;
        }
        F('setting', $setting);
        return $setting;
    }

    /**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options)
    {
        F('setting', NULL);
    }

    public function get($name)
    {
        return $this->where(compact('name'))->getField('data');
    }
	function update($setting){
        foreach ($setting as $key => $val) {
            $val = is_array($val) ? serialize($val) : $val;
            if($this->where(array('name' => $key))->find()){
                $this->where(array('name' => $key))->save(array('data' => $val));
            }else{
                $this->add(array('name'=>$key,'data'=>$val));
            }
        }                
        if(file_exists(DATA_PATH."setting.php")){
            !unlink(DATA_PATH."setting.php")&&exit(DATA_PATH."setting.php文件无法删除，请检查文件权限");    
        }         
    }
}