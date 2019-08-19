<?php
class ali_refundModuleSite extends yhgModuleSite{
	/*
	 * 订单原路退还
	 * 
	 * */
	public $request;
	public $aop;
	public $lotus;
	public $pub="";
	public function __construct() {
		$file=IA_ROOT."/addons/".$this->modulename."/inc/app_pay/ali_app/AopSdk.php";
	//	$this->pub = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhGt9taOjNL4NR3AxA/tGltkyY5QYbzawxqaTVJLnFwxcc9siMyH0EDoXQlMiIf7PokRZ04vUawNPbZt5QDQ25cA4lvSGJWjGcHnBn1vXqrNuTOvIbCyeFWbN3pV+QNMUvqV3vr+FsPoF6FMQyoGJNj6zi3xm4FBAAzl6CeCId/MpkPyQNf4DCGrHdTaJOM0zyEApQfgBe24ivofMfj+aEHDN55/glfFWn4BHgOV2Tv49ftl6nvVG6uQ1Y03wbrTxogT99zyGrlLLgBPRwUQsS8Y/qFpWO47EWHuErYJyAADUz80/aJohsELFHgaTG+h9120QfcNz5OJ8zujHPf83EQIDAQAB';
       
		include $file;
	}
	public function getorder($id,$goods_id){
		global $_W,$_GPC;
		$sql="select o.totalprice,o.transid,oo.totalprice oo_totalprice,oo.suboid,o.api,o.ordersn from ".tablename('xiantuan_order')." o 
			join ".tablename('xiantuan_order_goods')." oo on o.id=oo.orderid where o.id=".$id." and oo.id=".$goods_id;
		$data=pdo_fetch($sql);
		return $data;	
	}
	
	public function refund($order,$re,$money,$xcx=0){
		
		$aop = new AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->method='alipay.trade.refund';
	   	$sql="select app_payment from ".tablename('uni_settings')." where uniacid=5";
		$setting_app=unserialize(pdo_fetchcolumn($sql));
		$aop->method='alipay.trade.refund';
		if($order['type']==2 && !$setting_app['ali']){
			$result['msg']='请后台先行设置支付宝网页配置参数';
			return false;
		}elseif($order['type']==4 && !$setting_app['ali_xcx']){
			$result['msg']='请后台先行设置支付宝小程序配置';
			return false;
		}
		
		if($order['type']==2){
			$aop->appId=$setting_app['ali']['app_alipay_appid'];
			$aop->alipayrsaPublicKey=trim($setting_app['ali']['app_alipay_public_key']);
			$aop->rsaPrivateKey=trim($setting_app['ali']['app_alipay_private_key']);
		}else{
			$aop->appId=$setting_app['ali_xcx']['app_alipay_appid'];
			$aop->alipayrsaPublicKey=trim($setting_app['ali_xcx']['app_alipay_public_key']);
			$aop->rsaPrivateKey=trim($setting_app['ali_xcx']['app_alipay_private_key']);
		}
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='UTF-8';
		$aop->format='json';
		//$order=$this->getorder($id,$goods_id);
		$data=array(
			'trade_no'=>$order['transid'],
			'refund_amount'=>$money,
			'out_request_no'=>$order['suboid'],//同一个订单多次退款必传参数
			'refund_reason'=>"订单号".$order['ordersn'].'_'."申请退款 .原路返还",
		);
		$biz=json_encode($data);
		$request = new AlipayTradeRefundRequest ();
		$request->setBizContent($biz);
		$result = $aop->execute ( $request); 
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		return $result->$responseNode;
	}

	public function refund_data($params){

		$aop = new AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
	   	$sql="select app_payment from ".tablename('uni_settings')." where uniacid=5";
		$setting_app=unserialize(pdo_fetchcolumn($sql));
	
	   	if(!$setting_app['ali']['app_alipay_appid']){
	   		$result['msg']='请先设置支付宝帐号公私钥在来退款';
			echo json_encode($result);exit;
	   	}
		$aop->appId=$setting_app['ali']['app_alipay_appid'];
		$aop->alipayrsaPublicKey=trim($setting_app['ali']['app_alipay_public_key']);
		$aop->rsaPrivateKey=trim($setting_app['ali']['app_alipay_private_key']);

		$aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayFundTransToaccountTransferRequest();
		$out_biz_no=$params['ordersn'];
		$data=array(
			'out_biz_no'=>$out_biz_no,
			'payee_type'=>'ALIPAY_LOGONID',
			'payee_account'=>$params['account'],
			'amount'=>$params['realmoney'],
			'payer_show_name'=>'转帐给用户:'.$params['account'],
			'payee_real_name'=>$params['name'],
			'remark'=>'节约点转帐给用户',
		);
		$biz=json_encode($data);
		$request->setBizContent($biz);
        $result = $aop->execute($request);
	
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
		if($resultCode==10000){
			return $result->$responseNode;
		}else{
			return $result->$responseNode;
		}
	
	}
}