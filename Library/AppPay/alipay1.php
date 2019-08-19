<?php
/* *
 * 功能：支付宝移动支付服务端签名页面
 * 版本：1.0
 * 日期：2016-06-06
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要编写。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 *************************页面功能说明*************************
 * 本页面代码示例用于处理客户端使用http(s) post传输到此服务端的移动支付请求参数待签名字符串。
 * 本页面代码示例采用客户端创建订单待签名的请求字符串传输到服务端的这里进行签名操作并返回。
 */


function dataencode($data){
	$data = explode('&',$data);
	$str = '';
	foreach($data as $k=>$v){
		$val = explode('=',$v);
			$strstr = $val[0].'='.urlencode($val[1]);
		$str .= $strstr.'&';
	}
	$len = strlen($str);
	$str = substr($str,0,$len-1);
	return $str;
}

include IA_ROOT."/addons/xt_jyd/inc/app_pay/lib/alipay_notify.class.php";
include IA_ROOT."/addons/xt_jyd/inc/app_pay/lib/alipay_rsa.function.php";
include IA_ROOT."/addons/xt_jyd/inc/app_pay/lib/alipay_core.function.php";
//确认PID和接口名称是否匹配。
date_default_timezone_set("PRC");
function return_key($params){
	include IA_ROOT."/addons/xt_jyd/inc/app_pay/alipay.config.php";
if ($request['partner']=$alipay_config['partner'] && $request['service']=$alipay_config['service']) {
//	$request['app_id'] = '2018022802289318';
////	var_dump($request);exit;
//	$request['app_id'] = '2016090101833096';
	// 获取订单信息
	$request['app_id']=$alipay_config['app_id'];
	//var_dump($alipay_config);exit;
	$order = array();
	$order['product_code'] = '';
	$order['timeout_express'] = '30m';
	$order['seller_id'] = '';
	$order['body'] = '123';
	// 获取商户订单号
	$order['out_trade_no']=$params['ordersn'];
	$order['total_amount'] = $params['total_amount'];
   
	$order['subject'] = $params['body'];
	$request['biz_content'] = '{"timeout_express":"30m","seller_id":"","product_code":"QUICK_MSECURITY_PAY","total_amount":"'.$order['total_amount'].'","subject":"'.$order['subject'].'","body":"'.$order['body'].'","out_trade_no":"'.$order['out_trade_no'].'"}';
	$request['charset'] = 'utf-8';
	$request['format'] = 'json';
	$request['method'] = 'alipay.trade.app.pay';
	$request['notify_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/alipay_api.php';
	$request['sign_type'] = 'RSA';
	$request['timestamp'] = date('Y-m-d H:i:s',time());
	$request['version'] = '1.0';
	unset($request['order']);
	unset($request['price']);
//	unset($request['token']);
	unset($request['partner']);
	unset($request['service']);
	unset($request['model']);
	unset($request['con']);
	unset($request['dire']);
	//将post接收到的数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串。
	$data=createLinkstring($request);
	//打印待签名字符串。工程目录下的log文件夹中的log.txt。
	logResult($data);
	//将待签名字符串使用私钥签名,且做urlencode. 注意：请求到支付宝只需要做一次urlencode.
	$rsa_sign=urlencode(rsaSign($data, $alipay_config['private_key']));
	// 对数据进行encode
	$data = dataencode($data);
	
	//把签名得到的sign和签名类型sign_type拼接在待签名字符串后面。
	//$data = $data.'&sign='.$rsa_sign.'&sign_type='.$alipay_config['sign_type'];
	$data = $data.'&sign='.$rsa_sign;
//	echo $data;exit;
	return $data;
	//返回给客户端,建议在客户端使用私钥对应的公钥做一次验签，保证不是他人传输。
}
else{
	return false;
	//echo "不匹配或为空！";
	//echo json_encode(['info'=>'不匹配或为空！','status'=>0]);exit;
//	header('Content-Type:application/json; charset=utf-8');
//	exit(json_encode(['info'=>'不匹配或为空！','status'=>0]));
//	exit(json_encode(['code'=>201,'msg'=>'不匹配或为空!','data'=>null]));
	//logResult(createLinkstring($_POST));
}
}
?>