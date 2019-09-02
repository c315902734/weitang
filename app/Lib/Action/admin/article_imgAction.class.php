<?php

class article_imgAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('article_img');
    }

    public function _af_index($list){
        foreach($list as $key=>$val){
            $list[$key]['title'] = D('article')->where(array('id'=>$val['article_id']))->getField('title');
        }
        return $list;
    }

}