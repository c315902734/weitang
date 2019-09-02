<?php

defined('THINK_PATH') or exit();
/**
 * 行为扩展：模板内容输出替换
 */
class content_replaceBehavior extends Behavior {

    public function run(&$content){
        $content = $this->_replace($content);
    }

    private function _replace($content) {
        $replace = array();
        //静态资源地址
        $statics_url = C('ins_statics_url');
        if ($statics_url != '') {
            $replace['__STATIC__'] = $statics_url;
        } else {
            $replace['__STATIC__'] = __ROOT__.'/static';
        }
        //附件地址
        $replace['__UPLOAD__'] = __ROOT__.'/data/upload';
        $replace['__ASSETS__'] = __ROOT__ . "/app/Tpl/" . GROUP_NAME;
        if (C("DEFAULT_THEME")) {
            $replace['__ASSETS__'] .= "/" . C("DEFAULT_THEME");
        }
        $replace['__ASSETS__'] .= "/public";
        $replace['__SITEROOT__'] = get_siteroot();
        $content = str_replace(array_keys($replace),array_values($replace),$content);
        return $content;
    }
}