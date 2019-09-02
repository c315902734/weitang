<?php

class searchAction extends mbaseAction
{
    public function index()
    {
        $keywords = C('search_keywords');
        $this->assign(compact('keywords'));
        $this->show_footer();
        $this->display();
    }
}