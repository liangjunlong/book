<?php
class wechat_refundModuleSite extends yhgModuleSite{
	public $url='https://api.mch.weixin.qq.com/secapi/pay/refund';
	public $config=array(
			'appid'=>'',
			'mch_id'=>'',
			'mch_key'=>''
	);
	
	public function getorder($id,$goods_id){
		global $_W,$_GPC;
		$sql="select o.totalprice,o.transid,oo.totalprice oo_totalprice,oo.suboid,o.api,o.ordersn from ".tablename('xiantuan_order')." o 
			join ".tablename('xiantuan_order_goods')." oo on o.id=oo.orderid where o.id=".$id." and oo.id=".$goods_id;
		$data=pdo_fetch($sql);
		return $data;	
	}
	
	public function refund_all($order){
		global $_W,$_GPC;
		$url=$this->url;
		$account=$_W['account'];
		$setting = uni_setting($_W['uniacid'], array('payment', 'recharge'));
		
	//	$setting_app= unserialize(uni_setting($_W['uniacid'], array('app_payment', 'recharge'))['app_payment']);
		$sql="select app_payment from ".tablename('uni_settings')." where uniacid=5";
		$setting_app=unserialize(pdo_fetchcolumn($sql));
		
		if($order['api']==2){
			$this->config['mch_id']=$setting_app['wei']['app_wechat_merchid'];
			$this->config['mch_key']=$setting_app['wei']['app_wechat_apikey'];
			$this->config['appid']=$setting_app['wei']['app_wechat_appid'];
		}else{
			$pay = $setting['payment'];
			$this->config['mch_id']=$pay['wechat']['mchid'];
			$this->config['mch_key']=$pay['wechat']['apikey'];
			$this->config['appid']=$account['key'];
		}
		//判断是微信支付还是APP支付
		
		$data=array(
			'appid'=>$this->config['appid'],
			'mch_id'=>$this->config['mch_id'],
			'nonce_str'=>$this->createNoncestr(),
			'transaction_id'=>$order['transid'],//微信订单号
			'out_refund_no'=>$order['ordersn'],//商户退款单号
			'total_fee'=>intval($order['money']*100),//订单 金额 
			'refund_fee'=>intval($order['money']*100),//退款金额 
		);	
		$data['sign'] = $this->getSign($data,$this->config['mch_key']);
		$xml=$this->arrayToXml($data);
		
        $result = $this->postXmlCurl($xml,$url,$send=30,$order['api']);
       	$pay=$this->xmlToArray($result);	
		return $pay;
	}

	public function pay_money($params){
		$data=self::epay($params);
		return $data;
	}
	
	public function refund($order,$re,$money,$xcx=0){
		global $_W,$_GPC;
		$url=$this->url;
		$account=$_W['account'];
		$setting = uni_setting($_W['uniacid'], array('payment', 'recharge'));
		$sql="select app_payment from ".tablename('uni_settings')." where uniacid=5";
		$setting_app=unserialize(pdo_fetchcolumn($sql));
		if($order['paytype']==2){//APP
			$this->config['appid']=$account['key'];
			$this->config['mch_id']=$setting_app['wei']['app_wechat_merchid'];
			$this->config['mch_key']=$setting_app['wei']['app_wechat_apikey'];
		}elseif($order['paytype']==3){//小程序
			$sql="select * from ".tablename('yhg_xcx_setting')." where id=1";
			$xcx_setting=pdo_fetch($sql);
			$pay = $setting['payment'];
			$this->config['appid']=$xcx_setting['appid'];
			$this->config['mch_id']=$xcx_setting['mch_id'];
			$this->config['mch_key']=$xcx_setting['mch_key'];
		}else{
			$pay = $setting['payment'];
			$this->config['appid']=$account['key'];
			$this->config['mch_id']=$pay['wechat']['mchid'];
			$this->config['mch_key']=$pay['wechat']['apikey'];
		}
		//判断是微信支付还是APP支付
		
		$data=array(
			'appid'=>$this->config['appid'],
			'mch_id'=>$this->config['mch_id'],
			'nonce_str'=>$this->createNoncestr(),
			'refund_desc'=>'用户申请退款',
			'transaction_id'=>$order['transid'],//微信订单号
			'out_refund_no'=>$order['suboid'],//商户退款单号
			'total_fee'=>$order['totalprice']*100,//订单 金额 
			'refund_fee'=>$money*100,//退款金额 
		);
	//	var_dump($data);exit;
		$data['sign'] = $this->getSign($data,$this->config['mch_key']);
		$xml=$this->arrayToXml($data);
        $result = $this->postXmlCurl($xml,$url,$send=10,$order['paytype']);
		
       	$pay=$this->xmlToArray($result);
	//	var_dump($pay);exit;
		return $pay;
	}

    /**
     * 记录错误
     * @param $msg
     * @param $data
     */
    public function Log($msg, $data)
    {
		$path=ROOT_PATHA."/payment/dishi/log/";
        if (!is_dir($path)) mkdir($path);
        $txt = "========================================================\r\n";
        $txt .= "时间：" . date('Y-m-d H:i:s', time()) . "\r\n";
        $txt .= "执行：调用 " . $msg ."\r\n";
        foreach($data as $k=>$v){
            $txt .= $k."=>".$v."\r\n";
        }
        $txt .= "========================================================\r\n";
        $txt .= "\r\n";
        error_log($txt, 3, $path . DS . date('Y-m-d') . ".log");
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
   //     $data['sign'] = $this->getSign($data,$this->config['mch_key']);
  //      $result = $this->postXmlCurl($this->arrayToXml($data),$url);
  //      return $this->xmlToArray($result);
    }
	
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
 	 public function postXmlCurl($xml,$url,$second=30,$api=0){
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
     	
        if($api==2){//APP
        	$zs1=IA_ROOT."/addons/".$this->modulename."/inc/app_pay/cert_app/apiclient_cert.pem";
        	$zs2=IA_ROOT."/addons/".$this->modulename."/inc/app_pay/cert_app/apiclient_key.pem";
        }elseif($api==3){//小程序
        	$zs1=IA_ROOT."/addons/".$this->modulename."/inc/app_pay/cert_xcx/apiclient_cert.pem";
        	$zs2=IA_ROOT."/addons/".$this->modulename."/inc/app_pay/cert_xcx/apiclient_key.pem";
        }else{//公众号
        	$zs1=IA_ROOT."/addons/".$this->modulename."/inc/app_pay/cert/apiclient_cert.pem";
        	$zs2=IA_ROOT."/addons/".$this->modulename."/inc/app_pay/cert/apiclient_key.pem";
		}
	
        curl_setopt($ch,CURLOPT_SSLCERT,$zs1);
        curl_setopt($ch,CURLOPT_SSLKEY,$zs2);
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1 );
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        curl_close($ch);
       
       // var_dump($data);exit;

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
}