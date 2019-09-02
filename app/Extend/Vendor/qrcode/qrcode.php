<?php
class fqrcode {
	/**
	 * data                 二维码数据
	 * filename             生成的文件名
	 * errorCorrectionLevel 纠错级别：L、M、Q、H 
	 * matrixPointSize      点的大小：1到10 
	 */
	public function getCode($data,$filename,$level='H',$size=10){
		//引入phpqrcode库文件
		include('phpqrcode.php'); 
		QRcode::png($data, false, $level, $size);
	}
}
