<?php

class publicAction extends baseAction
{
    public function snsapiBaseResponse()
    {
        $config = C('WAP_WX_CONFIG');
        // 用户同意授权后,页面将跳转至 redirect_uri/?code=CODE&state=STATE
        // code说明 ： code作为换取access_token的票据，每次用户授权带上的code将不一样，code只能使用一次，5分钟未被使用自动过期
        // 第二步：通过code换取网页授权access_token
        // 获取code后，请求以下链接获取access_token：  https://api.weixin.qq.com/sns/oauth2/access_token
        // ?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
        $code   = $this->_get('code', 'trim');

        $redirect_uri = $this->_get('re_uri', 'trim');

        $url    = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $params = array(
            'appid'      => $config['app_id'],
            'secret'     => $config['app_secret'],
            'code'       => $code,
            'grant_type' => 'authorization_code'
        );
        $result = json_decode(Http::get($url, $params), true);
        // 第三步：刷新access_token（如果需要）
        if (empty($result['openid'])) {
            $this->error("无法获取到用户openid!");
        }
        // 第四步：拉取用户信息(需scope为 snsapi_userinfo)
        //  如果网页授权作用域为snsapi_userinfo，则此时开发者可以通过access_token和openid拉取用户信息了
        $url    = 'https://api.weixin.qq.com/sns/userinfo';
        $params = array(
            'access_token' => $result['access_token'],
            'openid'       => $result['openid'],
            'lang'         => 'zh_CN',
        );
        $result2 = json_decode(Http::get($url, $params), true);
        // 错误时微信会返回JSON数据包如下（示例为openid无效）:
        // {"errcode":40003,"errmsg":" invalid openid "}
        if ($result2['errcode']) {              // 若errcode存在且为：40003，则说明调用错误
            $this->error($result2['errmsg']);
        }
        // 存储拉取到的用户信息，有"openid":" OPENID",（用户的唯一标识）
        // " nickname": NICKNAME,（用户昵称）
        // "sex":"1",（用户的性别，值为1时是男性，值为2时是女性，值为0时是未知）
        //  "province":"PROVINCE"  (用户个人资料填写的省份)
        // "city":"CITY", (普通用户个人资料填写的城市)
        //  "country":"COUNTRY", (国家，如中国为CN)
        //  "headimgurl": "http://wx.qlogo.cn/mmopen/ (用户头像,最后一个数值代表正方形头像大小,用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。)
        //  g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
        // "privilege":[ "PRIVILEGE1" "PRIVILEGE2"], (用户特权信息，json 数组，如微信沃卡用户为（chinaunicom）)
        // "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL" (只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。)
        session('wx_user', $result2);
        session('openid', $result2['unionid']);
        redirect($redirect_uri);
    }

    public function captcha()
    {
        Image::buildImageVerify(4, 1, 'gif', '50', '30', 'captcha');
    }
}