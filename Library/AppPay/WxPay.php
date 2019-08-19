<?php
class WxPay{
    protected $config;
    public function __construct($status=0,$setting){
        //配置微信参数
        if(empty($setting)){
        	return;
        }
        if($status==1){
        	$pay = $setting;
			$this->config['mch_id']=$pay['mch_id'];
			$this->config['mch_key']=$pay['mch_key'];
			$this->config['appid']=$pay['appid'];
        }elseif($status==3){//代表小程序
			$xcx_setting=$setting;
        	$this->config = array(
	            'appid'=>$xcx_setting['appid'],
	            'appsecret'=>$xcx_setting['secret'],
	            'mch_id'=>$xcx_setting['mch_id'],
	            'mch_key'=>$xcx_setting['mch_key']
	        );
        }
        else{ //支付宝
			$setting_app=$setting;
        	$this->config['mch_id']=$setting_app['wei']['app_wechat_merchid'];
			$this->config['mch_key']=$setting_app['wei']['app_wechat_apikey'];
			$this->config['appid']=$setting_app['wei']['app_wechat_appid'];
	        /*$this->config = array(
	           'appid'=>'wx247414c123515757',
	            'appsecret'=>'70cbc2f9317201d1faef026612ed2661',
	            'mch_id'=>'1500141152',
	            'mch_key'=>'34w3gkwooqmyuorrldymwkkmavtjdhh0'
	        );
			*/
		}
		
    }

    /**
     * 记录错误
     * @param $msg
     * @param $data
     */
    public function Log($msg, $data)
    {
		$path='http'.$_SERVER['HTTP_HOST']."/error/payment/log/";
        if (!is_dir($path)) mkdir($path,0777,true);
        $txt = "========================================================\r\n";
        $txt .= "时间：" . date('Y-m-d H:i:s', time()) . "\r\n";
        $txt .= "执行：调用 " . $msg ."\r\n";
        foreach($data as $k=>$v){
            $txt .= $k."=>".$v."\r\n";
        }
        $txt .= "========================================================\r\n";
        $txt .= "\r\n";
        error_log($txt, 3, $path .'WXPAY' . date('Y-m-d') . ".log");
    }

    /**
     * 微信查询订单
     * @param $order
     * @return bool|\mix|mixed|null|string
     */
    public function weixin_select_order($order_sn){
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $data = array(
            'appid'=>$this->config['appid'],
            'mch_id'=>$this->config['mch_id'],
            'out_trade_no'=>$order_sn,
            'nonce_str'=>$this->createNoncestr(),
        );
		message1('data:'.json_encode($data));
        $data['sign'] = self::getSign($data,$this->config['mch_key']);
        $result = $this->postXmlCurl($this->arrayToXml($data),$url);
        return $this->xmlToArray($result);
    }
    /**
     * 微信回调方法
     */
    public function weixin(){

        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $buyid = $postObj->attach;
        // 商户订单号
        $out_trade_no = (string)$postObj->out_trade_no;
        // 微信支付订单号
        $trade_no = (string)$postObj->transaction_id;
        // 查询订单
        $res = $this->weixin_select_order($out_trade_no);
		message1(json_encode($postObj));
		message1('333');
		message1('res:'.json_encode($res));
        if($res['result_code']=='SUCCESS'){
            if($res['trade_state']=='SUCCESS'){
            	if($res['mch_id']==$this->config['mch_id']){
					$data['ordersn']=$res['out_trade_no'];
					$data['time_end']=$res['time_end'];
					$data['transaction_id']=$res['transaction_id'];
					$data['total_fee']=$res['total_fee'];
					message1($data);
					return $data;
				}
				message('asdfasdfsadfasdfasd');
            }
        }
       return false;
    }

    /**
     * 微信发起支付
     * @param $order
     * @param string $trade_type
     * @return bool|\mix|mixed|null|string
     */
    public function weixin_pay($order=null,$trade_type='APP'){
    
		 $url="https://api.mch.weixin.qq.com/pay/unifiedorder";
        if(empty($trade_type)){
      		return false;
        }
        if(empty($order)){
        	return false;
        }
	
        $data = array(
            'appid'=>$this->config['appid'],//应用id
            'mch_id'=>$this->config['mch_id'],//商户id
            'device_info'=>'WEB',
            'nonce_str'=>$this->createNoncestr(),//随机字符串
            'body'=>$order['subject'],
            'out_trade_no'=>$order['ordersn'],
        //    'total_fee'=>0.01*100,
        	 'total_fee'=>$order['total_amount']*100, //订单金额
  			'spbill_create_ip'=>$_SERVER['REMOTE_ADDR'],
            'notify_url'=>'http://'.$_SERVER['HTTP_HOST'].'/weixin_api.php',  //处理结果返回连接
            'trade_type'=>$trade_type,
            'limit_pay'=>'no_credit',
        );
		
		if($trade_type=='JSAPI'){
			$data['openid']=$order['openid'];
		}
        $data['sign'] = self::getSign($data,$this->config['mch_key']);
		$xml_data=$this->arrayToXml($data);
        $result = $this->postXmlCurl($xml_data,$url);
	
        $pay = $this->xmlToArray($result);
		$this->Log('微信支付',$pay);
        if(!isset($pay['prepay_id']) || empty($pay['prepay_id'])){
        	$results['code']=isset($pay['result_code'])?$pay['result_code']:$pay['return_code'];
			$results['msg']=isset($pay['err_code_des'])?$pay['err_code_des']:$pay['return_msg'];
			$results['err_code']=isset($pay['err_code'])?$pay['err_code']:'';
			$results['status']=-1;
  			return $results;
        }
		
		
        if($trade_type=='APP'){
            $paySign['appid'] = $this->config['appid'];
            $paySign['partnerid'] = $this->config['mch_id'];
            $paySign['prepayid'] = $pay['prepay_id'];
            $paySign['package']="Sign=WXPay";
            $paySign['noncestr'] = $this->createNoncestr();
            $paySign['timestamp'] = time();
			
            $paySign1['pay_sign'] = $sign = self::getSign($paySign,$this->config['mch_key']);
		    $paySign1['app_id'] =$paySign['appid'];
            $paySign1['partner_id'] =$paySign['partnerid'];
            $paySign1['prepay_id'] =$paySign['prepayid'];
            $paySign1['package']=$paySign['package'];
            $paySign1['nonce_str'] =$paySign['noncestr'];
            $paySign1['timestamp']=$paySign['timestamp'];
            unset($paySign1['package']);
            $paySign1['package']="Sign=WXPay";
			$paySign1['result_code']=0;
          //  unset($paySign['package']);
           // $paySign['package']="Sign=WXPay";
			return $paySign1;
        }elseif($trade_type=='JSAPI'){
        	$paySign['appId'] = $this->config['appid'];
            $paySign['nonceStr'] = $this->createNoncestr();
            $paySign['timeStamp'] = time();
            $paySign['package'] = 'prepay_id=' . $pay['prepay_id'];
            $paySign['signType'] = 'MD5';
      	
            $paySign1['sign'] = $sign = $this->getSign($paySign, $this->config['mch_key']);
            $paySign1['appid'] = $paySign['appId'];
            $paySign1['package'] = $paySign['package'];
            $paySign1['nonceStr'] = $paySign['nonceStr'];
            $paySign1['timeStamp'] = $paySign['timeStamp'];
            $paySign1['signType'] = 'MD5';
		//	var_dump($paySign1);exit;
			return $paySign1;
        }elseif($trade_type=='NATIVE'){
        	$paySign['appId'] = $this->config['appid'];
            $paySign['nonceStr'] = $this->createNoncestr();
            $paySign['timeStamp'] = time();
            $paySign['package'] = 'prepay_id=' . $pay['prepay_id'];
            $paySign['signType'] = 'MD5';
      	
            $paySign1['sign'] = $sign = $this->getSign($paySign, $this->config['mch_key']);
            $paySign1['appid'] = $paySign['appId'];
            $paySign1['package'] = $paySign['package'];
            $paySign1['nonceStr'] = $paySign['nonceStr'];
            $paySign1['timeStamp'] = $paySign['timeStamp'];
            $paySign1['signType'] = 'MD5';
			$paySign1['code_url']=$pay['code_url'];
			
        	return $paySign1;
        }else{
 			return false;
        }

    }
/*
	public function get_client_ip(){
		if($_SERVER['REMOTE_ADDR'])
	}*/

    /**
     * 	作用：array转xml
     *
     */
    public function arrayToXml($arr){
        $xml = '<xml>';
        foreach ($arr as $key=>$val) {
            if (is_numeric($val)) {
                $xml=$xml."<".$key.">".$val."</".$key.">";
            }
            else{
               $xml=$xml."<".$key."><![CDATA[".$val."]]></".$key.">";
			}
        }
        $xml=$xml."</xml>";
        return $xml;
    }
    /**
     *  作用：将xml转为array
     */
    public function xmlToArray($xml){
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }


    /**
     *  作用：格式化参数，签名过程需要使用
     */
    public function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    /**
     *  作用：生成签名
     */
    public function getSign($Obj,$key='')
    {
        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        if(!empty($key)){//判断是否加入key
            $String = $String."&key=".$key;
        }
        $String = md5($String);
        $result_ = strtoupper($String);
        return $result_;
    }

    /**
     *  作用：以post方式提交xml到对应的接口url
     */
 	 public function postXmlCurl($xml,$url,$second=30){
        //初始化curl
      $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
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
        if($data)
        {   //var_dump($data);die('||');
            //curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }
    /**
     *  作用：产生随机字符串，不长于32位
     */
    public function createNoncestr( $length = 32 )
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
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
        $jsApiObj["paySign"] = self::getSign($jsApiObj,$this->config['mch_key']);
        return json_encode($jsApiObj);
    }*/


}