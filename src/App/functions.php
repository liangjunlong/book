<?php

namespace App;
use PhalApi\Exception\BadRequestException;


/**
 *公用方法 按表字段返回数据 
 * 
 */
function return_limit_data($data,$flag_id,$flag_str){
		$data_result=[];
		$num=0;
		foreach($data as $k=>$v){
			if($v[$flag_id] && empty($data_result)){
				$data_result[$num]=$v;
				$num++;
				continue;
			}
			$flag=0;
			if($data_result){
				foreach($data_result as $kk=>$vv){
					if($v[$flag_id]==$vv[$flag_id]){
						$data_result[$kk][$flag_str].=','.$v[$flag_str];
						$flag=1;
						break;
					}
				}
			}
			if($flag==0){
				$data_result[$num]=$v;
				$num++;
			}
		}
		return $data_result;
}
function redis_check($md5,$data,$time=1,$function){
		$di = \PhalApi\DI();
		if($di->cache_redis){
			if($function!=false){
				$data=$di->cache_redis->$function($md5,$time);
				return $data;
			}else{
				if(!$data || empty($data)){
					$near_count=$di->cache_redis->get($md5);
					return $near_count;
				}else{
					$r=$di->cache_redis->set($md5,$data,$time);
					return $r;
				}
			}
		}
		return false;
	}
function check_addr($lat_lng,$addr_details,&$msg=""){
	$data_result=gaode_get_addr($lat_lng);
	if(!$data_result){
		$msg="未查找到该地区的经纬 度请检查地址是否正确";
		return false;
	}

	$city=$data_result->city;
	$details=$data_result->neighborhood->name;
	//1.查找字符串当中是否有这个城市
	$r_city=stripos($addr_details, $city);
//	var_dump($data_result);exit;
//	$r_area=stripos($details,$addr_details);
	if(!$r_city || !$r_city){
		$msg="当前经纬度和填写的具体的地址不是同一个城市";
		return false;
	}
	return true;
	
}
//根据地区获取经纬度
function gaode_get_lat($addr){
	global $_W,$_GPC;
	$key='fb1d809bfd398e14f79b260d90c722d6';
	$url="http://restapi.amap.com/v3/geocode/geo?&address=".$addr."&key=".$key."&output=json";
	$obj=json_decode(file_get_contents($url));
	
	if($obj->status==1){
		return $obj->geocodes[0]->location;
	}else{
		return false; 
	}
}

function gaode_get_addr($str){
	global $_W,$_GPC;
	$key='fb1d809bfd398e14f79b260d90c722d6';
	$url="http://restapi.amap.com/v3/geocode/regeo?&location=".$str."&key=".$key."&output=json";
	$obj=json_decode(file_get_contents($url));
	if($obj->status==1){
		return $obj->regeocode->addressComponent;
	}else{
		return false; 
	}
}
function do_array($data){
		$str="\r\n";
		if(is_array($data) || is_object($data)){
			foreach($data as $k=>$v){
				if((is_array($v) || is_object($v)) && !empty($v)){
					
					$str.=$k.'=>'.do_array($v);
				}else{
					$str.=$k.'=>'.$v."\r\n";
				}
			}	
		}else{
			$str.=$data;
		}
		return $str;
}
function isimplexml_load_string($string, $class_name = 'SimpleXMLElement', $options = 0, $ns = '', $is_prefix = false) {
	libxml_disable_entity_loader(true);
	if (preg_match('/(\<\!DOCTYPE|\<\!ENTITY)/i', $string)) {
		return false;
	}
	return simplexml_load_string($string, $class_name, $options, $ns, $is_prefix);
}
function getoverdue(){
	$time=time()+86400*7;
	return $time;
}
function token(){
	$str='feitlong'.getIP().time();
	$token=md5($str);
	return $token;
}
function getIP(){
	$ip=$_SERVER['REMOTE_ADDR'];	
	return $ip;
}
function getordersn($header='hotel',$mid=0){
	return $header.date('YmdHis',time()).$mid.mt_rand(1000,9999);
}
function param($data,$rule,$fi='post'){
	$service=[];
	$_REQUEST['service']=$_REQUEST['service']??$_REQUEST['s'];
	$service = explode('.',$_REQUEST['service']);
    $fun_name = lcfirst($service[2]);
	$param=[];
	if(!isset($rule[$fun_name])){
		return $data=[];
	}
	foreach($rule[$fun_name] as $k=>$v){
//		echo '$k=>'.$k;
//		echo '<br/>';
		if(isset($data->$k)){
			$param[$k]=$data->$k;
		}
	}
	//记录日志 用户传参数据
	addlog($param,$fi);
	return $param;
}

function addlog($content,string $filename=""):?bool
{
	$file=__FILE__;
	if(!$filename){
		$filename="/master.tet";
	}
	$file=substr($file,0,stripos($file,'/src')+1).'logs'.'/'.date('Y',time()).'-'.date('m',time()).'-'.date('d')."/";
	if (!file_exists($file)) 
		mkdir($file,0777,true); 
	$file.=$filename;
	$content=do_array($content);
	$content.="\r\n======================";
	$r=file_put_contents($file, $content,FILE_APPEND);
	return $r;		
}
	//聚合数据
function poly_data($data_return){
		//组合数据
		$objarr=[];
		$num=0;
		$m=0;
		foreach($data_return as $k=>$v){
			if($k==0 || 
			(isset($objarr[$num]['id']) && isset($objarr[$num]['table_id']) && $v['id']==$objarr[$num]['id'] && $v['table_id']==$objarr[$num]['table_id'])){
				$objarr[$num]['id']=$v['id'];
				$objarr[$num]['mid']=$v['mid'];
				$objarr[$num]['ordersn']=$v['ordersn'];
				$objarr[$num]['status']=$v['status'];
				$objarr[$num]['day']=$v['day'];
				$objarr[$num]['num']=$v['num'];
				$objarr[$num]['paytype']=$v['paytype'];
				$objarr[$num]['toalprice']=$v['totalprice'];
				$objarr[$num]['integral']=$v['integral'];
				$objarr[$num]['coupon_id']=$v['coupon_id'];
				$objarr[$num]['refund_coupon_status']=$v['refund_coupon_status'];
				$objarr[$num]['refund_integral_status']=$v['refund_integral_status'];
				$objarr[$num]['name']=$v['name'];
				$objarr[$num]['logo']=$v['logo'];
				$objarr[$num]['table_id']=$v['table_id'];
				$objarr[$num]['goods'][$m]['d_id']=$v['d_id'];
				$objarr[$num]['goods'][$m]['id']=intval($v['id']);
				$objarr[$num]['goods'][$m]['price']=floor($v['price']);
				$objarr[$num]['goods'][$m]['d_num']=intval($v['d_num']);
				$objarr[$num]['goods'][$m]['use_num']=intval($v['use_num']);
				$objarr[$num]['goods'][$m]['refund_num']=intval($v['refund_num']);
				$objarr[$num]['goods'][$m]['kou_num']=intval($v['kou_num']);
				$objarr[$num]['goods'][$m]['start_time']=intval($v['start_time']);
				$objarr[$num]['goods'][$m]['end_time']=$v['end_time'];
				$m++;
			}else{
				$num++;
				$m=0;
				$objarr[$num]['id']=$v['id'];
				$objarr[$num]['mid']=$v['mid'];
				$objarr[$num]['ordersn']=$v['ordersn'];
				$objarr[$num]['status']=$v['status'];
				$objarr[$num]['day']=$v['day'];
				$objarr[$num]['num']=$v['num'];
				$objarr[$num]['paytype']=$v['paytype'];
				$objarr[$num]['toalprice']=$v['totalprice'];
				$objarr[$num]['integral']=$v['integral'];
				$objarr[$num]['coupon_id']=$v['coupon_id'];
				$objarr[$num]['refund_coupon_status']=$v['refund_coupon_status'];
				$objarr[$num]['refund_integral_status']=$v['refund_integral_status'];
				$objarr[$num]['name']=$v['name'];
				$objarr[$num]['logo']=$v['logo'];
				$objarr[$num]['table_id']=$v['table_id'];
				$objarr[$num]['goods'][$m]['d_id']=$v['d_id'];
				$objarr[$num]['goods'][$m]['id']=intval($v['id']);
				$objarr[$num]['goods'][$m]['price']=floor($v['price']);
				$objarr[$num]['goods'][$m]['d_num']=intval($v['d_num']);
				$objarr[$num]['goods'][$m]['use_num']=intval($v['use_num']);
				$objarr[$num]['goods'][$m]['refund_num']=intval($v['refund_num']);
				$objarr[$num]['goods'][$m]['kou_num']=intval($v['kou_num']);
				$objarr[$num]['goods'][$m]['start_time']=intval($v['start_time']);
				$objarr[$num]['goods'][$m]['end_time']=$v['end_time'];
				$m++;
			}
		}
		return $objarr;
	}
	function sort_data($refund_data){
		//组合数据
		$objarr=[];
		$num=0;
		$m=0;
		foreach($refund_data as $k=>$v){
			if($k==0 || (isset($objarr[$num]['id']) && $v['id']==$objarr[$num]['id'])){
				$objarr[$num]['id']=$v['id'];
				$objarr[$num]['mid']=$v['mid'];
				$objarr[$num]['orderid']=$v['orderid'];
				$objarr[$num]['refund_ordersn']=$v['refund_ordersn'];
				$objarr[$num]['shen_status']=$v['shen_status'];
				$objarr[$num]['totalprice']=$v['totalprice'];
				$objarr[$num]['totalnum']=$v['totalnum'];
				$objarr[$num]['status']=$v['status'];
				$objarr[$num]['bili']=$v['bili'];
				$objarr[$num]['real_money']=$v['real_money'];
				$objarr[$num]['table_id']=$v['table_id'];
				$objarr[$num]['text']=$v['text'];
				$objarr[$num]['bo_time']=$v['bo_time'];
				$objarr[$num]['goods'][$m]['g_id']=$v['g_id'];
				$objarr[$num]['goods'][$m]['num']=intval($v['num']);
				$objarr[$num]['goods'][$m]['price']=floor($v['price']);
				$objarr[$num]['goods'][$m]['totalprice']=intval($v['totalprice']);
				$objarr[$num]['goods'][$m]['storeid']=intval($v['storeid']);
				$objarr[$num]['goods'][$m]['goodsid']=intval($v['goodsid']);
				$m++;
			}else{
				$num++;
				$m=0;
				$objarr[$num]['id']=$v['id'];
				$objarr[$num]['mid']=$v['mid'];
				$objarr[$num]['orderid']=$v['orderid'];
				$objarr[$num]['refund_ordersn']=$v['refund_ordersn'];
				$objarr[$num]['shen_status']=$v['shen_status'];
				$objarr[$num]['totalprice']=$v['totalprice'];
				$objarr[$num]['totalnum']=$v['totalnum'];
				$objarr[$num]['status']=$v['status'];
				$objarr[$num]['bili']=$v['bili'];
				$objarr[$num]['real_money']=$v['real_money'];
				$objarr[$num]['table_id']=$v['table_id'];
				$objarr[$num]['text']=$v['text'];
				$objarr[$num]['bo_time']=$v['bo_time'];
				$objarr[$num]['goods'][$m]['g_id']=$v['g_id'];
				$objarr[$num]['goods'][$m]['num']=intval($v['num']);
				$objarr[$num]['goods'][$m]['price']=floor($v['price']);
				$objarr[$num]['goods'][$m]['totalprice']=intval($v['totalprice']);
				$objarr[$num]['goods'][$m]['storeid']=intval($v['storeid']);
				$objarr[$num]['goods'][$m]['goodsid']=intval($v['goodsid']);
				$m++;
			}
		}
		return $objarr;
	}

