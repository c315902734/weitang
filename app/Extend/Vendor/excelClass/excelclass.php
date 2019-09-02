<?php
class excelclass {

	/*
	* $file excel文件路径
	* $column 总列数
	* $start 从第几行开始
	* $end 结束至第几行
	*/
	public function getFile($file,$column=2,$start=2,$end=false){
		//phpexcel
		require('PHPExcel.php');
		$PHPExcel = new PHPExcel();
		$PHPReader = new PHPExcel_Reader_Excel2007();
		if(!$PHPReader->canRead($file)){
			$PHPReader = new PHPExcel_Reader_Excel5();
			if(!$PHPReader->canRead($file)){
				return array("error"=>2);
			}
		}
		//读取文件内容
		$PHPExcel = $PHPReader->load($file);
		//只读第一页
		$currentSheet = $PHPExcel->getSheet(0);
		$allRow = $currentSheet->getHighestRow();
		//总行数
		if($end){
			$rownum = $end<=$allRow ? $end : $allRow;
		}
		//标题
		$array["title"] = $currentSheet->getTitle();
		$array["rows"]  = $allRow;
		$arr = array();
		for($currentRow=$start;$currentRow<=$allRow;$currentRow++){
			$row = array();
			for($j=0;$j<$column;$j++){
				$row[] = $currentSheet->getCellByColumnAndRow($j,$currentRow)->getValue();
			}
			$arr[$currentRow] = $row;
		}
		$array["list"] = $arr;
		//必须的，不然ThinkPHP和PHPExcel会冲突
		spl_autoload_register(array('Think','autoload'));
		unset($currentSheet);
		unset($PHPReader);
		unset($PHPExcel);
		return $array;
	}

	/*
     * $name 文件名
     * $data 数据 二维数组
     */
    public function echoOrderFile($name = '', $data)
    {
        if (empty($data)) return false;
        //phpexcel
        require('PHPExcel.php');
        $PHPExcel = new PHPExcel();
        $count    = count($data);

		//订单id,下单用户id,用户名称,total,prices,升级后价格,升级后总价,物流公司,物流sn,物流时间,收货人信息(姓名,电话,省市区,地址),是否抽奖,单双,对应期号,抽奖时间,商品id,商品名称.

        $PHPExcel->getActiveSheet()->setCellValue('A1', '订单id');
        $PHPExcel->getActiveSheet()->setCellValue('B1', '订单状态');
        $PHPExcel->getActiveSheet()->setCellValue('C1', '下单用户id');
        $PHPExcel->getActiveSheet()->setCellValue('D1', '用户名称');
        $PHPExcel->getActiveSheet()->setCellValue('E1', '订单价格');
        $PHPExcel->getActiveSheet()->setCellValue('F1', '商品价格');
        $PHPExcel->getActiveSheet()->setCellValue('G1', '升级后价格');
        $PHPExcel->getActiveSheet()->setCellValue('H1', '升级后总价');
        $PHPExcel->getActiveSheet()->setCellValue('I1', '物流公司');
        $PHPExcel->getActiveSheet()->setCellValue('J1', '物流sn');
        $PHPExcel->getActiveSheet()->setCellValue('K1', '物流时间');
        $PHPExcel->getActiveSheet()->setCellValue('L1', '收货人姓名');
        $PHPExcel->getActiveSheet()->setCellValue('M1', '收货人电话');
        $PHPExcel->getActiveSheet()->setCellValue('N1', '收货人信息');
        $PHPExcel->getActiveSheet()->setCellValue('O1', '是否抽奖');
        $PHPExcel->getActiveSheet()->setCellValue('P1', '单双');
        $PHPExcel->getActiveSheet()->setCellValue('Q1', '对应期号');
        $PHPExcel->getActiveSheet()->setCellValue('R1', '抽奖时间');
        $PHPExcel->getActiveSheet()->setCellValue('S1', '商品id');
        $PHPExcel->getActiveSheet()->setCellValue('T1', '商品名称');
        $PHPExcel->getActiveSheet()->setCellValue('U1', '商品数量');
        $PHPExcel->getActiveSheet()->setCellValue('V1', '商品规格');


        //$PHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);


        for ($i = 2; $i <= $count + 1; $i++) {

			$PHPExcel->getActiveSheet()->setCellValue('A' . $i, $this->convertUTF8($data[$i - 2]['id']));
			$PHPExcel->getActiveSheet()->setCellValue('B' . $i, $this->convertUTF8($data[$i - 2]['status']));

            $PHPExcel->getActiveSheet()->setCellValue('C' . $i, $this->convertUTF8($data[$i - 2]['orderid']."\t"));
            $PHPExcel->getActiveSheet()->setCellValue('D' . $i, $this->convertUTF8($data[$i - 2]['uname'].'('.$data[$i - 2]['uid'].')'));
            $PHPExcel->getActiveSheet()->setCellValue('E' . $i, $this->convertUTF8($data[$i - 2]['total']));
            $PHPExcel->getActiveSheet()->setCellValue('F' . $i, $this->convertUTF8($data[$i - 2]['total']));
            $PHPExcel->getActiveSheet()->setCellValue('G' . $i, $this->convertUTF8($data[$i - 2]['lottery_price']));
            $PHPExcel->getActiveSheet()->setCellValue('H' . $i, $this->convertUTF8($data[$i - 2]['lottery_total']."\t"));
            $PHPExcel->getActiveSheet()->setCellValue('I' . $i, $this->convertUTF8($data[$i - 2]['express_name']."\t"));
            $PHPExcel->getActiveSheet()->setCellValue('J' . $i, $this->convertUTF8($data[$i - 2]['express_sn']));
            $PHPExcel->getActiveSheet()->setCellValue('K' . $i, $this->convertUTF8($data[$i - 2]['express_time']));
            $PHPExcel->getActiveSheet()->setCellValue('L' . $i, $this->convertUTF8($data[$i - 2]['addr_name']));
            $PHPExcel->getActiveSheet()->setCellValue('M' . $i, $this->convertUTF8($data[$i - 2]['addr_tele']));
            $PHPExcel->getActiveSheet()->setCellValue('N' . $i, $this->convertUTF8($data[$i - 2]['addr']));
			$PHPExcel->getActiveSheet()->setCellValue('O' . $i, $this->convertUTF8($data[$i - 2]['lottery']));
            $PHPExcel->getActiveSheet()->setCellValue('P' . $i, $this->convertUTF8($data[$i - 2]['lottery_no']));
            $PHPExcel->getActiveSheet()->setCellValue('Q' . $i, $this->convertUTF8($data[$i - 2]['lottery_expect']."\t"));
            $PHPExcel->getActiveSheet()->setCellValue('R' . $i, $this->convertUTF8($data[$i - 2]['lottery_time']));
            $PHPExcel->getActiveSheet()->setCellValue('S' . $i, $this->convertUTF8($data[$i - 2]['item_id']));
            $PHPExcel->getActiveSheet()->setCellValue('T' . $i, $this->convertUTF8($data[$i - 2]['item_title']));
            $PHPExcel->getActiveSheet()->setCellValue('U' . $i, $this->convertUTF8($data[$i - 2]['item_num']));
            $PHPExcel->getActiveSheet()->setCellValue('V' . $i, $this->convertUTF8($data[$i - 2]['item_skus']));
        }
        $xlsWriter = new PHPExcel_Writer_Excel2007($PHPExcel);
        //ob_start(); ob_flush();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
        header('Content-Type:application/force-download');
        header('Content-Type:application/vnd.ms-execl');
        header('Content-Type:application/octet-stream');
        header('Content-Type:application/download');
        header('Content-Disposition:attachment;filename="' . $name . '"');
        header('Content-Transfer-Encoding:binary');
        $xlsWriter->save('php://output');
    }


	/*
	 * $name 文件名
	 * $data 数据 二维数组
	 */
	public function echoKanFile($name = '',$data){
		if(empty($data)) return false;
		//phpexcel
		require('PHPExcel.php');
		$PHPExcel = new PHPExcel();
		$count = count($data);

		$PHPExcel->getActiveSheet()->setCellValue('A1','活动标题名称');
		$PHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
		$PHPExcel->getActiveSheet()->setCellValue('B1','ID名称（微信或者APP昵称）');
		$PHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
		$PHPExcel->getActiveSheet()->setCellValue('C1','姓名');
		$PHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
		$PHPExcel->getActiveSheet()->setCellValue('D1','身份证号');
		$PHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
		$PHPExcel->getActiveSheet()->setCellValue('E1','联系电话');
		$PHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$PHPExcel->getActiveSheet()->setCellValue('F1','详细地址');
		$PHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(50);
		$PHPExcel->getActiveSheet()->setCellValue('G1','参与砍价开始时间');
		$PHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
		$PHPExcel->getActiveSheet()->setCellValue('H1','参与砍价结束时间');
		$PHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
		$PHPExcel->getActiveSheet()->setCellValue('I1','性别（微信或者APP）');
		$PHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(25);
		$PHPExcel->getActiveSheet()->setCellValue('J1','年龄（微信或者APP）');
		$PHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(25);
		$PHPExcel->getActiveSheet()->setCellValue('K1','地理位置（获取微信）');
		$PHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(25);

		for ($i = 2; $i <= $count+1; $i++) {
			$PHPExcel->getActiveSheet()->setCellValue('A' . $i, $this->convertUTF8($data[$i-2]['kan_title']));
			$PHPExcel->getActiveSheet()->setCellValue('B' . $i, $this->convertUTF8($data[$i-2]['uname']));
			$PHPExcel->getActiveSheet()->setCellValue('C' . $i, $this->convertUTF8($data[$i-2]['realname']));
			$PHPExcel->getActiveSheet()->setCellValue('D' . $i, $this->convertUTF8($data[$i-2]['cardid']));
			$PHPExcel->getActiveSheet()->setCellValue('E' . $i, $this->convertUTF8($data[$i-2]['tele']));
			$PHPExcel->getActiveSheet()->setCellValue('F' . $i, $this->convertUTF8($data[$i-2]['address']));
			$PHPExcel->getActiveSheet()->setCellValue('G' . $i, $this->convertUTF8($data[$i-2]['add_time']));
			$PHPExcel->getActiveSheet()->setCellValue('H' . $i, $this->convertUTF8($data[$i-2]['last_time']));
			$PHPExcel->getActiveSheet()->setCellValue('I' . $i, $this->convertUTF8($data[$i-2]['sex']));
			$PHPExcel->getActiveSheet()->setCellValue('J' . $i, $this->convertUTF8($data[$i-2]['age']));
			$PHPExcel->getActiveSheet()->setCellValue('K' . $i, $this->convertUTF8($data[$i-2]['city']));
		}
		$xlsWriter = new PHPExcel_Writer_Excel2007($PHPExcel); 
		//ob_start(); ob_flush(); 
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
		header('Content-Type:application/force-download');
		header('Content-Type:application/vnd.ms-execl');
		header('Content-Type:application/octet-stream');
		header('Content-Type:application/download');
		header('Content-Disposition:attachment;filename="'.$name.'"');
		header('Content-Transfer-Encoding:binary');
		$xlsWriter->save('php://output');
	}

	/*
     * $name 文件名
     * $data 数据 二维数组
     */
    public function echoUserOrderFile($name = '', $data)
    {
        if (empty($data)) return false;
        //phpexcel
        require('PHPExcel.php');
        $PHPExcel = new PHPExcel();
        $count    = count($data);

        $PHPExcel->getActiveSheet()->setCellValue('A1', '用户名称');
        $PHPExcel->getActiveSheet()->setCellValue('B1', '用户手机');
        $PHPExcel->getActiveSheet()->setCellValue('C1', '商品信息');
        $PHPExcel->getActiveSheet()->setCellValue('D1', '商品规格');
        $PHPExcel->getActiveSheet()->setCellValue('E1', '数量');
        $PHPExcel->getActiveSheet()->setCellValue('F1', '下单时间');
        $PHPExcel->getActiveSheet()->setCellValue('G1', '订单金额');
        $PHPExcel->getActiveSheet()->setCellValue('H1', '升级状态');
        $PHPExcel->getActiveSheet()->setCellValue('I1', '退款/提货');

        for ($i = 2; $i <= $count + 1; $i++) {

			$PHPExcel->getActiveSheet()->setCellValue('A' . $i, $this->convertUTF8($data[$i - 2]['username']));
			$PHPExcel->getActiveSheet()->setCellValue('B' . $i, $this->convertUTF8($data[$i - 2]['tele']."\t"));
			$PHPExcel->getActiveSheet()->setCellValue('C' . $i, $this->convertUTF8($data[$i - 2]['title']."\t"));
			$PHPExcel->getActiveSheet()->setCellValue('D' . $i, $this->convertUTF8($data[$i - 2]['skus']."\t"));
			$PHPExcel->getActiveSheet()->setCellValue('E' . $i, $this->convertUTF8($data[$i - 2]['nums']."\t"));
            $PHPExcel->getActiveSheet()->setCellValue('F' . $i, $this->convertUTF8($data[$i - 2]['add_time']."\t"));
            $PHPExcel->getActiveSheet()->setCellValue('G' . $i, $this->convertUTF8($data[$i - 2]['prices']));
            $PHPExcel->getActiveSheet()->setCellValue('H' . $i, $this->convertUTF8($data[$i - 2]['lottery']));
            $PHPExcel->getActiveSheet()->setCellValue('I' . $i, $this->convertUTF8($data[$i - 2]['status']));
        }
        $xlsWriter = new PHPExcel_Writer_Excel2007($PHPExcel);
        //ob_start(); ob_flush();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
        header('Content-Type:application/force-download');
        header('Content-Type:application/vnd.ms-execl');
        header('Content-Type:application/octet-stream');
        header('Content-Type:application/download');
        header('Content-Disposition:attachment;filename="' . $name . '"');
        header('Content-Transfer-Encoding:binary');
        $xlsWriter->save('php://output');
    }


    public function echoUserPriceFile($name = '', $data)
	{
        if (empty($data)) return false;
        //phpexcel
        require('PHPExcel.php');
        $PHPExcel = new PHPExcel();
        $count    = count($data);

        $PHPExcel->getActiveSheet()->setCellValue('A1', '用户');
        $PHPExcel->getActiveSheet()->setCellValue('B1', '手机');
        $PHPExcel->getActiveSheet()->setCellValue('C1', '变动金额');
        $PHPExcel->getActiveSheet()->setCellValue('D1', '类型');
        $PHPExcel->getActiveSheet()->setCellValue('E1', '时间');

        for ($i = 2; $i <= $count + 1; $i++) {

			$PHPExcel->getActiveSheet()->setCellValue('A' . $i, $this->convertUTF8($data[$i - 2]['uname']));
			$PHPExcel->getActiveSheet()->setCellValue('B' . $i, $this->convertUTF8($data[$i - 2]['tele']."\t"));
			$PHPExcel->getActiveSheet()->setCellValue('C' . $i, $this->convertUTF8($data[$i - 2]['price']."\t"));
			$PHPExcel->getActiveSheet()->setCellValue('D' . $i, $this->convertUTF8($data[$i - 2]['type']."\t"));
            $PHPExcel->getActiveSheet()->setCellValue('E' . $i, $this->convertUTF8($data[$i - 2]['add_time']."\t"));
        }
        $xlsWriter = new PHPExcel_Writer_Excel2007($PHPExcel);
        //ob_start(); ob_flush();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control:must-revalidate, post-check=0, pre-check=0');
        header('Content-Type:application/force-download');
        header('Content-Type:application/vnd.ms-execl');
        header('Content-Type:application/octet-stream');
        header('Content-Type:application/download');
        header('Content-Disposition:attachment;filename="' . $name . '"');
        header('Content-Transfer-Encoding:binary');
        $xlsWriter->save('php://output');
    }


	public function convertUTF8($str)
	{
	   //if(empty($str)) return '';
	   //return  iconv('gb2312', 'utf-8', $str);
	   return $str;
	}
}
