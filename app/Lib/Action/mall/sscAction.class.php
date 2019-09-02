<?php

class sscAction extends mbaseAction
{

    public function index()
    {
        $mod = D('ssc_data');
		$page_size = 10;

        $count = $mod->where($where)->count('id');
        $pager = $this->_pager($count,$page_size);

		$list = $mod->order('opentime desc')->limit($pager->firstRow, $pager->listRows)->select();
		foreach($list as $key=>$val){
			$list[$key]['is_odd'] = (is_numeric($val['lastno'])&($val['lastno']&1));
			$list[$key]['is_even'] = (is_numeric($val['lastno'])&(!($val['lastno']&1)));
			$list[$key]['expecttime'] = getExpectTime($val['expect']);

		}
		$wait = array();
		for($i=2;$i>0;$i--){
			$wait[] = array(
				'expect' => $list[0]['expect'] + $i,
				'expecttime' => getExpectTime($list[0]['expect'] + $i),
			);
		}

		

        $this->assign(compact('wait','list'));
        //当前页码
        $p  = $this->_get('p', 'intval', 1);
        $cp = ceil($count / $page_size);
        $this->assign('p', $p);
        $this->assign('cp', $cp);
        if ($p < $cp) {
            $this->assign('show_load', 1);
            $this->assign('show_page', 1);
        }
        if (IS_AJAX) {
            $resp = $this->fetch('waterfall');
            $data = array(
                'isfull' => $p >= $cp ? 0 : 1,
                'html'   => $resp
            );
            $this->ajaxResult($data);
        }
        else {
			$this->show_footer();
            $this->display();
        }
    }
}