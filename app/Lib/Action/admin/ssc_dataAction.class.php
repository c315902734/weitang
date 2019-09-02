<?php
class ssc_dataAction extends backendAction
{
	protected $pk = '*';
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('ssc_data');
    }

    protected function _search() {
        $map = array();
        ($time_start = $this->_request('time_start', 'trim')) && $map['opentime'][] = array('egt', $time_start);
        ($time_end = $this->_request('time_end', 'trim')) && $map['opentime'][] = array('elt', $time_end);
       
        $this->assign('search', array(
			'time_start' => $time_start,
            'time_end' => $time_end,
        ));
        return $map;
    }

    public function _before_index(){
		$big_menu = array(
            'title' => '补充开奖信息',
            'iframe' => U('ssc_data/add'),
            'id' => 'add',
            'width' => '400',
            'height' => '100',
        );
        $this->assign('big_menu', $big_menu);
	}

	protected function _before_insert($data = '') {
    	//检测分类是否存在
    	if(!$data['expect'] || !$data['opencode'] || !$data['opentime']){
    		$this->ajaxReturn(0, L('operation_failure'));
    	}
    	$data['lastno'] = substr($data['opencode'],-1);
    	$data['opentimestamp'] = strtotime($data['opentime']);
    	return $data;
    }



}