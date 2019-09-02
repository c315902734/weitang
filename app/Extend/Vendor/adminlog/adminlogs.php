<?php

/**
 * +------------------------------------------------------------------------------
 * 基于用户的操作记录验证类
 * +------------------------------------------------------------------------------
 * @author    baipre.com@照妖镜 <8205524@qq.com>
 * @version   1.0.1
 * +------------------------------------------------------------------------------
 */
class AdminLog
{

    protected $onoff;  //是否启用
    public $error;  //错误信息

    /**
     * @todo  验证是否开启记录
     */
    public function __construct($config = array())
    {
        if (empty($config)) {
            return false;
        } else {
            $this->onoff = isset($config['onoff']) ? $config['onoff'] : false;
        }
        if ($this->onoff === false) {
            return false;
        }
    }

    /**
     * @获取客户端IP地址
     * @$type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     */
    protected function getClientIp($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     * @检测日志文件是否存在,如果不存在则创建新文件
     * 网址,IP,时间,uid,操作系统,浏览器
     */
    public function doLog($menuid, $model, $action, $sign = '')
    {
        if (!class_exists($model."Model")) return;
		if (!$model) return;
        $where              = array();
        
        $menuid = D('menu')->where(array('module_name'=>$model,'action_name'=>$action))->getField('id');
        $pid = D('menu')->where(array('module_name'=>$model,'action_name'=>$action))->getField('pid');
        $pname = D('menu')->where(array('id'=>$pid))->getField('name');

        $where['id']        = $menuid;
        $menu_info          = D('menu')->where($where)->find();
        $data['admin_id']   = $_SESSION['admin']['id'];
        $data['admin_name'] = $_SESSION['admin']['username'];
        $data['admin_time']   = date('Y-m-d H:i:s');
        $data['actions']     = 'module_name:' . $pname . ':'.$model.',action_name:' . $menu_info['name'].':'.$action;
        $log_action         = L('log_action');
        $data['remark']     = $data['admin_name'].'执行了'.$menu_info['name'] . '的操作';
        $id = $_REQUEST[D($model)->getPk()];
        if($id){
            $data['remark'] .= '，它的相关ID：'.$id;
        }
        $data['ip']         = $this->getClientIp();
        D('admin_log')->add($data);
    }

    public function __destruct()
    {
        $this->onoff = false;
        $this->error = '';
    }
}
