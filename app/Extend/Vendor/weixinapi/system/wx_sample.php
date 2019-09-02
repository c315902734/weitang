<?php

//traceHttp();

define("validTOKEN", "80ecc5a8c17f9fee84c3");
$wechatObj = new wechatCallbackapiTest();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
}else{
    echo '错误';
}

class wechatCallbackapiTest {

	public function valid(){
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()){
			echo $echoStr;
			exit;
		}
	}

	private function checkSignature(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
		$token = validTOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

function traceHttp(){
	logger("\n\nREMOTE_ADDR:".$_SERVER["REMOTE_ADDR"].(strstr($_SERVER["REMOTE_ADDR"],'101.226')? " FROM WeiXin": "Unknown IP"));
	logger("QUERY_STRING:".$_SERVER["QUERY_STRING"]);
}
function logger($log_content){
	$max_size = 500000;
	$log_filename = "log.xml";
	if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
	file_put_contents($log_filename, date('Y-m-d H:i:s').$log_content."\r\n", FILE_APPEND);
}
