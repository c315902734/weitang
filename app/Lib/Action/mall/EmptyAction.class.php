<?php

/**
 * 404错误
 */
class EmptyAction extends Action
{
    public function _empty()
    {
        $res = pathinfo($_SERVER['PATH_INFO']);
        if (in_array(strtolower($res['extension']), ['jpg', 'jpeg', 'bmp', 'png', 'gif', 'webp'])) {
            header("location:" . __SITEROOT__ . "/data/upload/NOPIC.png");
        }
        else {
            send_http_status(404);
            $this->display(TMPL_PATH . '404.html');
        }
    }
}