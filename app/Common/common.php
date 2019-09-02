<?php
function msubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true)
{
    $str = trim(strip_tags($str));
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    }
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    }
    else {
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    $str_len = strlen($str);
    return $str_len > $length ? $slice . '...' : $slice;
}

function addslashes_deep($value)
{
    $value = is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    return $value;
}

function stripslashes_deep($value)
{
    if (is_array($value)) {
        $value = array_map('stripslashes_deep', $value);
    }
    elseif (is_object($value)) {
        $vars = get_object_vars($value);
        foreach ($vars as $key => $data) {
            $value->{$key} = stripslashes_deep($data);
        }
    }
    else {
        $value = stripslashes($value);
    }

    return $value;
}

function todaytime()
{
    return mktime(0, 0, 0, date('m'), date('d'), date('Y'));
}

/**
 * 友好时间
 */
function fdate($time)
{
    if (!$time) {
        return false;
    }
    $fdate = '';
    $d     = time() - intval($time);
    $ld    = $time - mktime(0, 0, 0, 0, 0, date('Y')); //年
    $md    = $time - mktime(0, 0, 0, date('m'), 0, date('Y')); //月
    $byd   = $time - mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')); //前天
    $yd    = $time - mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')); //昨天
    $dd    = $time - mktime(0, 0, 0, date('m'), date('d'), date('Y')); //今天
    $td    = $time - mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')); //明天
    $atd   = $time - mktime(0, 0, 0, date('m'), date('d') + 2, date('Y')); //后天
    if ($d == 0) {
        $fdate = '刚刚';
    }
    else {
        switch ($d) {
            case $d < $atd:
                $fdate = date('Y年m月d日', $time);
                break;
            case $d < $td:
                $fdate = '后天' . date('H:i', $time);
                break;
            case $d < 0:
                $fdate = '明天' . date('H:i', $time);
                break;
            case $d < 60:
                $fdate = $d . '秒前';
                break;
            case $d < 3600:
                $fdate = floor($d / 60) . '分钟前';
                break;
            case $d < $dd:
                $fdate = floor($d / 3600) . '小时前';
                break;
            case $d < $yd:
                $fdate = '昨天' . date('H:i', $time);
                break;
            case $d < $byd:
                $fdate = '前天' . date('H:i', $time);
                break;
            case $d < $md:
                $fdate = date('Y年m月d H:i', $time);
                break;
            case $d < $ld:
                $fdate = date('Y年m月d', $time);
                break;
            default:
                $fdate = date('Y年m月d日', $time);
                break;
        }
    }
    return $fdate;
}

/**
 * 获取用户头像
 */
function avatar($uid, $size = 100)
{
    $url_preix = __SITEROOT__;
    if (is_numeric($uid) && !empty($uid)) {
        $avatar_size = explode(',', C('ins_avatar_size'));
        $size        = in_array($size, $avatar_size) ? $size : '100';
        $avatar_dir  = avatar_dir($uid);
        $avatar_file = $avatar_dir . md5($uid) . "_{$size}.jpg";
        if (!is_file(C('ins_attach_path') . 'avatar/' . $avatar_file)) {
            $avatar_file = "default_{$size}.jpg";
        }
        return $url_preix . '/' . C('ins_attach_path') . 'avatar/' . $avatar_file;
    }
    else {
        if (is_url($uid)) {
            return $uid;
        }
        if (empty($uid)) {
            return $url_preix . '/' . C('ins_attach_path') . "avatar/default_{$size}.jpg";
        }
        else {
            $attach_path  = strstr($uid, 'static/') != false ? $uid : "data/upload/assets/" . $uid;
            $attach_path2 = strstr($uid, 'static/') != false ? $uid : "data/upload/avatar/temp/" . $uid;
            if (file_exists('./' . $attach_path)) {
                return $url_preix . '/' . $attach_path;
            }
            else if (file_exists('./' . $attach_path2)) {
                return $url_preix . '/' . $attach_path2;
            }
            else {
                return $url_preix . '/' . C('ins_attach_path') . "avatar/default_{$size}.jpg";
            }
        }
    }
}

function avatar_dir($uid)
{
    $uid  = abs(intval($uid));
    $suid = sprintf("%09d", $uid);
    $dir1 = substr($suid, 0, 3);
    $dir2 = substr($suid, 3, 2);
    $dir3 = substr($suid, 5, 2);
    return $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
}


function attach($attach, $type = 'assets', $full_url = false, $tb = '')
{
    $attach = trim($attach);
    if (is_url($attach)) {
        return $attach;
    }
    if (is_bool($type)) {
        $full_url = $type;
        $type     = 'assets';
    }
    $attach    = ltrim($attach, '/');
    $url_preix = __SITEROOT__;
    if ($full_url) {
        $url_preix = get_siteroot();
    }
    $attach_path = strstr($attach, 'static/') != false ? $attach : "data/upload/" . $type . '/' . $attach;

    if (!file_exists('./' . $attach_path) || empty($attach)) {
        if ($tb == 'item') {
            return $url_preix . "/data/upload/NOPIC.png";
        }
        else {
            return $url_preix . "/data/upload/nopic3.png";
        }
    }
    else {
        return $url_preix . '/' . $attach_path;
    }
}

/**
 * 获取缩略图文件名
 * @param string $img 原图文件名
 * @param string $thumb 前/后缀名, 如 "thumb_" , "_thumb" ;'_b , _m , _s' 分别是 大图、中图、小图
 * @param string $type 默认0为获取后缀名图片，1为获取前缀名图片(注意前缀名图片只能是单纯的图片文件名)
 */
function get_thumb($img, $thumb = '_thumb', $type = '0')
{
    if (empty($img)) {
        //原图不存在,直接返回空
        return '';
    }
    if (false === strpos($img, 'http://')) {
        //本地附件
        $img_array = explode('.', $img); //操作最后2个参数，导数第一个文件类型，导数第二个文件名
        $ext       = array_pop($img_array); //弹出数组中的文件类型
        if ($type == 1) {
            $filename = array_pop($img_array); //弹出少了文件类型数组中的文件名)
            //前缀名图片
            $thumbname = str_replace($filename . '.', $thumb . '.' . $filename, $img);
        }
        else {
            //后缀名图片
            $thumbname = str_replace('.' . $ext, $thumb . '.' . $ext, $img);
        }
    }
    else {
        //远程附件
        if (false !== strpos($img, 'taobaocdn.com') || false !== strpos($img, 'taobao.com')) {
            //判断如果是淘宝图片，按  _s _m _b 设置替换成相应缩略图
            switch ($thumb) {
                case '_s':
                    $thumbname = $img . '_100x100.jpg';
                    break;
                case '_m':
                    $thumbname = $img . '_210x1000.jpg';
                    break;
                case '_b':
                    $thumbname = $img . '_480x480.jpg';
                    break;
            }
        }
        else {
            $thumbname = $img;
        }
    }
    return $thumbname;
}

/**
 * 解析 URL，返回其组成部分，判断 $res['scheme'] 不为空
 */
function is_url($str)
{
    $res = parse_url($str);
    return !empty($res['scheme']);
}

/**
 * 对象转换成数组
 */
function object_to_array($obj)
{
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    $arr  = array();
    foreach ($_arr as $key => $val) {
        $val       = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
        $arr[$key] = $val;
    }
    return $arr;
}

function html_select($name, $list, $id = -1)
{
    if ($id == null) {
        $id = -1;
    }
    $html = "<select name='$name' id='$name'>";
    $html .= "<option value='-1'>请选择...</option>";
    foreach ($list as $key => $val) {
        $html .= "<option value='$key'";
        if ($key == $id) {
            $html .= " selected='selected'";
        }
        $html .= ">$val</option>";
    }
    $html .= "</select>";
    return $html;
}

function html_radio($name, $list, $id = -1)
{
    $html = "";
    if (is_array($list)) {
        foreach ($list as $key => $val) {
            $html .= "<span class='radio_item'><input type='radio' name='$name' value='$key'";
            if ($key == $id) {
                $html .= " checked='checked'";
            }
            $html .= "/>$val</span>";
        }
    }
    else {
        $html .= "<script type='text/javascript'>\$(function(){\$(\"input[name='$name'][value='$list']\").attr('checked','checked');});</script>";
    }

    return $html;
}

/**
 * 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000;

    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;

    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;

    $calcLongitude      = $lng2 - $lng1;
    $calcLatitude       = $lat2 - $lat1;
    $stepOne            = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo            = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;

    return round($calculatedDistance);
}

/*
 * 获取一个点一定距离的经纬度范围
 */
function getAround($lat, $lng, $raidus)
{
    $PI = 3.14159265;

    $latitude  = $lat;
    $longitude = $lng;

    $degree     = (24901 * 1609) / 360.0;
    $raidusMile = $raidus;

    $dpmLat    = 1 / $degree;
    $radiusLat = $dpmLat * $raidusMile;
    $minlat    = $latitude - $radiusLat;
    $maxlat    = $latitude + $radiusLat;
    if ($minlat > $maxlat) {
        $minLat = $maxlat;
        $maxLat = $minlat;
    }
    else {
        $minLat = $minlat;
        $maxLat = $maxlat;
    }

    $mpdLng    = $degree * cos($latitude * ($PI / 180));
    $dpmLng    = 1 / $mpdLng;
    $radiusLng = $dpmLng * $raidusMile;
    $minlng    = $longitude - $radiusLng;
    $maxlng    = $longitude + $radiusLng;
    if ($minlng > $maxlng) {
        $minLng = $maxlng;
        $maxLng = $minlng;
    }
    else {
        $minLng = $minlng;
        $maxLng = $maxlng;
    }
    return compact('minLat', 'maxLat', 'minLng', 'maxLng');
}

function strEncrypt($data, $key)
{
    $char = '';
    $str  = '';

    $key = md5($key);
    $x   = 0;
    $len = strlen($data);
    $l   = strlen($key);

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= $key{$x};
        $x++;
    }
    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    return base64_encode($str);
}
// 数据（字符串）解密
function strDecrypt($data, $key)
{
    $char = '';
    $str  = '';

    $key  = md5($key);
    $x    = 0;
    $data = base64_decode($data); // base64_decode() 将 BASE64 编码字符串解码。
    $len  = strlen($data);
    $l    = strlen($key);

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }
        else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}

function intMin($data, $num)
{
    return ceil($data) < $num ? $num : ceil($data);
}

function intMax($data, $num)
{
    return ceil($data) > $num ? $num : ceil($data);
}

function array_connect($arr1, $arr2)
{
    $result = $arr1;
    foreach ($arr2 as $key => $val) {
        $result[] = $val;
    }
    return $result;
}

function _json_decode($val, $assoc = true)
{
    if (is_string($val)) {
        return json_decode($val, $assoc);
    }
    return $val;
}

function _json_encode($val)
{
    if (!is_string($val)) {
        if (PHP_VERSION > '5.4.0') {
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        else {
            return json_encode($val);
        }
    }
    return $val;
}

function var_default($val, $default = true)
{
    return empty($val) ? $default : $val;
}

function parse_editor_info($str)
{
    include_once(APP_PATH . "Lib/Inslib/simple_html_dom.php");
    $html = str_get_html($str);
    if ($html) {
        $img_list = $html->find('img');
        foreach ($img_list as $k => $v) {
            $src = $html->find('img', $k)->src;
            if (strstr($src, 'data/upload/')) {
                $html->find('img', $k)->src = __SITEROOT__ . '/' . substr($src, strpos($src, "data/upload/"));
            }
        }
        return $html->innertext;
    }
    else {
        return $str;
    }
}

function _base64_decode($str)
{
    $str = str_replace(" ", "+", $str);
    return base64_decode($str);
}
// 判断是否来自微信浏览器访问
function is_weixin_browser()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], "MicroMessenger") !== false;
}

function getWxPaySignStr($package, $sign_key, $forceLower = true)
{
    if ($forceLower) {
        $result = array();
        foreach ($package as $key => $val) {
            $result[strtolower($key)] = $val;
        }
        $package = $result;
    }

    ksort($package);
    $package_arr = array();
    foreach ($package as $key => $val) {
        $package_arr[] = $key . '=' . $val;
    }
    $package_arr[]  = "key=$sign_key";
    $stringSingTemp = implode('&', $package_arr);
    return $stringSingTemp;
}

function getWxPaySign($package, $sign_key, $forceLower = true)
{
    $stringSingTemp = getWxPaySignStr($package, $sign_key, $forceLower);
    return strtoupper(md5($stringSingTemp));
}

function httpPost($url, $data, $is_build = TRUE, array $options = array(), $errorReturn = FALSE)
{
    $data = ($is_build === TRUE) ? http_build_query($data) : $data;
    /*
     *微信不支持json_encode对中文的编码!!!
     * */
    $data     = preg_replace('/\\\u([0-9a-f]{4})/ie', "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $data);
    $ch       = curl_init();
    $defaults = array(
        CURLOPT_HEADER            => FALSE,
        CURLOPT_POST              => TRUE,
        CURLOPT_RETURNTRANSFER    => TRUE,
        CURLOPT_SSL_VERIFYPEER    => FALSE,
        CURLOPT_VERBOSE           => false,
        CURLOPT_FRESH_CONNECT     => FALSE,
        CURLOPT_URL               => $url,
        CURLOPT_POSTFIELDS        => $data,
        CURLOPT_TIMEOUT           => 15,
        CURLOPT_CONNECTTIMEOUT    => 0,
        CURLOPT_DNS_CACHE_TIMEOUT => 120,
        CURLOPT_SSLVERSION        => 1,
        CURLOPT_SSL_VERIFYPEER    => FALSE,
    );
//    $options = array_merge($defaults, $options);

    curl_setopt_array($ch, $defaults);

    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    if ($errno) {
        return $errorReturn === FALSE ? "Error: #" . curl_error($ch) : $errorReturn;
    }
    curl_close($ch);
    return $response;
}

function xmlToArray($xml, $options = array())
{
    if (is_string($xml)) {
        $xml = simplexml_load_string($xml);
    }
    $defaults       = array(
        'namespaceSeparator' => ':',//you may want this to be something other than a colon
        'attributePrefix'    => '@',   //to distinguish between attributes and nodes with the same name
        'alwaysArray'        => array(),   //array of xml tag names which should always become arrays
        'autoArray'          => true,        //only create arrays for tags which appear more than once
        'textContent'        => '$',       //key used for the text content of elements
        'autoText'           => true,         //skip textContent key if node has no attributes or child nodes
        'keySearch'          => false,       //optional search and replace on tag and attribute names
        'keyReplace'         => false       //replace values for above search values (as passed to str_replace())
    );
    $options        = array_merge($defaults, $options);
    $namespaces     = $xml->getDocNamespaces();
    $namespaces[''] = null; //add base (empty) namespace

    //get attributes from all namespaces
    $attributesArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
            //replace characters in attribute name
            if ($options['keySearch']) {
                $attributeName =
                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
            }
            $attributeKey                   = $options['attributePrefix']
                . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                . $attributeName;
            $attributesArray[$attributeKey] = (string)$attribute;
        }
    }

    //get child nodes from all namespaces
    $tagsArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->children($namespace) as $childXml) {
            //recurse into child nodes
            $childArray = xmlToArray($childXml, $options);
            list($childTagName, $childProperties) = each($childArray);

            //replace characters in tag name
            if ($options['keySearch']) {
                $childTagName =
                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
            }
            //add namespace prefix, if any
            if ($prefix) {
                $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
            }

            if (!isset($tagsArray[$childTagName])) {
                //only entry with this key
                //test if tags of this type should always be arrays, no matter the element count
                $tagsArray[$childTagName] =
                    in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                        ? array($childProperties) : $childProperties;
            }
            elseif (
                is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                === range(0, count($tagsArray[$childTagName]) - 1)
            ) {
                //key already exists and is integer indexed array
                $tagsArray[$childTagName][] = $childProperties;
            }
            else {
                //key exists so convert to integer indexed array with previous value in position 0
                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
            }
        }
    }

    //get text content of node
    $textContentArray = array();
    $plainText        = trim((string)$xml);
    if ($plainText !== '') {
        $textContentArray[$options['textContent']] = $plainText;
    }

    //stick it all together
    $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
        ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

    //return node as array
    return array(
        $xml->getName() => $propertiesArray
    );
}

function arrayToXml($arr)
{
    $xml = "<xml>";
    foreach ($arr AS $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
        else {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }
    $xml .= "</xml>";
    return $xml;
}

function url_add_query($arr, $replace = true)
{
    $url = sprintf("%s://%s%s", $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
    $res = parse_url($url);

    $query = array();
    parse_str($res['query'], $query);
    foreach ($arr as $key => $val) {
        if (array_key_exists($key, $query) && $replace) {
            $query[$key] = $val;
        }
        else {
            $query[$key] = $val;
        }
    }
    return sprintf("%s://%s%s?%s", $res['scheme'], $res['host'], $res['path'], http_build_query($query));
}

function returnSquarePoint($lng, $lat, $distance = 0.5)
{
    $EARTH_RADIUS = 6371;
    $dlng         = 2 * asin(sin($distance / (2 * $EARTH_RADIUS)) / cos(deg2rad($lat)));
    $dlng         = rad2deg($dlng);

    $dlat = $distance / $EARTH_RADIUS;
    $dlat = rad2deg($dlat);
    return array(
        'lng' => $dlng,
        'lat' => $dlat,
    );
}

function imageThumb($src, $cropData = array())
{
    $sizes = C('THUMB_SIZE');
    $info  = pathinfo($src);
    if ($cropData) {
        $cropFile = $info['dirname'] . '/' . $info['filename'] . "_crop." . $info['extension'];
        Image::cropByData($src, $cropFile, $cropData);
        unlink($src);
        rename($cropFile, $src);
    }
    foreach ($sizes as $key => $size) {
        $newImg = $info['dirname'] . '/' . $info['filename'] . "_$key." . $info['extension'];
        Image::thumb($src, $newImg, $size[0], $size[1]);
    }
}


/**
 * 生成随机字符串
 * $len 长度
 * $type = number/letter/all/capall 纯数字/字母/混搭/混搭字母大小写
 */
function strRand($len = 8, $type = 'capall')
{
    $number = '0123456789';
    $letter = 'abcdefghijklmnopqrstuvwxyz';
    $caps   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    switch ($type) {
        case 'capall':
            $str = $number . $letter . $caps;
            break;
        case 'number':
            $str = $number;
            break;
        case 'letter':
            $str = $letter;
            break;
        case 'ncaps':
            $str = $number . $caps;
            break;
        case 'all':
            $str = $number . $letter;
            break;
        default:
            $str = $number . $letter . $caps;
    }
    $text = '';
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, strlen($str));
        if ($n == strlen($str)) {
            $n--;
        }
        $text .= substr($str, $n, 1);
    }
    return $text;
}

//将字符串部分替换为*
function strReplaceChar($str, $num = 4, $char = "*")
{
    $n = strlen($str);
    if ($n < $num) {
        return $str;
    }
    $num    = $num * -1;
    $limit  = $n + $num;
    $c      = substr($str, $num);
    $result = '';
    for ($i = 0; $i < $limit; $i++) {
        $result .= $char;
    }
    $result = $result . $c;
    return $result;
}

/**增加开始**/
function item_type_html($info)
{
    if (isset($info['title']) && isset($info['type'])) {
        if ($info['type'] == 1) {
            return '<span style="color:blue ">[全赠]</span>';
        }
        else if ($info['type'] == 2) {
            return '<span style="color:red ">[日赠]</span>';
        }
    }
    return "";
}

function get_cate_tree($mod, $id = 0)
{
    $where = array();
    if ($id > 0) {
        $where['id'] = $id;
    }
    else {
        $where['pid'] = 0;
    }
    $list = $mod->where($where)->select();
    foreach ($list as $key => $val) {
        $list[$key]['depth'] = 0;
        $list[$key]['child'] = get_child_tree($mod, $val['id'], 0);
    }
    return $list;
}

function get_child_tree($mod, $pid, $depth = 0)
{
    $where['pid'] = $pid;
    $list         = $mod->where($where)->select();
    if ($list) {
        $depth++;
        foreach ($list as $key => $val) {
            $list[$key]['depth'] = $depth;
            $list[$key]['child'] = get_child_tree($mod, $val['id'], $depth);
        }
    }
    else {
        return false;
    }
    return $list;
}

/**增加结束**/

function current_date()
{
    return date('Y-m-d H:i:s');
}

function get_mysql_info()
{
    $res    = M()->query('SHOW VARIABLES;');
    $result = [];
    foreach ($res as $key => $val) {
        $result[$val['Variable_name']] = $val['Value'];
    }

    return $result;
}

/*
网页调用原生功能
*/
function hybrid($method, $params = [])
{
    return C('HYBRID_SCHEME') . '://' . base64_encode(_json_encode(compact('method', 'params')));
}
// APP之间的相互跳转
function APPOpenURL($title, $url, $vals = [])
{
    if (isAppWebView()) {
        return hybrid('APPOpenURL', ['title' => $title, 'url' => U($url, $vals, true, false, true)]);
    }
    else {
        return U($url, $vals, true, false, true);
    }
}

function APPYiDetail($yi_id)
{
    return hybrid('APPYiDetail', ['yi_id' => $yi_id]);
}

function APPYiAddr($yi_id){
    return hybrid('APPYiAddr', ['yi_id' => $yi_id]);
}

function APPTrendAdd($yi_id)
{
    return hybrid('APPTrendAdd', ['yi_id' => $yi_id]);
}

function APPShare($title, $content, $url, $vals, $img)
{
    return hybrid('APPShare', ['title' => $title, 'content' => $content, 'url' => U($url, $vals, true, false, true), 'img' => $img]);
}
// 判断是否是APP端访问，若是APP端用户登录，登录时请求一次token，之后用token（前提是token没有过期）调用接口
function isAppWebView()
{
    // 通过http协议客户端cookie给脚本的变量
    $token       = $_COOKIE['token'];
    $session_key = 'APP_TOKEN';
    if ($token) {
        // $token若有值，则直接赋给$session_key=APP_TOKEN
        session($session_key, $token);
    }
    else {
        $token = session($session_key);
    }
    return !empty($token);
}

function get_request_url()
{
    return 'http://' . $_SERVER['HTTP_HOST'] . ($_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT']) . $_SERVER['REQUEST_URI'];
}

function order_status($status)
{
    //0：未付款，1：已付款，2：已发货，4:退款退货，５:已收货，6:已评价,9：已取消
    switch ($status) {
        case 0:
            return '未付款';
        case 1:
            return '已付款';
        case 2:
            return '待提货';
        case 3:
            return '提货中';
        case 4:
            return '提货中';
        case 5:
            return '已完成';
        case 6:
            return '已评价';
        case 9:
            return '已取消';
        default:
            return '无效状态';
    }
}

function order_status_all($status)
{
    //0：未付款，1：已付款，2：已发货，4:已发货，５:已收货，6:已评价,9：已取消
    switch ($status) {
        case 0:
            return '未付款';
        case 1:
            return '已付款';
        case 2:
            return '待提货';
        case 3:
            return '待发货';
        case 4:
            return '已发货';
        case 5:
            return '已完成';
        case 6:
            return '已评价';
        case 9:
            return '已取消';
        default:
            return '无效状态';
    }
}

function get_current_url()
{
    return sprintf("%s://%s%s", 'http', $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
}

function safe_tele($tele)
{
    if (preg_match("/^1[34578]\d{9}$/", $tele)) {
        return substr($tele, 0, 3) . '****' . substr($tele, 7, 4);
    }
    else {
        return $tele;
    }
}

function parse_editor_img($info)
{
    $list = array(); //这里存放结果map
    $c1   = preg_match_all('/<img\s.*?>/', $info, $m1); //先取出所有img标签文本
    for ($i = 0; $i < $c1; $i++) { //对所有的img标签进行取属性
        $c2 = preg_match_all('/(\w+)\s*=\s*(?:(?:(["\'])(.*?)(?=\2))|([^\/\s]*))/', $m1[0][$i],
            $m2); //匹配出所有的属性
        for ($j = 0; $j < $c2; $j++) { //将匹配完的结果进行结构重组
            $list[$i][$m2[1][$j]] = !empty($m2[4][$j]) ? $m2[4][$j] : $m2[3][$j];
        }
    }
    $res = array();
    foreach ($list as $val) {
        if (is_url($val['src'])) {
            continue;
        }
        $res[] = ltrim($val['src'], '/');
    }
    return $res;
}

/**
 * 获取ip地址信息
 */
function ip2region($ip)
{
    require LIB_PATH . 'Inslib/ip2region/Ip2Region.class.php';
    $ip2regionObj = new Ip2Region(LIB_PATH . 'Inslib/ip2region/ip2region.db');

    $res    = $ip2regionObj->btreeSearch($ip);
    $res    = explode('|', $res['region']);
    $result = [
        'country'  => $res[0],
        'province' => $res[2],
        'city'     => $res[3],
    ];
    return $result;
}

function table($name)
{
    return C('DB_PREFIX') . $name;
}

/*计算当前时间区间(10分钟),分解批次
	[批次逻辑] 2017 04 28 001 对应是 10:00
	10:00~22:00 10分一次 
	22:01~01:55 5分一次 (此处跨日了)
	01:56~09:59 等10点期
	对应区间
	A 1~24期 00:00~01:55
	B 25~97期 10:00~22:00
	C 98~120期 22:00~00:00
	D 25期 01:55~10:00
*/
function getExpect($time){
	if(!$time) return false;
	$et = explode(' ',$time);
	//先获取订单当日0点时间戳
	$ordertimestamp1 = strtotime($time);
	$ordertimestamp2 = strtotime($et[0]);
	$t = $ordertimestamp1 - $ordertimestamp2;
	//分割时间看是在哪个区间
	if($t<6900){
		$expect = ceil($t/300); //1~24期间
	}elseif($t>6900 && $t<36000){
		$expect = 25;  //25期
	}elseif($t>36000 && $t<79200){
		$expect = ceil(($t-36000)/600)+24;  //26~97期
	}else{
		$expect = ceil(($t-79200)/300)+97;  //98~120期 
	}
	return date('Ymd',$ordertimestamp1).str_pad($expect,3,'0',STR_PAD_LEFT);
}

function getExpectTime($expect){
	if(!$expect) return false;
	$no = substr($expect,-3,3);
	$num = intval($no);
	if($num<25){
		$time = $num*300;
	}elseif($num>24&&$num<98){
		$time = (($num-24)*600)+36000;
	}elseif($num==25){
		$time = 36000;
	}else{
		$time = (($num-97)*300)+79200;
	}
	$date = substr($expect,0,strlen($expect)-3);
	$timestamp = strtotime(substr($date,0,4).'-'.substr($date,4,2).'-'.substr($date,6,2));
	return $timestamp+$time;

}
