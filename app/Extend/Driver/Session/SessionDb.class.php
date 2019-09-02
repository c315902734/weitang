<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * 数据库方式Session驱动
 *    CREATE TABLE think_session (
 *      session_id varchar(255) NOT NULL,
 *      session_expire int(11) NOT NULL,
 *      session_data blob,
 *      UNIQUE KEY `session_id` (`session_id`)
 *    );
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Session
 * @author    liu21st <liu21st@gmail.com>
 */
class SessionDb
{

    /**
     * Session有效时间
     */
    protected $lifeTime;

    /**
     * session保存的数据库名
     */
    protected $sessionTable;

    /**
     * 数据库句柄
     */
    protected $handler;

    /**
     * 打开Session
     * @access public
     * @param string $savePath
     * @param mixed $sessName
     */
    public function open($savePath, $sessName)
    {
        if (in_array(GROUP_NAME, C("API_MODULES"))) {
            return false;
        }
        $this->lifeTime     = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : ini_get('session.gc_maxlifetime');
        $this->sessionTable = C("DB_PREFIX") . (C('SESSION_TABLE') ? C('SESSION_TABLE') : "session");
        //分布式数据库
        $host = explode(',', C('DB_HOST'));
        $port = explode(',', C('DB_PORT'));
        $name = explode(',', C('DB_NAME'));
        $user = explode(',', C('DB_USER'));
        $pwd  = explode(',', C('DB_PWD'));
        if (1 == C('DB_DEPLOY_TYPE')) {
            //读写分离
            if (C('DB_RW_SEPARATE')) {
                $w = intval(mt_rand(0, C('DB_MASTER_NUM') - 1));
                if (is_numeric(C('DB_SLAVE_NO'))) {//指定服务器读
                    $r = C('DB_SLAVE_NO');
                }
                else {
                    $r = intval(mt_rand(C('DB_MASTER_NUM'), count($host) - 1));
                }
                //主数据库链接
                $handler = mysqli_connect(
                    $host[$w] . (isset($port[$w]) ? ':' . $port[$w] : ':' . $port[0]),
                    isset($user[$w]) ? $user[$w] : $user[0],
                    isset($pwd[$w]) ? $pwd[$w] : $pwd[0]
                );
                $dbSel   = mysqli_select_db(
                    isset($name[$w]) ? $name[$w] : $name[0]
                    , $handler);
                if (!$handler || !$dbSel) {
                    return false;
                }
                $this->handler[0] = $handler;
                //从数据库链接
                $handler = mysqli_connect(
                    $host[$r] . (isset($port[$r]) ? ':' . $port[$r] : ':' . $port[0]),
                    isset($user[$r]) ? $user[$r] : $user[0],
                    isset($pwd[$r]) ? $pwd[$r] : $pwd[0]
                );
                $dbSel   = mysqli_select_db(
                    isset($name[$r]) ? $name[$r] : $name[0]
                    , $handler);
                if (!$handler || !$dbSel) {
                    return false;
                }
                $this->handler[1] = $handler;
                return true;
            }
        }
        //从数据库链接
        $r = intval(mt_rand(0, count($host) - 1));

        $conn_host = $host[$r];
        $conn_port = isset($port[$r]) ? $port[$r] : $port[0];
        $conn_user = isset($user[$r]) ? $user[$r] : $user[0];
        $conn_pwd  = isset($pwd[$r]) ? $pwd[$r] : $pwd[0];
        $handler   = mysqli_connect(
            $conn_host,
            $conn_user,
            $conn_pwd,
            '',
            $conn_port
        );

        $dbSel = mysqli_select_db($handler,
            isset($name[$r]) ? $name[$r] : $name[0]
        );
        if (!$handler || !$dbSel) {
            return false;
        }
        $this->handler = $handler;
        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close()
    {
        if (is_array($this->handler)) {
            $this->gc($this->lifeTime);
            return (mysqli_close($this->handler[0]) && mysqli_close($this->handler[1]));
        }
        $this->gc($this->lifeTime);
        return mysqli_close($this->handler);
    }

    /**
     * 读取Session
     * @access public
     * @param string $sessID
     */
    public function read($sessID)
    {
        if (in_array(GROUP_NAME, C("API_MODULES"))) {
            return false;
        }

        $handler = is_array($this->handler) ? $this->handler[1] : $this->handler;
        $sql     = "SELECT session_data AS data FROM " . $this->sessionTable . " WHERE session_id = '$sessID'   AND session_expire >" . time();
        $res     = mysqli_query($handler, $sql);
        if ($res) {
            $row = mysqli_fetch_assoc($res);
            return $row['data'];
        }
        return "";
    }

    /**
     * 写入Session
     * @access public
     * @param string $sessID
     * @param String $sessData
     */
    public function write($sessID, $sessData)
    {
        if (in_array(GROUP_NAME, C("API_MODULES"))) {
            return false;
        }

        $handler = is_array($this->handler) ? $this->handler[0] : $this->handler;
        $expire  = time() + $this->lifeTime;
        $sql     = "REPLACE INTO  " . $this->sessionTable . " (  session_id, session_expire, session_data)  VALUES( '$sessID', '$expire',  '$sessData')";
        mysqli_query($handler, $sql);
        mysqli_error($handler);

        if (mysqli_affected_rows($handler)) {
            return true;
        }
        return false;
    }

    /**
     * 删除Session
     * @access public
     * @param string $sessID
     */
    public function destroy($sessID)
    {
        $handler = is_array($this->handler) ? $this->handler[0] : $this->handler;
        mysqli_query($handler, "DELETE FROM " . $this->sessionTable . " WHERE session_id = '$sessID'");
        if (mysqli_affected_rows($handler)) {
            return true;
        }
        return false;
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param string $sessMaxLifeTime
     */
    public function gc($sessMaxLifeTime)
    {
        $handler = is_array($this->handler) ? $this->handler[0] : $this->handler;
        mysqli_query($handler, "DELETE FROM " . $this->sessionTable . " WHERE session_expire < " . time());
        return mysqli_affected_rows($handler);
    }

    /**
     * 打开Session
     * @access public
     */
    public function execute()
    {
        session_set_save_handler(array(&$this, "open"),
            array(&$this, "close"),
            array(&$this, "read"),
            array(&$this, "write"),
            array(&$this, "destroy"),
            array(&$this, "gc"));
    }

    protected function ajaxReturn($data)
    {
        header('Content-Type:text/json; charset=utf-8');
        if (PHP_VERSION > '5.4.0') {
            exit(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        else {
            exit(json_encode($data));
        }
    }
}
