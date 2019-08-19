<?php


class WxPay
{
    protected $config;
    protected $notify_url;

    public function __construct()
    {
        //配置微信参数
        /* $this->config = array(
             'appid'=>'wx64f8e3212b61b207',
             'appsecret'=>'3d791bf32929d0f03d0d8a0668636dae',
             'mch_id'=>'1487039712',
             'mch_key'=>'wbhpc1ixax5sbgyjtdsxikgynlcanary'
         );*/
    }

    public function setConfig($data = array())
    {
        $this->config = array(
            'appid' => $data['appid'],
            'appsecret' => $data['appsecret'],
            'mch_id' => $data['mch_id'],
            'mch_key' => $data['mch_key']
        );
    }

    public function setNotifyUrl($notifyUrl)
    {
        $this->notify_url = $notifyUrl;
    }

    /**
     * 记录错误
     * @param $msg
     * @param $data
     */
    private function Log($msg, $data)
    {
        //$path = ROOT_PATH . "/log/wxpay/";
        /*        if (!is_dir($path)) mkdir($path);
                $txt = "========================================================\r\n";
                $txt .= "时间：" . date('Y-m-d H:i:s', time()) . "\r\n";
                $txt .= "执行：调用 " . $msg . "\r\n";
                foreach ($data as $k => $v) {
                    $txt .= $k . "=>" . $v . "\r\n";
                }
                $txt .= "========================================================\r\n";
                $txt .= "\r\n";
                error_log($txt, 3, $path . DS . date('Y-m-d') . ".log");*/
        $log = ROOT_PATH . '/log/wxpay/' . date('y-m-d') . '/wxpay.txt';
        if (!file_exists($log)) {
            mkdir(dirname($log), 0777, true);
        }
        file_put_contents($log, 'time:' . date('m-d H:i:s') . PHP_EOL . "flag:" . json_encode($data) . PHP_EOL, FILE_APPEND);
    }

    /**
     * 微信查询订单
     * @param $order
     * @return bool|\mix|mixed|null|string
     */
    public function weixin_select_order($order_sn)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $data = array(
            'appid' => $this->config['appid'],
            'mch_id' => $this->config['mch_id'],
            'out_trade_no' => $order_sn,
            'nonce_str' => $this->createNoncestr(),
        );
        $data['sign'] = $this->getSign($data, $this->config['mch_key']);
        $result = $this->postXmlCurl($this->arrayToXml($data), $url);
        return $this->xmlToArray($result);
    }


    /**
     * 微信回调方法--返回订单编号
     */
    public function WX_OrderNo()
    {
        $postStr = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        // 商户订单号
        $out_trade_no = (string)$postObj->out_trade_no;
        return $out_trade_no;
    }

    /**
     * 微信回调方法
     */
    public function weixin()
    {

        // $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");


        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $buyid = $postObj->attach;
        // 商户订单号
        $out_trade_no = (string)$postObj->out_trade_no;
        // 微信支付订单号
        $trade_no = (string)$postObj->transaction_id;
        // 查询订单
        $res = $this->weixin_select_order($out_trade_no);
        if ($res['result_code'] == 'SUCCESS') {
            if ($res['trade_state'] == 'SUCCESS') {
                if ($res['mch_id'] == $this->config['mch_id']) {
                    $data['ordersn'] = $res['out_trade_no'];
                    $data['time_end'] = $res['time_end'];
                    $data['transaction_id'] = $res['transaction_id'];
                    $data['total_fee'] = $res['total_fee'];
                    return $data;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return false;
    }

    /*
     * 企业付款
     *
     * */
    public function payConpany($order = array())
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        if (empty($order)) {
            return false;
        }

        $data = array(
            'mch_appid' => $this->config['appid'],//应用id
            'mchid' => $this->config['mch_id'],//商户id
            'device_info' => 'WEB',
            'nonce_str' => $this->createNoncestr(),//随机字符串
            'partner_trade_no' => $order['partner_trade_no'], //商户订单号
            'openid' => $order['openid'], //商户appid下，某用户的openid
            'check_name' => 'FORCE_CHECK', //FORCE_CHECK校验真实姓名，NO_CHECK：不校验真实姓名
            're_user_name' => $order['re_user_name'], //收款用户真实姓名。如果check_name设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => $order['total_amount'] * 100, //订单金额
            'desc' => $order['desc'], //企业付款操作说明信息。必填。
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] //调用接口的机器Ip地址
        );
        $data['sign'] = $this->getSign($data, $this->config['mch_key']);
        $xml_data = $this->arrayToXml($data);
        //$result = $this->postXmlCurl($xml_data, $url);
        $result = $this->curl_post_ssl($url, $xml_data, $second = 30, $aHeader = array());

        $pay = $this->xmlToArray($result);

        if ($pay['return_code'] == 'SUCCESS' && $pay['result_code'] == 'SUCCESS') {
            $arr = array(
                'code' => 'SUCCESS',
                'msg' => $pay['return_msg']
            );
            return $arr;
        } else {
            $arr = array(
                'code' => 'FAIL',
                'msg' => !empty($pay['err_code_des']) ? $pay['err_code_des'] : '警告：内部错误！'
            );
            return $arr;
        }

    }

    /*
     * 退款
     * */

    public function refund($order = array())
    {
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        if (empty($order)) {
            return false;
        }

        $data = array(
            'appid' => $this->config['appid'],//应用id
            'mch_id' => $this->config['mch_id'],//商户id
            'nonce_str' => $this->createNoncestr(),//随机字符串
            'transaction_id' => $order['transaction_id'], //商户订单号
            'out_trade_no' => $order['order_no'], //商户订单号
            'out_refund_no' => $order['refund_no'], //退款订单号
            'total_fee' => $order['act_money'] * 100, //订单总金额
            'refund_fee' => $order['refund_amount'] * 100, //退款金额
        );
        $data['sign'] = $this->getSign($data, $this->config['mch_key']);

        $xml_data = $this->arrayToXml($data);


        $result = $this->curl_post_ssl($url, $xml_data, $second = 30, $aHeader = array());

        $pay = $this->xmlToArray($result);

        if ($pay['return_code'] == 'SUCCESS' && $pay['result_code'] == 'SUCCESS') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 微信发起支付
     * @param $order
     * @param string $trade_type
     * @return bool|\mix|mixed|null|string
     */
    public function weixin_pay($order = null, $trade_type = 'APP')
    {

        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        if (empty($trade_type)) {
            return false;
        }
        if (empty($order)) {
            return false;
        }
        $data = array(
            'appid' => $this->config['appid'],//应用id
            'mch_id' => $this->config['mch_id'],//商户id
            'device_info' => 'WEB',
            'nonce_str' => $this->createNoncestr(),//随机字符串
            'body' => '明嘉租房',
            'out_trade_no' => $order['out_trade_no'],
            'total_fee' => $order['total_amount'] * 100, //订单金额
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => $this->notify_url,  //处理结果返回连接
            'trade_type' => $trade_type,
            'limit_pay' => 'no_credit',
        );


        $data['sign'] = $this->getSign($data, $this->config['mch_key']);
        $xml_data = $this->arrayToXml($data);
        $result = $this->postXmlCurl($xml_data, $url);
        $pay = $this->xmlToArray($result);

        $this->Log('微信支付', $pay);
        if (!isset($pay['prepay_id']) || empty($pay['prepay_id'])) {
            $result['code'] = $pay['result_code'];
            $result['msg'] = $pay['err_code_des'];
            $result['err_code'] = $pay['err_code'];
            $result['status'] = -1;
            return $result;
        }

        //
        if ($trade_type == 'APP') {
            $paySign['appid'] = $this->config['appid'];
            $paySign['partnerid'] = $this->config['mch_id'];
            $paySign['prepayid'] = $pay['prepay_id'];
            $paySign['package'] = "Sign=WXPay";
            $paySign['noncestr'] = $this->createNoncestr();
            $paySign['timestamp'] = time();
            $paySign1['sign'] = $sign = $this->getSign($paySign, $this->config['mch_key']);

            $paySign1['appid'] = $paySign['appid'];
            $paySign1['partnerid'] = $paySign['partnerid'];
            $paySign1['prepayid'] = $paySign['prepayid'];
            $paySign1['package'] = $paySign['package'];
            $paySign1['noncestr'] = $paySign['noncestr'];
            $paySign1['timestamp'] = $paySign['timestamp'];

            unset($paySign1['package']);
            $paySign1['package'] = "Sign=WXPay";
            //$paySign1['result_code']=0;

            // var_dump($paySign1);exit;
            return $paySign1;
        } else {
            return false;
        }

    }
    /*
        public function get_client_ip(){
            if($_SERVER['REMOTE_ADDR'])
        }*/

    /**
     *    作用：array转xml
     *
     */
    public function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml = $xml . "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml = $xml . "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml = $xml . "</xml>";
        return $xml;
    }

    /**
     *  作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }


    /**
     *  作用：格式化参数，签名过程需要使用
     */
    private function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *  作用：生成签名
     */
    private function getSign($Obj, $key = '')
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        if (!empty($key)) {//判断是否加入key
            $String = $String . "&key=" . $key;
        }
        $String = md5($String);
        $result_ = strtoupper($String);
        return $result_;
    }

    /**
     *  作用：以post方式提交xml到对应的接口url
     */
    private function postXmlCurl($xml, $url, $second = 30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);

        curl_close($ch);

        /*   $hander = curl_init();
             curl_setopt($hander,CURLOPT_URL,$url);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
             curl_setopt($ch, CURLOPT_POST, TRUE);
             curl_setopt($hander, CURLOPT_POSTFIELDS, $xml);
             curl_setopt($hander,CURLOPT_HEADER,0);
             $data=curl_exec($hander);
             var_dump($data);exit;
             curl_close($hander);*/
        //返回结果

        if ($data) {   //var_dump($data);die('||');
            //curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /*
请确保您的libcurl版本是否支持双向认证，版本高于7.20.1
*/

    function curl_post_ssl($url, $vars, $second = 30, $aHeader = array())
    {

        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //以下两种方式需选择一种

        //第一种方法，cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'../system/libray/payment/wxpay/cert/apiclient_cert.pem');
        curl_setopt($ch, CURLOPT_SSLCERT, DIR_SYSTEM . 'library/payment/wxpay/cert/apiclient_cert.pem');
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        //curl_setopt($ch, CURLOPT_SSLKEY, getcwd() . '../system/libray/payment/wxpay/cert/apiclient_key.pem');
        curl_setopt($ch, CURLOPT_SSLKEY, DIR_SYSTEM . 'library/payment/wxpay/cert/apiclient_key.pem');

        //第二种方式，两个文件合成一个.pem文件
        //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');

        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);

        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }


    /**
     *  作用：产生随机字符串，不长于32位
     */
    private function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * jsapi发起支付
     * @param $prepay_id
     * @return mixed|string
     */
    /* public function jsApi($prepay_id){
         $jsApiObj["appId"] = get_weixin_config('appid');
         $timeStamp = time();
         $jsApiObj["timeStamp"] = "$timeStamp";
         $jsApiObj["nonceStr"] = $this->createNoncestr();
         $jsApiObj["package"] = "prepay_id=$prepay_id";
         $jsApiObj["signType"] = "MD5";
         $jsApiObj["paySign"] = $this->getSign($jsApiObj,$this->config['mch_key']);
         return json_encode($jsApiObj);
     }*/


}