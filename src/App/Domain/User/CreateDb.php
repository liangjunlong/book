<?php
namespace App\Domain\User;
use App\Common\CommonDomain;
use App\Model\User\User as ModelUser;
use function App\token;
use function App\getIP;
use function App\getoverdue;
set_time_limit(0);
class CreateDb extends CommonDomain
{
   	private static $Model = null;
	public $flag_str="-";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
	
    public function __construct()
    {
        if (self::$Model == null) {
            self::$Model = new ModelUser();
        }
    }
	public function add_alter($table,$column_name,$msg){
		$data=[];
		for($i=0;$i<32;$i++){
			$hotel_order_or_=$table.$i;
			$sql=" SELECT * FROM information_schema.`COLUMNS` WHERE table_name ='".$hotel_order_or_."' and column_name ='".$column_name."'";
			$data[$i]['table']=$hotel_order_or_;
			$data[$i]['sql']=$sql;
		//	$alter_sql="ALTER TABLE ".$hotel_order_or_." DROP ".$column_name;

			$alter_sql=" alter table ".$hotel_order_or_." add ".$column_name." int not null comment'".$msg."'";
			$data[$i]['alter_sql']=$alter_sql;
		}

		foreach($data as $k=>$v){
			$r=self::$Model->get_orm()->queryAll($v['sql']);//创建表
			if(!$r || empty($r)){
				$r2=self::$Model->get_orm()->queryAll($v['alter_sql']);
			}
		}
		
//		for($i=0;$i<32;$i++){
//			$hotel_order_details="hotel_order_details_".$i;
//			$sql=" SELECT * FROM information_schema.`COLUMNS` WHERE table_name ='".$hotel_order_details."' and column_name ='kou_num'";
//			$data[$i]['table']=$hotel_order_details;
//			$data[$i]['sql']=$sql;
//			$alter_sql=" alter table ".$hotel_order_details." add kou_num int not null comment'预扣除申请退款扣除'";
//			$data[$i]['alter_sql']=$alter_sql;
//		}
//		foreach($data as $k=>$v){
//			$r=self::$Model->get_orm()->queryAll($v['sql']);//创建表
//			if(!$r || empty($r)){
//				$r2=self::$Model->get_orm()->queryAll($v['alter_sql']);
//			}
//		}
	}
	public function createdb(){
		//1.创建以用户为条件的表
		$this->add_alter('hotel_','refund_integral_status','退款时用到记录是否退积分');
		exit;
		for($i=0;$i<32;$i++){
			$hotel_order="hotel_order_".$i;
			$hotel_pay_log="hotel_pay_log_".$i;
			$hotel_order_or="hotel_order_or_".$i;
			$hotel_order_details="hotel_order_details_".$i;
			$hotel_order_evaluation="hotel_order_evaluation_".$i;
			$hotel_order_release="hotel_order_release_".$i;
			$hotel_order_release_or="hotel_order_release_or_".$i;
			$hotel_order_release_details="hotel_order_release_details_".$i;
			$hotel_order_grab_single="hotel_order_grab_single_".$i;
			$hotel_paylog="hotel_paylog_".$i;
			$sql_hotel_paylog=" DROP TABLE IF EXISTS `".$hotel_paylog."`;
			CREATE TABLE `".$hotel_paylog."`(
			`plid` int primary key auto_increment,
			`mid` int not null,
			`transid` varchar(88) not null,
			`ordersn` varchar(32) not null,
			`tid` int not null,
			`type` varchar(20) not null,
			`status` tinyint not null,
			`pay_status` tinyint not null,
			`fee` decimal(10,2) not null,
			`score` decimal(10,2) not null comment'积分',
			`card_money` decimal(10,2) not null comment'优惠卷抵扣的钱',
			`coupon_id` int not null comment'优惠卷ID',
			`api_status` tinyint not null,
			`xcx` tinyint not null,
			`prepay_id` varchar(88) not null,
			`time` int not null,
			index ordersn_status(ordersn,status,tid)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0";
			$sql_hotel_order=" DROP TABLE if exists `".$hotel_order."`;
			CREATE TABLE IF NOT EXISTS `".$hotel_order."`(
			`id` int primary key auto_increment,
			`mid` int not null,
			`status` tinyint not null,
			`paytype` tinyint not null comment'1微信2支付宝3微信小程序4余额',
			`ordersn` varchar(32) not null,
			`num` int not null comment'总数量',
			`day` int not null comment'居住天数',
			`totalprice` decimal(10,2) not null comment'总钱',
			`in_time` char(11) not null comment'入驻时间',
			`time` int not null,
			 index mid_status_paytype(mid,status,in_time),
			 index mid_paytype(paytype,mid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0";
			$sql_hotel_pay_log=" DROP TABLE if exists `".$hotel_pay_log."`;
			create table if not exists `".$hotel_pay_log."`(
			`id` int primary key auto_increment,
			`mid` int not null,
			`type` tinyint not null comment'1 支付 2退款 3 充值  4提现   5邀请好友 6注册 7预留  三级分销',
			`time` int not null comment'时间',
			`credit` char(12) not null comment'integral,credit,wechat,xcx,alipay现金',
			`orderid` int not null comment'订单ID',
			`msg` varchar(88) not null comment'文字说明',
			`add_money` decimal(10,2) not null comment'赠送的钱',
			`topup_id` int not null comment'充值ID',
			`refund_id` int not null comment'退款',
			`withdraw_id` int not null comment'提现ID',
			`agentid` int not null comment'agentid',
			`storeid` int not null comment'所属商家',
			`money` int not null comment'交易的钱',
			index mids(mid,type,orderid)
			)comment'交易日志表'";
			$sql_hotel_order_or="DROP TABLE IF EXISTS `".$hotel_order_or."`;
	 		CREATE TABLE IF NOT EXISTS `".$hotel_order_or."`(
			`id` int primary key auto_increment,
			`orderid` int not null,
			`coupon_id` int not null,
			`integral` decimal(10,2) not null comment'使用的积分',
			`balance` decimal(10,2) not null comment'使用的余额',
			`rmb` decimal(10,2) not null comment'使用现金',
			`release_id` int not null comment'关联发布ID无太多用处',
			`delete_status` tinyint not null,
			`delete_time` int not null,
			`refund_coupon_status` tinyint not null comment'是否退优惠卷 0未退1已退',
			'refund_balance_status' tinyint not null comment'是否退余额 0未退1已退',
			`message_status` tinyint not null comment'发送消息状态 1已发送',
			index orderids(orderid,delete_status,message_status)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0 comment'订单其他表';";
			
			$sql_hotel_order_details="DROP TABLE IF EXISTS `".$hotel_order_details."`;
			 CREATE TABLE IF NOT EXISTS `".$hotel_order_details."`(
			`id` int primary key auto_increment,
			`orderid` int not null,
			`storeid` int not null comment'商家ID',
			`price` decimal(10,2) not null,
			`num` int not null,
			`use_num` int not null comment'按时间进行计算使用了天数',
			`status` tinyint not null comment'状态',
			`distance` decimal(6,2) not null comment'KM单位',
			`refund_num` int not null comment'已退的数量',
			`kou_num` int not null comment'预扣除的数量',
			`start_time` int not null,
			`end_time` int not null,
			`time` int not null,
			index rele(storeid,status),
			index orders(orderid,status)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0;";
			$sql_hotel_order_evaluation="DROP TABLE IF EXISTS `".$hotel_order_evaluation."`;
			 CREATE TABLE IF NOT EXISTS `".$hotel_order_evaluation."`(
			`id` int primary key auto_increment,
			`mid` int not null,
			`orderid` int not null,
			`storeid` int not null,
			`start` int not null comment'星星',
			`text` text not null,
			`image` text not null,
			`status` tinyint not null comment'0开1关闭',
			index orderid_storeid_status(orderid,storeid,status),
			index orderid_mid_status(orderid,mid,status)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0;";
			$sql_hotel_order_release="DROP TABLE IF EXISTS `".$hotel_order_release."`; 
			CREATE TABLE IF NOT EXISTS `".$hotel_order_release."`(
			`id` int primary key auto_increment,
			`mid` int not null,
			`totalprice` decimal(10,2) not null,
			`num` int not null comment'需要的数量',
			`status` tinyint not null comment'0发布1商家接单2用户已同意',
			`ordersn` varchar(32) not null comment'需求订单号',
			`hotel_type` int not null comment'酒店的类型',
			`room_type` int not null comment'房间类型', 
			`lat` decimal(10,6) not null,
			`lng` decimal(10,6) not null,
			`distance` decimal(10,1) not null comment'距离限制附近几公里',
			`time` int not null,
			index orderid_mid_ordersn(mid,ordersn)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0;";
			$sql_hotel_order_release_or="DROP TABLE IF EXISTS `".$hotel_order_release_or."`; 
			CREATE TABLE IF NOT EXISTS `".$hotel_order_release_or."`(
			`id` int primary key auto_increment,
			`release_id` int not null,
			`mid` int not null,
			`text` varchar(200) not null comment'备注说明',
			`equipment_id` varchar(200) not null comment'设备',
			`time` int not null,
			index release_mid(release_id,mid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0;";
			
			$sql_hotel_order_release_details="DROP TABLE IF EXISTS `".$hotel_order_release_details."`;
			 CREATE TABLE IF NOT EXISTS `".$hotel_order_release_details."`(
			`id` int primary key auto_increment,
			`mid` int not null,
			`release_id` int not null comment'需求ID以防以后发布多需求',
			`total` decimal(10,2) not null comment'房间的总价', 
			`price` decimal(10,2) not null comment'价格',
			`day` int not null,
			`start_time` int not null comment'开始住的时间',
			`end_time` int not null comment'结束住的时间',
			`time` int not null,
			index release_id_mid(release_id,mid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0 comment'订单发布需求';";
			$sql_hotel_order_grab_single="DROP TABLE IF EXISTS `".$hotel_order_grab_single."`;
			 CREATE TABLE IF NOT EXISTS `".$hotel_order_grab_single."`(
			`id` int primary key auto_increment,
			`release_id` int not null comment'需求表ID',
			`storeid` int not null,
			`type_id` int not null,
			`money` decimal(10,2) not null comment'商家确认价格',
			`status` tinyint not null comment'1已同意2驳回',
			`ke_money` decimal(10,2) not null comment'用户可浮动价格在商家确认价格向下浮动',
			`time` int not null,
			 index release_id_store_id_type_id(release_id,storeid,type_id)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=0 comment'用户抢单表 最后供用户选择';";
			$data[$i][0]['table']=$hotel_order;
			$data[$i][0]['sql']=$sql_hotel_order;
			$data[$i][1]['table']=$hotel_pay_log;
			$data[$i][1]['sql']=$sql_hotel_pay_log;
			$data[$i][2]['table']=$hotel_order_or;
			$data[$i][2]['sql']=$sql_hotel_order_or;
			$data[$i][3]['table']=$hotel_order_details;
			$data[$i][3]['sql']=$sql_hotel_order_details;
			$data[$i][4]['table']=$hotel_order_evaluation;
			$data[$i][4]['sql']=$sql_hotel_order_evaluation;
			$data[$i][5]['table']=$hotel_order_release;
			$data[$i][5]['sql']=$sql_hotel_order_release;
			$data[$i][6]['table']=$hotel_order_release_details;
			$data[$i][6]['sql']=$sql_hotel_order_release_details;
			$data[$i][7]['table']=$hotel_order_grab_single;
			$data[$i][7]['sql']=$sql_hotel_order_grab_single;
			$data[$i][8]['table']=$hotel_order_release_or;
			$data[$i][8]['sql']=$sql_hotel_order_release_or;
			$data[$i][9]['table']=$hotel_paylog;
			$data[$i][9]['sql']=$sql_hotel_paylog;
		//	var_dump($data);exit
	//		self::$Model->get_orm()->queryAll();
		}
		if(!empty($data)){
		foreach($data as $kk=>$vv){
			foreach($vv as $k=>$v){
				$sql="show tables like '".$v['table']."'";
			 	$status=self::$Model->get_orm()->queryAll($sql);
				try{
				//	if($status==false || !$status){//创建表
						
						$r=self::$Model->get_orm()->queryAll($v['sql']);//创建表
						
						//查询表是否已被创建
						$sql="show tables like '".$v['table']."'";
						$status=self::$Model->get_orm()->queryAll($sql);
						
				 		if($status==false || !$status){
				 			var_dump($v['sql']);
				 			echo $sql;
				 			var_dump($status);exit;
							return false;
				 		}	
		 		//	}else{
		 			//	echo '已生成';exit;
		 		//	}
				}catch(Exception $e){
					var_dump($e);exit;
					echo '出错';exit;
				}
			 }
		}
	}
	return true;
	}
}
