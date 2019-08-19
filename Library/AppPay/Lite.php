<?php
if (!defined('APPPAY')) {
    $file=dirname(__FILE__) . '/';
	$wx_file=$file.'WxPay.php';
    require($wx_file);
//	$wx_file=$file.'WxPay.php';
//  require_once($wx_file);
//	$wx_file=$file.'WxPay.php';
//  require_once($wx_file);
//	$wx_file=$file.'WxPay.php';
//  require_once($wx_file);
//	$wx_file=$file.'WxPay.php';
//  require_once($wx_file);
}
class AppPay_Lite{
	public function new_wexin($status,$setting){
		$this->wx=new WxPay($status,$setting);
		return $this->wx;  
	}
}
?>