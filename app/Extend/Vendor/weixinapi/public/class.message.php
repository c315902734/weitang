<?php
class weixintoolmessage extends weixinapi {
	
	public $token;
	public $url;
	public $table = 'wx_message';
	public $tablesub = 'wx_temp';
	public $from = 'system';
	public $createtime;
	public $passive = true;
	public $to = false;
			
	//格式化配置
	public function __construct($token=false){
		$this->createtime = time();
		$this->token = $token;
		$this->url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$this->token;
	}
	
	//获取微信post过来的信息
	public function getMessage(){
		$data = $GLOBALS["HTTP_RAW_POST_DATA"];
		$xml = false;
		if(!empty($data)) $xml = (array)simplexml_load_string($data,'SimpleXMLElement',LIBXML_NOCDATA);
		return $xml;
	}

	/**
	 * 发送消息
	 * type 类型 text文本,imgtext图文,imgtextall多条图文
	 * to 接收用户openid
	 * content 内容
	 * passive 是否被动消息
	 */
	public function sendMessage($type,$content,$passive=true,$to=false){
		$this->passive = $passive;
		$this->to = $to;
		//先格式化类型对应的文件内容
		$data = $this->formatType($type,$content);
		if($passive){
			echo $data;
		}else{
			$this->send($data);
		}
		return true;
	}

	/**
	 * 格式化信息类型
	 * text 发送文本信息
	 * imgtext 单条图文
	 * imgtextall 多条图文
	 */
	public function formatType($type,$content){
		switch($type){
			case 'text':
				$data = $this->doText($content);
				break;
			case 'imgtext':
				$data = $this->doImgText($content);
				break;
			case 'imgtextall':
				$data = $this->doImgTextAll($content);
				break;
		}
		return $data;
	}
	
	//回复文本消息
	function doText($content){
		$result = '';
		//xml
		if($this->passive){
			$data = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>";
			$result = sprintf($data,$this->to,$this->from,$this->createtime,$this->changeImg($content));
		}
		//json
		else{
			$data = array(
				'touser' => $this->to,
				'msgtype' => 'text',
				'text' => array(
					'content' => strip_tags($content),//$this->changeImg($content)
				),
			);
			$result = json_encode($data,JSON_UNESCAPED_UNICODE);
		}
		return $result;
	}

	//回复图文消息
	function doImgText($content){
		$result = '';
		$num = count($content);
		//xml
		if($this->passive){
			$header = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>";
			$data = sprintf($header,$this->to,$this->from,$this->createtime,$num);
			foreach($content AS $k=>$v){
				$info = "<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>";
				$data .= sprintf($info,$v['title'],$this->changeImg($v['description']),$v['picurl'],$v['url']);
			}
					
			$data .= "</Articles>
					</xml>";
			$result = $data;
		}
		//json
		else{
			foreach($content AS $k=>$v){
				$content[$k]['description'] = $this->changeImg($v['description']);
			}
			$data = array(
				'touser' => $this->to,
				'msgtype' => 'news',
				'news' => array(
					'articles' => $content,
				)
			);
			$result = json_encode($data,JSON_UNESCAPED_UNICODE);
		}
		return $result;
	}

	//给用户发送消息
	public function send($data){
		$result = $this->doPost($this->url,$data,true);
		return $result;
	}

	//改变图片属性以及去除img
	public function changeImg($str){
		$str = @preg_replace('/(<img).+(src=\"?.+)\/(.+\.(jpg|gif|bmp|bnp|png)\"?).+>/e','$this->getEmotion("\\3")',$str);
		return $str;
	}

	public function getEmotion($str){
		$data = array(
			'1' => '/微笑',
			'2' => '/撇嘴',
			'3' => '/色',
			'4' => '/发呆',
			'5' => '/流泪',
			'6' => '/害羞',
			'7' => '/闭嘴',
			'8' => '/睡',
			'9' => '/大哭',
			'10' => '/害羞',
			'11' => '/害羞',
			'12' => '/害羞',
			'13' => '/害羞',
			'14' => '/害羞',
			'15' => '/害羞',
			'20' => '/可爱',
			'41' => '/坏笑',
			'49' => '/亲亲',
		);
		$i = intval($str);
		$n = $data[$i] ? $data[$i] : $str;
		return $n;

	}

}