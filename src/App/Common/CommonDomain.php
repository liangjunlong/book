<?php
namespace App\Common;	
use Mobile\ChuanglanSmsHelper\ChuanglanSmsApi;
use App\Domain\Setting\Setting;	
use function App\redis_check;
use App\Domain\Setting\HotelType as HotelTypeModel;
class CommonDomain
{
	public $table="mdhr";
	public $order_num=31;
	public $error="";
	public $sesscue="";
	//检测当前是否发送toekn并生成提示，如发送则根据token查找用户信息
	public function check_auth_token(){
		var_dump($this);exit;
	}
	
	public function redis($md5,$data=false,$time=false,$function=false){
		return redis_check($md5,$data,$time,$function);
	}
	public function get_hotel_data($function,$data){
		$hotel=new HotelTypeModel();
		$hotel_data=$hotel->$function($data);
		return $hotel_data['data'];
	}
	public function get_return($result,$re,$msg=""){
		
		if($re){
			$result['result']='1';
			$result['msg']=$msg;
			$result['data']=$re;
		}
		return $result;
	}
	//处理二维数组的房间属性参数
	public function check_equipment($release_data){
		$equipment_data=$this->get_hotel_data('select_equipment',[]);
		foreach($release_data as $k=>$v){
			$num=0;
			foreach($equipment_data as $kk=>$vv){
				if(in_array($vv['id'],explode(',', $v['equipment_id']))){
					$release_data[$k]['equipment_data'][$num]=$vv;
					$num++;
					}
				}
			}
		return $release_data;
	}
	
	//处理一维数据房间属性
	public function check_equipment_single($release_data){
		$equipment_data=$this->get_hotel_data('select_equipment',[]);
		$release_equipment=explode(',', $release_data['equipment_id']);
		$num=0;
		foreach($equipment_data as $kk=>$vv){
			if(in_array($vv['id'],$release_equipment)){
				$release_data['equipment_data'][$num]=$vv;
				$num++;
			}
		}
		unset($release_data['equipment_id']);
		return 	$release_data;
	}
		public function check_room_single($rob_data,$flag="room_type"){
		$room_data=$this->get_hotel_data('select_room_types',[]);
		$room_data_rob=explode(',', $rob_data[$flag]);
		$num=0;
		foreach($room_data as $kk=>$vv){
			if(in_array($vv['id'],$room_data_rob)){
				$rob_data['room_data'][$num]=$vv;
				$num++;
			}
		}
		unset($rob_data[$flag]);
		return 	$rob_data;
	}
	public function setting($select_function,$data=[]){
		$setting=new setting();
		if($data){
			$datas=$setting->$select_function($data);
		}else{
			$datas=$setting->$select_function();
		}
		return $datas;
	}
	
	//获取用户所在表的路径
	public function getuser_id($mid){
		$id=$mid%$this->order_num;
		return $id;
	}
	
	public function message($mobile,$msg){
		$clapi=new ChuanglanSmsApi();
		$result = $clapi->sendSMS($mobile, $msg);
		if(!is_null(json_decode($result))){
			$output=json_decode($result,true);
			if(isset($output['code'])  && $output['code']=='0'){
				return $f='0';
			}else{
				return $output['errorMsg'];
			}
		}else{
			return $result;
		}
	}
	
	public function upload_image_bsae64_single($pic1){
				$file=substr(__FILE__,0,stripos(__FILE__,'src'));
				if (!file_exists($file.'public/images/5/'.date('Y').'/'.date('m').'/'.date('d'))) {
                 	mkdir($file.'public/images/5/'.date('Y').'/'.date('m').'/'.date('d'), 0777, true);
                }
				
				file_put_contents($file.'public/images/5/'.date('Y').'/'.date('m').'/'.date('d').'/a1.text', $pic1);
                $user_pic = substr(strstr($pic1, ','), 1);
				
                $imgs = base64_decode($user_pic);
                $uploadpath = $file.'public/upload/images/5/'.date('Y').'/';
                $uploadpath .= date('Y-m-d',time())."/";
                if (!file_exists($uploadpath)) {
                 	$r=mkdir($uploadpath, 0777, true);
					var_dump($r);exit;
                }
				
                $pic_path = $uploadpath . time() . mt_rand(1000, 9999) . ".jpg";
                //保存base64图片
          		
                file_put_contents($pic_path, $imgs);
				$pic=substr($pic_path,stripos($pic_path,'/public/'));
				$pic='http://'.$_SERVER['HTTP_HOST'].$pic;
				return $pic;
	}
	//加密
	public function encryption($data){
		return urlencode(json_encode($data));
	}
	//解密
	public function unencryption($data){
		return urldecode(json_decode($data));
	}
}