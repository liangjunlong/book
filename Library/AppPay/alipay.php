<?php
/**
 * AOP SDK 入口文件
 * 请不要修改这个文件，除非你知道怎样修改以及怎样恢复
 * @author wuxiao
 */
/**
 * 定义常量开始
 * 在include("AopSdk.php")之前定义这些常量，不要直接修改本文件，以利于升级覆盖
 */
/**
 * SDK工作目录
 * 存放日志，AOP缓存数据
 */
if (!defined("AOP_SDK_WORK_DIR"))
{
	define("AOP_SDK_WORK_DIR", "/tmp/");
}
/**
 * 是否处于开发模式
 * 在你自己电脑上开发程序的时候千万不要设为false，以免缓存造成你的代码修改了不生效
 * 部署到生产环境正式运营后，如果性能压力大，可以把此常量设定为false，能提高运行速度（对应的代价就是你下次升级程序时要清一下缓存）
 */
if (!defined("AOP_SDK_DEV_MODE"))
{
	define("AOP_SDK_DEV_MODE", true);
}
/**
 * 定义常量结束
 */
class alipay
{
	public $request;
	public $aop;
	public $lotus;
	public $pub="";
	public $RSA="RSA2";
	
    public function __construct($num=0) {
		$file=IA_ROOT."/addons/yhg/inc/app_pay/ali_app/AopSdk.php";
		include $file;
	   	$sql="select app_payment from ".tablename('uni_settings')." where uniacid=5";
		$setting_app=unserialize(pdo_fetchcolumn($sql));
		
		if($num==0){
		   	if(!$setting_app['ali']['app_alipay_appid']){
		   		$result['msg']='请先设置支付宝帐号公私钥在来退款';
				echo json_encode($result);exit;
		   	}
			$this->pub=trim($setting_app['ali']['app_alipay_public_key']);
			$this->appId=$setting_app['ali']['app_alipay_appid'];
			$this->alipayrsaPublicKey=trim($setting_app['ali']['app_alipay_public_key']);
			$this->rsaPrivateKey=trim($setting_app['ali']['app_alipay_private_key']);
		}else{
			if(!$setting_app['ali_xcx']['app_alipay_appid']){
		   		$result['msg']='请先设置支付宝帐号公私钥在来退款';
				echo json_encode($result);exit;
		   	}
			if(!$setting_app['ali_xcx']['app_xcx_flag']){
				$result['msg']='请先开启支付宝小程序再进行操作';
				echo json_encode($result);exit;
			}
			$this->pub=trim($setting_app['ali_xcx']['app_alipay_public_key']);
			$this->appId=$setting_app['ali_xcx']['app_alipay_appid'];
			$this->alipayrsaPublicKey=trim($setting_app['ali_xcx']['app_alipay_public_key']);
			$this->rsaPrivateKey=trim($setting_app['ali_xcx']['app_alipay_private_key']);
		}
		
		$aop = new AopClient ();
		$this->aop=$aop;
		$this->aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$this->aop->apiVersion = '1.0';
		$this->aop->postCharset='UTF-8';
		$this->aop->format='json';
        $this->aop->signType =  $this->RSA;
		$this->aop->appId=$this->appId;
		$this->aop->rsaPrivateKey=$this->rsaPrivateKey;
		$this->aop->alipayrsaPublicKey=$this->alipayrsaPublicKey;
	}

	public function get_alipay($params){
    //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
    	$aop=$this->aop;
        $request = new AlipayTradeAppPayRequest();
		
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $params1 = array(
            'body' => '订单支付',   //具体信息描叙
            'subject' =>$params['subject'],         //商品的标题/交易标题/订单标题/订单关键字等。
            'out_trade_no' => $params['ordersn'],    //商户网站唯一订单号，用pay_log里的uniontid
            'total_amount' => $params['total_amount'],         //订单总金额，单位为元，精确到小数点后两位
            'product_code' => 'QUICK_MSECURITY_PAY'
        );
        $bizcontent = json_encode($params1);
        $request->setNotifyUrl('http://'.$_SERVER['HTTP_HOST'].'/alipay_api.php');
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        $code = ($response);//就是orderString 可以直接给客户端请求，无需再做处理。
       // echo $code;exit;
        return $code;
	}

	public function alipay_qcode($shop_id,$page){
		$aop=$this->aop;
		$request = new AlipayOpenAppQrcodeCreateRequest ();
		$params1 = array(
            'query_param' => 'buy=buy',   //具体信息描叙
            'url_param' =>$page?$page:'/pages/index/index',         //商品的标题/交易标题/订单标题/订单关键字等。
            'describe' => "购票二维码",    //商户网站唯一订单号，用pay_log里的uniontid
        );
	//	var_dump($params1);exit;
        $bizcontent = json_encode($params1);
		$request->setBizContent($bizcontent);
		$result = $aop->execute ( $request); 
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
		 	return $result->alipay_open_app_qrcode_create_response->qr_code_url;
		} else {
		 	return $resultCode;
		}
	}
	public function getAuth($params,&$results){
		$aop=$this->aop;
		$request = new AlipaySystemOauthTokenRequest ();
		$request->setGrantType($params['grant_type']);
		$request->setCode($params['code']);
		$result = $aop->execute ( $request); 
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$user_id=$result->alipay_system_oauth_token_response->user_id;
		$this->site_refund_insert_logs(json_encode($result),'111111'.'_'.$goods['id']);
		if(!empty($user_id) && $user_id){
			$results['result']='1';	
			$results['msg']='成功';
			return $user_id;
		} else {
			$results['result']='-2';
			$results['msg']=$result->error_response->msg.$result->error_response->sub_msg;
			$results['code']=$resultCode;
			return -2;
		} 
	}
	
	public function site_refund_insert_logs($data,$orderid){
	    $str=$data;
		$date=date('Y-m-d',time());
        $file=IA_ROOT."/error/refund/$date/";
        if(!file_exists($file)){
            mkdir($file,0777,true);
        }
		$time=time();
        $file.= $orderid.".txt";
		$str="\r\n".'=========================='."\r\n".$str."\r\n".'=================================';
        file_put_contents($file,$str,FILE_APPEND);
		return ;
	}
	public function get_xcx_alipay($params){
		$aop=$this->aop;
        $request = new AlipayTradeCreateRequest ();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
     //  var_dump($aop);exit;
        $params1 = array(
            'body' => '订单支付',   //具体信息描叙
            'subject' =>$params['subject'],         //商品的标题/交易标题/订单标题/订单关键字等。
            'out_trade_no' => $params['ordersn'],    //商户网站唯一订单号，用pay_log里的uniontid
            'total_amount' =>floatval($params['total_amount']),         //订单总金额，单位为元，精确到小数点后两位
            'buyer_id'=>$params['buyer_id']
        );
		//var_dump($params1);exit;
        $bizcontent = json_encode($params1);
        $request->setNotifyUrl('http://'.$_SERVER['HTTP_HOST'].'/alipay_api.php');	
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
		$result = $aop->execute ( $request); 
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		
		if($resultCode==10000){
			$code['ordersn']=$result->alipay_trade_create_response->trade_no;
			$code['result']='1';
		}else{
			$code['result']='-1';
			$code['msg']=$result->$responseNode->code.$result->$responseNode->sub_msg;
		}
        return $code;
	
	}	
	public function get_alipay_mobile($params){
		$aop=$this->aop;
   //     $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhGt9taOjNL4NR3AxA/tGltkyY5QYbzawxqaTVJLnFwxcc9siMyH0EDoXQlMiIf7PokRZ04vUawNPbZt5QDQ25cA4lvSGJWjGcHnBn1vXqrNuTOvIbCyeFWbN3pV+QNMUvqV3vr+FsPoF6FMQyoGJNj6zi3xm4FBAAzl6CeCId/MpkPyQNf4DCGrHdTaJOM0zyEApQfgBe24ivofMfj+aEHDN55/glfFWn4BHgOV2Tv49ftl6nvVG6uQ1Y03wbrTxogT99zyGrlLLgBPRwUQsS8Y/qFpWO47EWHuErYJyAADUz80/aJohsELFHgaTG+h9120QfcNz5OJ8zujHPf83EQIDAQAB';
  
	    //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeWapPayRequest  ();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
     //  var_dump($aop);exit;
        $params1 = array(
            'body' => '订单支付',   //具体信息描叙
            'subject' =>$params['subject'],         //商品的标题/交易标题/订单标题/订单关键字等。
            'out_trade_no' => $params['ordersn'],    //商户网站唯一订单号，用pay_log里的uniontid
            'total_amount' => $params['total_amount'],         //订单总金额，单位为元，精确到小数点后两位
            'product_code' => 'QUICK_WAP_WAY'
        );
        $bizcontent = json_encode($params1);
		$return_url="https://".$_SERVER['HTTP_HOST']."/refund.php";
        $request->setNotifyUrl('http://'.$_SERVER['HTTP_HOST'].'/alipay_api.php');
		$request->setReturnUrl($return_url);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->pageExecute($request);
        $code = ($response);//就是orderString 可以直接给客户端请求，无需再做处理。
       // echo $code;exit;
        return $code;
	}

	public function get_alipay_pc($params){
		$aop=$this->aop;
     //   $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhGt9taOjNL4NR3AxA/tGltkyY5QYbzawxqaTVJLnFwxcc9siMyH0EDoXQlMiIf7PokRZ04vUawNPbZt5QDQ25cA4lvSGJWjGcHnBn1vXqrNuTOvIbCyeFWbN3pV+QNMUvqV3vr+FsPoF6FMQyoGJNj6zi3xm4FBAAzl6CeCId/MpkPyQNf4DCGrHdTaJOM0zyEApQfgBe24ivofMfj+aEHDN55/glfFWn4BHgOV2Tv49ftl6nvVG6uQ1Y03wbrTxogT99zyGrlLLgBPRwUQsS8Y/qFpWO47EWHuErYJyAADUz80/aJohsELFHgaTG+h9120QfcNz5OJ8zujHPf83EQIDAQAB';
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradePagePayRequest ();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $params1 = array(
            'body' => '订单支付',   //具体信息描叙
            'subject' =>$params['subject'],         //商品的标题/交易标题/订单标题/订单关键字等。
            'out_trade_no' => $params['ordersn'],    //商户网站唯一订单号，用pay_log里的uniontid
            'total_amount' => $params['total_amount'],         //订单总金额，单位为元，精确到小数点后两位
            'product_code' => 'FAST_INSTANT_TRADE_PAY'
        );
		$return_url="https://".$_SERVER['HTTP_HOST']."/refund.php";
        $bizcontent = json_encode($params1);
        $request->setNotifyUrl('http://'.$_SERVER['HTTP_HOST'].'/alipay_api.php');
		$request->setReturnUrl($return_url);
	//	var_dump($request);exit;
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->pageExecute($request);
        $code = ($response);//就是orderString 可以直接给客户端请求，无需再做处理。
       // echo $code;exit;
        return $code;
	}

	public function notify(){
		$aop=$this->aop;
		$aop->alipayrsaPublicKey = $this->pub;
	
		$flag = $aop->rsaCheckV1($_POST, NULL,$this->RSA);
		message1('111'.$flag);
		if($flag){
			message1('进入');
			message1($_POST);
			$out_trade_no = $_POST['out_trade_no'];
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			$total_fee=$_POST['buyer_pay_amount'];
			message1($file='total_fee');
		    if($_POST['trade_status'] == 'TRADE_FINISHED') {
		    	echo "success";
		    }
		    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		    	message1('进来128');
		    	$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE uniontid='.$out_trade_no;
				$log = pdo_fetch($sql);
				message1($log);
				message1($sql);
				if(!empty($log) && $log['status'] == '0') {
					$log['transaction_id'] = $_POST['trade_no'];
					$record = array();
					$record['status'] = '1';
				//	$record['type']='alipay';
					$record['uniontid']=$_POST['trade_no'];
					pdo_update('core_paylog', $record, array('plid' => $log['plid']));
					$f=pdo_fetch("select status,totalprice from ".tablename('yhg_order')." where id='{$log['tid']}'");
					message1($f);
					message1($total_fee);
					if($f['status']!=0){
						echo "sign fail";exit;
					}
					$site = WeUtility::createModuleSite($log['module']);
					if($log['module']=='yhg'){
							$action='pay';
							$file=IA_ROOT.'/addons/'.$log['module'].'/inc/app/' . strtolower($action) . '.class.php'; 
							include_once $file;
							$classname = "payModuleSite";
							$log['result'] = 'success';
							$log['tag'] = $tag;
							$method = 'payResult';
							$obj=new $classname();
							$obj->__define=$site->__define;
							if (method_exists($obj, $method)) {
								message1($f='进来129');
								$f=$obj->$method($log,$api=1);
								message1('回调返回');
							}
					}
					message1($f='进来1290');
					message1($f);
					if($f==false){
						message1('错误');
						echo "sign fail";exit;
					}else{
						message1('成功');
						echo "success";	exit;
					}
				}
				message1($f='进来1291');
					echo "success";	
		    	}
				echo "success";		//请不要修改或删除
		}else{
			$data='1111111111'; 
			message1($data='任务失败');
			echo "sign fail";exit;
		}
	}
	
	public function get_mobile_alipay($id,$ordersn,$mid,$money){
			$data=array(
				'orderid'=>$id,
				'mid'=>$mid,
				'money'=>$money
			);
			$str="";
			$str=urlencode(json_encode($data));
			$file=ROOT_PATH.'framework/payment/alipay/wappay/buildermodel/AlipayTradeWapPayContentBuilder.php';
			include $file;
			$file1=ROOT_PATH.'framework/payment/alipay/wappay/service/AlipayTradeService.php';
			include $file1;
		    //商户订单号，商户网站订单系统中唯一订单号，必填
		    $out_trade_no = $ordersn;
			$notify_url='http://'.$_SERVER['HTTP_HOST'].'/notify.php';
			$return_url="http://slw.5niu.top/#/user";
		    //订单名称，必填
		    $subject = '用户充值';
		
		    //付款金额，必填
		    $total_amount = $money;
		
		    //商品描述，可空
		    $body = '用户充值';

		    //超时时间
		    $timeout_express="1m";
				
		    $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
		//	$payRequestBuilder->setstr($str);
		    $payRequestBuilder->setBody($body);
		    $payRequestBuilder->setSubject($subject);
		    $payRequestBuilder->setOutTradeNo($out_trade_no);
		    $payRequestBuilder->setTotalAmount($total_amount);
		    $payRequestBuilder->setTimeExpress($timeout_express);
		//	$biz_content=$payRequestBuilder->getBizContent();
		
			$payResponse = new AlipayTradeService($config);
			$result=$payResponse->wapPay($payRequestBuilder,$return_url,$notify_url,$str);
			//var_dump($result);exit;
			$result1 = $this->aop->pageExecute($result,"post");
			
			return $result1;
		//    $payResponse = new AlipayTradeService($config);
			
		  //  $result=$payResponse->wapPay($payRequestBuilder,$notify_url,$return_url);
	}

	public function transfer($params){
		sleep(2);
		$this->request = new AlipayFundTransToaccountTransferRequest();
		
		$params['amount']=round($params['amount'],2);
		insert_pai_message1($params,'alipay');
		//舞牛
    	$out_biz_no = time();
		$r=$this->request->setBizContent();
		$data="{" .
            "\"out_biz_no\":\"$out_biz_no\"," .
            "\"payee_type\":\"ALIPAY_LOGONID\"," .
            "\"payee_account\":\"$params[account]\"," .
            "\"amount\":\"$params[amount]\"," .
            "\"payer_show_name\":\"转帐给用户$params[account]\"," .
            "\"payee_real_name\":\"$params[name]\"," .
            "\"remark\":\" 顺利网转帐给用户\"" .
            "}";
        $this->request->setBizContent($data);
        $this->result = $this->aop->execute($this->request);
		
        $responseNode = str_replace(".", "_", $this->request->getApiMethodName()) . "_response";
        $resultCode = $this->result->$responseNode->code;
//		var_dump($resultCode);
//		echo '<hr/>';
//		var_dump($responseNode);
//		echo '<hr/>';
//		var_dump($this->result);exit;
		$data=array();
        if(!empty($resultCode)&& $resultCode == 10000){
           	$data['pay_data']=$this->result->$responseNode->pay_date;
			$data['order_id']=$this->result->$responseNode->order_id;
			$data['out_biz_no']=$this->result->$responseNode->out_biz_no;
			$data['resultCode']=$resultCode;
	
		
			return $data;exit;
        } else {
        	$data['sub_code']=$this->result->$responseNode->sub_code;
			$data['sub_msg']=$this->result->$responseNode->sub_msg;
			$data['resultCode']=$this->resultCode;
        	$data['msg']=$result->$this->responseNode->msg;
            return $data;
        }
	
	}
	
}
