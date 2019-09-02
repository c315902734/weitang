<?php

class item_cateAction extends mbaseAction
{
    public function index()
    {
        $pid = $this->_get('pid', 'intval', 0);
        $where = [
            'pid'    => $pid,
            'status' => 1,
        ];
        $cate_list = D('item_cate')->where($where)->select();

        foreach ($cate_list as $key => $val) {
            $sub_where = ['pid' => $val['id']];
            $cate_list[$key]['child_list'] = D('item_cate')->where($sub_where)->select();
        }

        $this->assign(compact('cate_list'));
        $this->show_footer();
        $this->display();
    }

    protected function get_item_cate_ids($mid)
    {
        $res      = D('item')->field('cate_id')->where(compact('mid'))->distinct('cate_id')->select();
        $cate_ids = [];
        foreach ($res as $val) {
            $cate_id = $val['cate_id'];

            if (!in_array($cate_id, $cate_ids)) {
                $cate_ids[] = $cate_id;
            }

            $spid     = D('item_cate')->where(['id' => $cate_id])->getField('spid');
            $spid_res = explode('|', $spid);
            foreach ($spid_res as $v) {
                if (empty($v)) {
                    continue;
                }
                if (!in_array($v, $cate_ids)) {
                    $cate_ids[] = $v;
                }
            }
        }
        foreach ($cate_ids as $key => $cate_id) {
            if (D('item')->where(compact('mid', 'cate_id'))->count() == 0) {
                unset($cate_ids[$key]);
            }
        }
        return $cate_ids;
    }
}