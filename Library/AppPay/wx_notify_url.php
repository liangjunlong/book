<?php
	define('IN_API', true);
	define('ROOT_PATH',substr(__FILE__,0,strripos(__FILE__,'/',0)));
	define('ROOT_PATHA',substr(__FILE__,0,stripos(__FILE__,'payment',0)));
	define('ROOT_PATHA_P',substr(__FILE__,0,stripos(__FILE__,'api',0)));
	require_once ROOT_PATHA."payment/uc.php";
	require_once ROOT_PATHA."payment/payResult.php";
	require_once ROOT_PATH."/alipay.config.php";
	require ROOT_PATH."/wx.php";
    require ROOT_PATHA_P."api/shops/result_card.php";
	
	$obj= new WeipayController();
	$code=$obj->weixin();
	
	if($code==false){
		message1('错误');exit;
	}
	
	$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniontid`=:uniontid';
	$params = array();
	$params[':uniontid'] = $code['ordersn'];
	message($code);
	$log = pdo_fetch($sql, $params);
	message($log);
	if(!empty($log) && $log['status'] == '0') {
		$log['transaction_id'] = $code['transaction_id'];
		$record = array();
		if($log['card_id']>0 && $log['card_money']>0){
			$record['card_type']='1';
			pdo_update('core_paylog', $record, array('plid' => $log['plid']));
		}
	/*	$f=pdo_fetch("select status,totalprice from ".tablename('xiantuan_order')." where id='{$log['tid']}'");
		if($f['status']!=0 || $f['totalprice']!=$total_fee){
			echo "sign fail";exit;
		}*/
		
		$ret = array();
		$ret['plid']=$log['plid'];
		$ret['status']=1;
		$ret['weid'] = $log['weid'];
		$ret['uniacid'] = $log['uniacid'];
		$ret['result'] = 'success';
		$ret['from'] = 'notify';
		$ret['type']='wechat';
		$ret['trade_no']=$trade_no;
		$ret['tid'] = $log['tid'];
		$ret['uniontid'] = $log['uniontid'];
		$ret['transaction_id'] = $log['transaction_id'];
		$ret['user'] = $log['openid'];
		$ret['fee'] = $log['fee'];
		$ret['is_usecard'] = $log['is_usecard'];
		$ret['card_type'] = $log['card_type'];
		$ret['card_fee'] = $log['card_fee'];
		$ret['card_id'] = $log['card_id'];
		message($f='进来129');
		if($log['card_id']>0 && $log['card_money']>0){
			$ret['card_type']='1';
			//单独跳转页面
		}
		if($log['pay_status']==1 and $log['card_money_id']){
			$ret['card_status']=$log['card_status'];
			$ret['card_money_id']=$log['card_money_id'];
			 message($f='进入卡卷购买');
			 $f=$result_card($ret);
			 message($f='返回卡卷购买');
		}
		else{
			 message($f='进入其他支付');
			$f=payResult($ret);
		}
		message($f);
		if($f==false){
			message('完成错误');
			$result['return_code']='FAIL';
			$result['return_msg']='OK';
			$xml=xml($result);
		}else{
			message('正确');
			$result['return_code']='SUCCESS';
			$result['return_msg']='OK';
			$xml=xml($result);
		}
		echo $xml;
		message($xml);die();
	}
message1('订单错误');
function xml($data){
	  $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
}