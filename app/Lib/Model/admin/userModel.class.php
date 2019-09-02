<?php

class userModel extends baseModel
{
    protected $_validate = array(
        array('username', 'require', '{%username_require}'), //不能为空
        array('tele', 'require', '手机号码不能为空'), //不能为空
        array('repassword', 'password', '{%inconsistent_password}', 0, 'confirm'), //确认密码
        //array('email', 'email', '{%email_error}'), //邮箱格式
        array('username', '1,20', '{%username_length_error}', 0, 'length', 1), //用户名长度
        array('password', '6,20', '{%password_length_error}', 0, 'length', 1), //密码长度
        //array('username', '', '{%username_exists}', 0, 'unique', 1), //新增的时候检测重复
    );

    protected $_auto = array(
        array('password', 'md5', 1, 'function'), //密码加密
        array('reg_time', 'getCurrentTime', 1, 'callback'), //注册时间
        array('last_time', 'getCurrentTime', 3, 'callback'), //注册时间
        array('reg_ip', 'get_client_ip', 1, 'function'), //注册IP
    );

    /**
     * 修改用户名
     */
    public function rename($map, $newname)
    {
        if ($this->where(array('username' => $newname))->count('id')) {
            return false;
        }
        $this->where($map)->save(array('username' => $newname));
        $uid = $this->where(array('username' => $newname))->getField('id');
        //修改商品表中的用户名
        M('item')->where(array('uid' => $uid))->save(array('uname' => $newname));
        return true;
    }

    public function name_exists($name, $id = 0)
    {
        $where = "username='" . $name . "' AND id<>'" . $id . "'";
        $result = $this->where($where)->count('id');
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function email_exists($email, $id = 0)
    {
        $where = "email='" . $email . "' AND id<>'" . $id . "'";
        $result = $this->where($where)->count('id');
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
	protected function _parse_item($result, $_options)
    {
		if($result['birthday'] > 0){
			$result['age'] = $this->getAge($result['birthday']);
		}else{
			$result['age'] = 0;
		}
        return $result;
    }
	//根据出生日期获得年纪
	protected function getAge($birthday) {
		$age = 0;
		$year = $month = $day = 0;
		if (is_array($birthday)) {
			extract($birthday);
		} else {
			if (strpos($birthday, '-') !== false) {
				list($year, $month, $day) = explode('-', $birthday);
				$day = substr($day, 0, 2);
			}
		}
		$age = date('Y') - $year;
		if (date('m') < $month || (date('m') == $month && date('d') < $day)) $age--;
		return $age;
	}
}