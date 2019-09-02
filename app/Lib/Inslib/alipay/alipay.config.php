<?php
return array(
    'partner'             => '2088121541113377',
    'seller_id'           => '1803551889@qq.com',
    'private_key_path'    => LIB_PATH . 'Inslib/alipay/key/rsa_private_key.pem',
    'ali_public_key_path' => LIB_PATH . 'Inslib/alipay/key/rsa_public_key.pem',
    'sign_type'           => strtoupper('RSA'),
    'input_charset'       => strtolower('utf-8'),
    'cacert'              => LIB_PATH . 'Inslib/alipay/key/alipay_public_key.pem',
    'transport'           => 'http',
);