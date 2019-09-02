<?php

/* *
 * 功能：手机网站支付接口接入页
 * 版本：3.3
 */

class publicAlipayV3
{

    public $confic = array();

    public function __construct()
    {
    }

    public function setConfig()
    {
        $config = array(
            'email'   => C('ALI_WAP_CONFIG.email'), //收款商家帐号
            'partner' => C('ALI_WAP_CONFIG.partner'), //商家申请的partner
            'key'     => C('ALI_WAP_CONFIG.key'), //商家申请的key,
        );

        /******************/
        /*以下设置请勿修改 - 此配置仅支持即时到账*/
        /******************/
        //格式化配置
        $this->config = $config;
        //收款支付宝账号
        $this->config['seller_id'] = $this->config['partner'];
        //主机IP地址
        $this->config['ip'] = $_SERVER['REMOTE_ADDR'];
        //商户的私钥（后缀是.pen）文件相对路径
        $this->config['private_key_path'] = 'wap/key/rsa_private_key.pem';
        //支付宝公钥（后缀是.pen）文件相对路径
        $this->config['ali_public_key_path'] = 'wap/key/alipay_public_key.pem';
        //签名方式 不需修改
        $this->config['sign_type'] = strtolower('MD5');
        //字符编码格式 目前支持 gbk 或 utf-8
        $this->config['input_charset'] = strtolower('utf-8');
        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $this->config['cacert'] = getcwd() . '\\cacert.pem';
        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $this->config['transport'] = 'http';
    }

    /** 支付主操作函数
     * $oid 订单id(唯一)
     * $oname 订单名称
     * $price 订单金额
     * $nurl 异步通知url
     * $rurl 跳转同步页面url
     * $murl 操作中断返回地址
     * $body 订单描述[可不填]
     * $gurl 商品展示地址[可不填]
     */
    public function doPay($out_trade_no, $subject, $total_fee, $notify_url, $return_url, $murl = '', $body = '', $show_url = '')
    {
        //构造要请求的参数数组，无需改动
        //参考地址:http://t.cn/Rcrvw4X

        $parameter = array(
            "service"        => "alipay.wap.create.direct.pay.by.user",
            "partner"        => trim($this->config['partner']),
            "seller_id"      => trim($this->config['seller_id']),
            "payment_type"   => "1",
            "notify_url"     => $notify_url,
            "return_url"     => $return_url,
            "out_trade_no"   => $out_trade_no,
            "subject"        => $subject,
            "total_fee"      => $total_fee,
            "app_pay"        => "Y",
            //交易超时时间
            "it_b_pay"       => "5m",
            "_input_charset" => trim(strtolower($this->config['input_charset']))
        );
        if ($body) {
            $parameter['body'] = $body;
        }
        if ($show_url) {
            $parameter['body'] = $show_url;
        }
        //建立请求
        require_once("wap/lib/alipay_submit.class.php");
        $alipaySubmit = new AlipaySubmit($this->config);
        $html_text    = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
    }

    //异步回调
    public function doReturnWap()
    {
        require_once("wap/lib/alipay_notify.class.php");
        $alipayNotify  = new AlipayNotify($this->config);
        $verify_result = $alipayNotify->verifyNotify();
        if ($verify_result) {//验证成功
            $status = false;
            $doc    = new DOMDocument();
            if ($this->config['sign_type'] == 'MD5') {
                $doc->loadXML($_POST['notify_data']);
            }
            if ($this->config['sign_type'] == '0001') {
                $doc->loadXML($alipayNotify->decrypt($_POST['notify_data']));
            }
            if (!empty($doc->getElementsByTagName("notify")->item(0)->nodeValue)) {
                //商户订单号
                $status['out_trade_no'] = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
                //支付宝交易号
                $status['trade_no'] = $doc->getElementsByTagName("trade_no")->item(0)->nodeValue;
                //交易状态
                $status['trade_status'] = $doc->getElementsByTagName("trade_status")->item(0)->nodeValue;
                //支付金额
                $status['total_fee'] = $doc->getElementsByTagName("total_fee")->item(0)->nodeValue * 100; //线上分为单位
            }
            return $status;
        }
        else {
            echo false;
        }
    }

    //同步回调
    public function doBackWap()
    {
        if (isset($_GET['TRADE_SUCCESS']) && $_GET['TRADE_SUCCESS'] == 'TRADE_SUCCESS') {
            return $_GET;
        }
        else {
            return false;
        }
    }

    /* 退款 */
    public function doRefund($sn, $price, $notify_url, $remark = '订单退款')
    {
        $detail_data                  = $sn . '^' . $price . '^' . $remark;
        $batch_no                     = date('YmdHis') . rand(1111, 9999);
        $this->config['service']      = 'refund_fastpay_by_platform_pwd';
        $this->config['refund_date']  = date("Y-m-d H:i:s", time());
        $this->config['notify_url']   = $notify_url;
        $this->config['seller_email'] = $this->config['email'];
        $this->config['partner']      = C('ALI_WAP_CONFIG.appid');
        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"        => $this->config['service'],
            "partner"        => $this->config['partner'],
            "notify_url"     => $notify_url,
            "seller_user_id" => $this->config['seller_id'],
            "seller_email"   => $this->config['email'],
            "refund_date"    => date("Y-m-d H:i:s", time()),
            "batch_no"       => $batch_no,
            "batch_num"      => 1,
            "detail_data"    => $detail_data,
            "_input_charset" => strtolower($this->config['input_charset'])
        );
        //建立请求
        require_once("wap/lib/alipay_submit.class.php");
        $alipaySubmit = new AlipaySubmit($this->config);
        $html_text    = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
    }

}