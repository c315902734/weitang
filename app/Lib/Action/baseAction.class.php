<?php

/**
 * 控制器基类
 */
class baseAction extends Action
{
    protected $uploadPath;
    protected $uploadPathSub;

    protected function _initialize()
    {
        //消除所有的magic_quotes_gpc转义        
        Input::noGPC();
        //初始化网站配置
        if (false === $setting = F('setting')) {
            $setting = D('setting')->setting_cache();
        }
        C($setting);
        //发送邮件
        $this->assign('async_sendmail', session('async_sendmail'));
        $this->assign('server', $_SERVER);
        $this->assign('Think', array(
            'get'     => $_GET,
            'post'    => $_POST,
            'request' => $_REQUEST,
            'server'  => $_SERVER,
            'cookie'  => $_COOKIE,
            'session' => $_SESSION,
        ));
        //当前上传目录
        $this->uploadPathSub = date("/Y/m/d/");
        $this->uploadPath    = './data/upload/assets' . $this->uploadPathSub;
    }

    public function _empty()
    {
        $this->_404();
    }

    protected function _404($url = '')
    {
        if ($url) {
            redirect($url);
        }
        else {
            // 发送HTTP状态
            send_http_status(404);
            $this->display(TMPL_PATH . '404.html');
            exit;
        }
    }

    /**
     * 添加邮件到队列
     */
    protected function _mail_queue($to, $subject, $body, $priority = 1)
    {
        $to_emails = is_array($to) ? $to : array($to);
        $mails     = array();
        $time      = time();
        foreach ($to_emails as $_email) {
            $mails[] = array(
                'mail_to'      => $_email,
                'mail_subject' => $subject,
                'mail_body'    => $body,
                'priority'     => $priority,
                'add_time'     => $time,
                'lock_expiry'  => $time,
            );
        }
        D('mail_queue')->addAll($mails);

        //异步发送邮件
        $this->send_mail(false);
    }

    /**
     * 发送邮件
     */
    public function send_mail($is_sync = true)
    {
        if (!$is_sync) {
            //异步
            session('async_sendmail', true);
            return true;
        }
        else {
            //同步
            session('async_sendmail', null);
            return D('mail_queue')->send();
        }
    }

    /**
     * 上传文件默认规则定义
     */
    protected function _upload_init($upload)
    {
        $allow_max  = C('ins_attr_allow_size'); //读取配置
        $allow_exts = explode(',', C('ins_attr_allow_exts')); //读取配置
        $allow_max && $upload->maxSize = $allow_max * 1024; //文件大小限制
        $allow_exts && $upload->allowExts = $allow_exts; //文件类型限制
        $upload->saveRule = 'uniqid';
        return $upload;
    }

    protected function uploadFile($content)
    {
        $saveDir   = C('ASSETS_DIR_RULE');
        $urlPrefix = 'http://' . $_SERVER['HTTP_HOST'] . '/data/upload/assets';
        if (strpos($content, $urlPrefix) === 0) {
            $res      = substr($content, strlen($urlPrefix));
            $saveDir  = substr($res, 0, strrpos($res, '/') + 1);
            $saveName = substr($res, strrpos($res, '/') + 1);
        }
        else if (preg_match('/^\/(\d+)\/(\d+)\/(\d+)\//', $content) > 0) {
            $saveDir  = substr($content, 0, strrpos($content, '/') + 1);
            $saveName = substr($content, strrpos($content, '/') + 1);
        }
        else {
            $saveName = uniqid() . '.jpg';
            $path     = rtrim(C('ins_attach_path'), '/') . '/assets' . $saveDir;
            mkdir($path, 0777, true);

            file_put_contents($path . $saveName, _base64_decode($content));
            imageThumb($path . $saveName);
        }

        $data = array(
            'saveDir'  => $saveDir,
            'savePath' => $saveDir . $saveName,
            'fullPath' => __SITEROOT__ . '/data/upload/assets' . $saveDir . $saveName,
        );
        return compact('data');
    }

    /**
     * 上传文件
     */
    protected function _upload($file, $thumb = array(), $save_rule = 'uniqid')
    {
        $saveDir = C('ASSETS_DIR_RULE');

        $upload           = new UploadFile();
        $upload->savePath = rtrim(C('ins_attach_path'), '/') . '/assets' . $saveDir;

        mkdir($upload->savePath, 0777, true);
        //判断上传图片是否缩略
        if ($thumb) {
            $upload->thumb             = true;
            $upload->thumbMaxWidth     = $thumb['width'];
            $upload->thumbMaxHeight    = $thumb['height'];
            $upload->thumbPrefix       = '';
            $upload->thumbSuffix       = isset($thumb['suffix']) ? $thumb['suffix'] : '_thumb';
            $upload->thumbExt          = isset($thumb['ext']) ? $thumb['ext'] : '';
            $upload->thumbRemoveOrigin = isset($thumb['remove_origin']) ? true : false;
        }
        //自定义上传规则
        $upload = $this->_upload_init($upload);

        if ($result = $upload->uploadOne($file)) {
            $data = array();
            foreach ($result as $key => $val) {
                $savePath = $result[$key]['savepath'] = trim($val['savepath'], '/') . '/';
                if (empty($this->uploadCropData)) {
                    $cropData = $this->_get('cropData');
                }
                else {
                    $cropData = $this->uploadCropData[$key];
                }
                if (strpos($savePath, '/assets/') > 0) {
                    imageThumb($savePath . $val['savename'], json_decode(base64_decode($cropData)));
                }
                $data [] = array(
                    'saveDir'  => $saveDir,
                    'savePath' => $saveDir . $val['savename'],
                    'fullPath' => __SITEROOT__ . '/data/upload/assets' . $saveDir . $val['savename'],
                );
            }
            $result = array('error' => 0, 'info' => $result, 'data' => $data);
        }
        else {
            $result = array('error' => 1, 'info' => $upload->getErrorMsg());
        }
        $this->uploadCropData = array();
        return $result;
    }

    protected function ajaxResult($params, $type = 'json')
    {
        $result = array('status' => 0, 'msg' => '(' . __LINE__ . ')操作无效!',);

        if (is_string($params)) {
            $result = array('status' => 0, 'msg' => $params);
        }

        if (is_numeric($params)) {
            $result = array('status' => 1, 'msg' => '操作成功!', 'data' => $params);
        }

        if (is_bool($params)) {
            $result = array('status' => $params == true ? 1 : 0, 'msg' => $params == true ? '操作成功!' : '操作失败!');
        }

        if (is_array($params)) {
            if (isset($params['msg']) && isset($params['status'])) {
                $result = $params;
            }
            else if (count($params)) {
                $result = array('status' => 1, 'data' => $params, 'msg' => '操作成功');
            }
            else {
                $result = array('status' => 0, 'data' => [], 'msg' => '操作失败');
            }
        }
        if (!IS_AJAX) {
            if ($result['status']) {
                $this->success($result['msg']);
                exit();
            }
            else {
                $this->error($result['msg']);
                exit();
            }
        }
        else {
            parent::ajaxReturn($result, $type);
        }
    }

    protected function ajaxResultSuccess($msg = '操作成功', $extra = array())
    {
        $data = array(
            'status' => 1,
            'msg'    => $msg,
        );
        if (is_array($extra)) {
            $data = array_merge($data, (array)$extra);
        }
        $this->ajaxResult($data);
    }

    protected function ajaxResultError($msg = '操作失败', $extra = array())
    {
        $data = array(
            'status' => 0,
            'msg'    => $msg,
        );
        if (is_array($extra)) {
            $data = array_merge($data, (array)$extra);
        }
        $this->ajaxResult($data);
    }

    protected function getApiSession($user)
    {
        $content = "$user[id]/" . time();
        return strEncrypt($content, md5(C('API_SECRET_KEY')));
    }

    protected function strEncrypt($content)
    {
        return strEncrypt($content, md5(C('API_SECRET_KEY')));
    }

    protected function strDecrypt($content)
    {
        return strDecrypt($content, md5(C('API_SECRET_KEY')));
    }

    protected function arrEncrypt($content)
    {
        return strEncrypt(serialize($content), md5(C('API_SECRET_KEY')));
    }

    protected function arrDecrypt($content)
    {
        return unserialize(strDecrypt($content, md5(C('API_SECRET_KEY'))));
    }


    protected function sendSMS($mobile, $content)
    {
        $data           = array(
            'apikey' => C('SMS_APP_KEY'),
            'mobile' => $mobile,
            'text'   => $content,
        );
        $result         = Http::post('http://yunpian.com/v1/sms/send.json', $data);
        $result         = json_decode($result, true);
        $result['data'] = $data;
        return $result;
    }

    protected function sendUserSMS($id, $content)
    {
        $tele = D('user')->where(compact('id'))->getField('tele');
        if (empty($tele)) {
            return;
        }
        $this->sendSMS($tele, $content);
    }

    protected function get_api_user()
    {
        $token = $this->_post('token', 'trim', $_COOKIE['token']);
        if (empty($token)) {
            $token = session('APP_TOKEN');
        }
        if ($token) {
            $res = strDecrypt($token, md5(C('API_SECRET_KEY')));  // 获取解密后的字符串
            $res    = explode('/', $res);   // 将解密后的字符串分割成一个数组
            // 根据分割后数组取得用户的id，进而获取用户的个人信息
            $result = D('user')->where(array('id' => intval($res[0])))->find();
            return $result;
        }
        return null;
    }

    protected function get_invite_code($uid)
    {
        if (empty($uid)) {
            return '';
        }
        return $this->arrEncrypt([
            'uid'         => $uid,
            'create_time' => current_date(),
        ]);
    }

    protected function check_invite_code()
    {
        $invite_code = $this->_request('invite_code');
        if ($invite_code) {
            $invite_code_res = $this->arrDecrypt($invite_code);
            $invite_uid      = $invite_code_res['uid'];
            if (!empty($invite_uid)) {
                session('invite_code', $invite_code);
                file_put_contents('./data/runtime/check_invite_code.log', $invite_code . PHP_EOL, FILE_APPEND);
            }
        }
    }
}