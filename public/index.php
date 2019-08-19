<?php
/**
 * 统一访问入口
 */
header("Access-Control-Allow-Origin:http://127.0.0.1:8020");
header("Access-Control-Allow-Methods:GET,POST,OPTIONS");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers:Origin,No-Cache,X-Requested-With,If-Modified-Since,Pragma,Last-Modified,Cache-Control,Expires,Content-Type,X-E4M-With,authorization,Authorization,Token");
header("Access-Control-Request-Headers:Origin,X-Requested-With,content-Type,Accept,authorization,Authorization");
//header('Access-Control-Allow-Origin:http://127.0.0.1:8020');//允许所有来源访问
//header('Access-Control-Allow-Method:POST,GET');//允许访问的方式
//header('Access-Control-Allow-Credentials');
//header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With,Token");

require_once dirname(__FILE__) . '/init.php';
define('DBNAME','db_master');
define('TABLE','');
define('SMS_FLAG',1);
define('IS_CHECK_ADDR',1);
$mobile_preg='/^1[3|4|5|6|7|8|9]{1}[0-9]{9}$/';
define('MOBILE',$mobile_preg);
$money_preg='/^\d+\.{0,1}\d{2}$/';
//preg_match($money_preg,$money,$match);
define('MONEY',$money_preg);
define('REMOVED',31);
define('START_REDIS_MSG',0);//开启日志和消息记录使用redis缓存发送 使用这块必须把socket开启 使用单独 的cli启动socket 来存储和发送消息等
define("EARTH_RADIUS", 6378137); //地球半径，平均半径为6371km
define("PI",3.141592653);
define("STORE_REGEX",0);//商家接单是否完全匹配
define("MOBILE_SMS",0);//全局开启短信发送
//substr(__FILE,0,stripos(__FILE,'/public'))
function site_refund_insert_message($data,$orderid){
		if(!is_string($data)){
	    	$str=json_encode($data);
		}else{
			$str=$data;
		}
	//	$str=params_data($data);
	
		$date=date('Y-m-d',time());
		//echo __FILE__;exit;
		$IA_ROOT=substr(__FILE__,0,strrpos(__FILE__,'/public'));
        $file=$IA_ROOT."/error/message/$date/";
	
        if(!file_exists($file)){
            mkdir($file,0777,true);
        }
		$time=time();
        $file.= $orderid.".txt";
		$str="\r\n".'=========================='."".$str."".'=================================';
        file_put_contents($file,$str,FILE_APPEND);
		return ;
}

function message($data){
	site_refund_insert_message($data,'message_pay');
}
function new_dom(){
//	var_dump(phpinfo());exit;
	$html=new DOMDocument('1.0');
	return $html;
}
function dom_path($dom){
	$xpath = new DOMXPath($dom);
	return $xpath;
}	
//for($i=0;$i<5;$i++){
//	for($j=0;$j<5-$i;$j++){
//		echo "\t";
//	}
//	for($j=0;$j<$i*2-1;$j++){
//		
//		echo "★";
//	}
//	echo '<Br/>';
//}	
site_refund_insert_message("index开始时间".microtime(),'times');
$pai = new \PhalApi\PhalApi();
$pai->response()->output();

