<?php

class assetsAction extends mbaseAction
{
    public function item_img()
    {
        $id  = $this->_get('id', 'intval', 0);
        $img = D('item')->where(compact('id'))->getField('img');
        header("location:" . attach($img));
    }

    public function avatar()
    {
        $id  = $this->_get('id', 'intval', 0);
        $img = D('user')->where(['id' => $id])->getField('img');
        header("location:" . avatar($img));
    }

    public function uploadImage()
    {
        $res = [];
        foreach ($_FILES as $key => $val) {
            $res = $this->_upload($val);
            break;
        }
        $this->ajaxResult($res['data'][0]);
    }

    public function placeholder()
    {
        header("location:" . C('CDN_URL') . "/data/upload/NOPIC.png");
    }

    public function article()
    {
        $id   = $this->_get('id');
        $info = D('article')->where(compact('id'))->find();
        if (IS_AJAX) {
            $info = $info['info'];
            $this->ajaxResult(compact('info'));
        }
        else {
            $this->assign(compact('info'));
            $this->display();
        }
    }
}