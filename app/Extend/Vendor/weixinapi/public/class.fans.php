<?php
class weixintoolfans extends weixinapi {
	
	public $token;
	public $url = array();
	public $table = 'wx_fans';
	public $tablesub = 'wx_groups';

	public function __construct($token=false){
		$this->token = $token;
		//https://api.weixin.qq.com/cgi-bin/groups/get?access_token=ACCESS_TOKEN
		$this->url['getgroups'] = 'https://api.weixin.qq.com/cgi-bin/groups/get';
		//https://api.weixin.qq.com/cgi-bin/groups/create?access_token=ACCESS_TOKEN
		$this->url['creategroups'] = 'https://api.weixin.qq.com/cgi-bin/groups/create';
		//https://api.weixin.qq.com/cgi-bin/groups/getid?access_token=ACCESS_TOKEN
		$this->url['getfansgroups'] = 'https://api.weixin.qq.com/cgi-bin/groups/getid';
		//https://api.weixin.qq.com/cgi-bin/groups/update?access_token=ACCESS_TOKEN
		$this->url['editgroups'] = 'https://api.weixin.qq.com/cgi-bin/groups/update';
		//https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=ACCESS_TOKEN
		$this->url['movefansgroups'] = 'https://api.weixin.qq.com/cgi-bin/groups/members/update';
		//https://api.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&next_openid=NEXT_OPENID
		$this->url['getfanslist'] = 'https://api.weixin.qq.com/cgi-bin/user/get';
		//https://api.weixin.qq.com/cgi-bin/user/info?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
		$this->url['getfansinfo'] = 'https://api.weixin.qq.com/cgi-bin/user/info';
	}
	
	//获取分组列表
	public function getGroups(){
		$target = 'access_token='.$this->token;
		$result = $this->doGet($this->url['getgroups'],$target);
		return $this->_formatStr($result);
	}
	
	//获取用户列表
	public function getFansList($openid=false){
		$target = 'access_token='.$this->token;
		if($openid) $target .= '&next_openid='.$openid;
		$result = $this->doGet($this->url['getfanslist'],$target);
		return $this->_formatStr($result);
	}

	//获取用户信息
	public function getFansInfo($openid){
		$target = 'access_token='.$this->token.'&openid='.$openid.'&lang=zh_CN';
		$result = $this->doGet($this->url['getfansinfo'],$target);
		return $this->_formatStr($result);
	}

	//获取用户所在分组
	public function getFansGroups($openid){
		$target = $this->url['getfansgroups'].'access_token='.$this->token;
		//{"openid":"od8XIjsmk6QdVTETa9jLtGWA6KBc"}
		$result = $this->doPost($target,json_encode(array('openid'=>$openid)));
		return $this->_formatStr($result);
	}

	//移动用户到分组
	public function moveFans($openid,$groupid){
		$groupid = intval($groupid);
		if(!$openid || !$groupid) return false;
		$target = $this->url['movefansgroups'].'access_token='.$this->token;
		//{"openid":"oDF3iYx0ro3_7jD4HFRDfrjdCM58","to_groupid":108}
		$result = $this->doPost($target,json_encode(array('openid'=>$openid,'to_groupid'=>$groupid),JSON_UNESCAPED_UNICODE));
		return $this->_formatStr($result);
	}

	//创建分组
	public function createGroups($name){
		if(!$name) return false;
		$target = $this->url['creategroups'].'access_token='.$this->token;
		//{"group":{"name":"test"}}
		$result = $this->doPost($target,json_encode(array('group'=>array('name'=>$name)),JSON_UNESCAPED_UNICODE));
		return $this->_formatStr($result);
	}

	//修改分组
	public function editGroups($id,$name){
		$id = intval($id);
		if(!$id || !$name) return false;
		$target = $this->url['editgroups'].'access_token='.$this->token;
		//{"group":{"id":108,"name":"test2_modify2"}}
		$result = $this->doPost($target,json_encode(array('group'=>array('id'=>$id,'name'=>$name)),JSON_UNESCAPED_UNICODE));
		return $this->_formatStr($result);
	}

	protected function _formatStr($result){
		return $this->_stdClassToArray(json_decode($result));
	}

	protected function _formatInsert($list,$str){
		$key = explode('_',$str) ? explode('_',$str) : array('',0);
		$data['attribute'] = $key[0];
		$data['assetid'] = $key[1] ? $key[1] : 0;
		if($list['type']=='view'){
			$data['content'] = $list['url'];
		}
		return $data;
	}

	//格式化stdClass数组
	protected function _stdClassToArray($arr){
		$result = array();
		if(empty($arr)) return false;
		foreach($arr AS $key =>$value){
			if(gettype($value) == 'array' || gettype($value) == 'object'){
				$result[$key] = $this->_stdClassToArray($value);
			}else{
				$result[$key] = $value;
			}
		}
		return $result;
	}
}