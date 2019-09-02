<?php
class weixintoolmenu extends weixinapi{
	
	public $token;
	public $url;
	public $createurl;
	public $deleteurl;
	public $table = 'wx_menu';
	public $tablesub = 'wx_menu_sub';

	public function __construct($token=false){
		$this->token = $token;
		//https://api.weixin.qq.com/cgi-bin/menu/get?access_token=ACCESS_TOKEN
		$this->url = 'https://api.weixin.qq.com/cgi-bin/menu/get';
		//https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN
		$this->createurl = 'https://api.weixin.qq.com/cgi-bin/menu/create';
		//https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=ACCESS_TOKEN
		$this->deleteurl = 'https://api.weixin.qq.com/cgi-bin/menu/delete';
	}
	
	//curl微信接口获取并
	public function getMenu(){
		$list = $this->getDbMenu();
		return $list;
	}

	public function doMenu(){
		$target = 'access_token='.$this->token;
		$result = $this->doGet($this->url,$target);
		return $result;
	}

	public function deleteMenu(){
		$target = 'access_token='.$this->token;
		$result = $this->doGet($this->deleteurl,$target);
		return $result;
	}

	public function applyMenu(){
		$url = $this->createurl.'?access_token='.$this->token;
		//格式化菜单
		$data = $sub = $list = array();
		//取出所有一级菜单
		$data[0] = D($this->table)->order('sort ASC')->select();
		//取出所有二级菜单
		$data[1] = D($this->tablesub)->order('sort ASC')->select();
		if($data[1]){
			foreach($data[1] AS $k=>$v){
				$sub[$v['fid']][] = $v;
			}
		}
		//重构菜单
		if($data[0]){
			foreach($data[0] AS $k=>$v){
				$sublist = $sub[$v['id']] ? $sub[$v['id']] : false;
				$list['button'][] = $this->_formatMenuStr($v,$sublist);
			}
		}
		$menu = json_encode($list, JSON_UNESCAPED_UNICODE);
		$result = $this->doPost($url,$menu,true);
		return $this->_stdClassToArray(json_decode($result));
	}

	//强制同步菜单
	public function synchronization(){
		D($this->table)->delete();
		D($this->tablesub)->delete();
		$list = $this->insertDbMenu();
		return $list;
	}

	//本地数据库操作
	public function insertDbMenu(){
		$result = $this->doMenu();
		$list = $this->_formatStr($result);
		if($list['menu']['button']){
			foreach($list['menu']['button'] AS $k=>$v){
				$menu = array(
					'name' => $v['name'],
					'type' => $v['type'],
					'key'  => $v['key'] ? $v['key'] : '',
					'url'  => $v['url'] ? $v['url'] : '',
					'sort' => $k
				);
				$fid = D($this->table)->add($menu);
				if($v['sub_button']){
					foreach($v['sub_button'] AS $sk=>$sv){
						$sub = array(
							'fid'  => $fid,
							'name' => $sv['name'],
							'type' => $sv['type'] ? $sv['type'] : '',
							'key'  => $sv['key'] ? $sv['key'] : '',
							'url'  => $sv['url'] ? $sv['url'] : '',
							'sort' => $sk
						);
						D($this->tablesub)->add($sub);
					}
				}
			}
		}
		return $list;
	}
	
	public function getDbMenu(){
		$list = false;
		$menulist = D($this->table)->order('sort ASC')->select();
		if($menulist){
			foreach($menulist AS $k=>$v){
				$sub_button = D($this->tablesub)->where('fid='.$v['id'])->order('sort ASC')->select();
				$menulist[$k]['sub_button'] = !empty($sub_button) ? $sub_button : false;
			}
			$list['menu'] = array('button'=>$menulist);
		}
		return $list;
	}
	
	//格式化菜单类型
	protected function _formatMenuType($type){
		$result = $type=='view' ? 'view' : 'click';
		return $result;
	}

	protected function _formatMenuStr($data,$sublist=false){
		$array = array();
		if($sublist && is_array($sublist)){
			$array['name'] = $data['name'];
			$array['sub_button'] = array();
			foreach($sublist AS $k=>$v){
				$type = $v['type']=='click' ? 'click' : 'view';
				$sub['type'] = $type;
				$sub['name'] = $v['name'];
				if($type=='view'){
					$sub['url'] = $v['content'];
				}else{
					$sub['key'] = $v['attribute'].'_'.$v['assetid'];
				}
				$sub['sub_button'] = array();
				$array['sub_button'][] = $sub;
			}
		}else{
			$type = $data['type']=='view' ? 'view' : 'click';
			$array['type'] = $type;
			$array['name'] = $data['name'];
			if($type == 'view'){
				$array['url'] = $data['content'];
			}else{
				$array['key'] = $data['attribute'].'_'.$data['assetid'];
			}
		}
		return $array;
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