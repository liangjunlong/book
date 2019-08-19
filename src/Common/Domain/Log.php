<?php
namespace Common\Domain;
use Common\Common\CommonDomain;
use Common\Model\Log as ModelLog;
use Mobile\Domain\User\User as ModelUser;
class Log extends CommonDomain
{
   	private static $Model = null;
	private static $userModel=null;
	public $flag_str="-";
	public $table="mdhr";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
    public function __construct()
    {
    	
        if (self::$Model == null) {
          	 self::$Model = new ModelLog();
        }
		if (self::$userModel == null) {
          	 self::$userModel = new ModelUser();
        }
    }
	public function get_param($param,$mid){
		$where=" 1 = 1 ";
		if(!isset($mid)){
			$this->error="请传用户ID";
			return false;
		}
		if(isset($param['id'])){
			$where.=" and id = ".$param['id'];
		}
		if(isset($param['credit'])){
			$where.=" and credit = '".$param['credit']."'";
		}
		if(isset($param['type'])){
			$where.=" and type = ".$param['type'];
		}
		if(isset($param['orderid'])){
			$where.=" and orderid=".$param['orderid'];
		}
		if(isset($param['topup_id'])){
			$where.=" and topup_id=".$param['topup_id'];
		}
		if(isset($param['refund_id'])){
			$where.=" and refund_id=".$param['refund_id'];
		}
		if(isset($param['withdraw_id'])){
			$where.=" and withdraw_id=".$param['withdraw_id'];
		}
		if(isset($param['topup_id'])){
			$where.=" and topup_id=".$param['topup_id'];
		}
		if(isset($param['agentid'])){
			$where.=" and agentid=".$param['agentid'];
		}
		if(isset($param['storeid'])){
			$where.=" and storeid=".$param['storeid'];
		}
		return $where;
	}
	public function select_logs($mid,$param){
		$where=self::get_param($param,$mid);
		if($where==false){
			return false;
		}
		self::$Model->table="pay_log";
		$data_log=self::$Model->get_orm($mid)->where($where)->fetch();
		if(!$data_log){
			$this->error="未查找到数据";
			return false;
		}
		return $data_log;
	}
	/**
	 *@return type 1 支付 2退款 3 充值  4提现   5邀请好友 6注册 7预留  三级分销
	 * @desc $business_data 为需要存储的参数数组 
	 * @Desc mid 用来进行分表操作
	 * 
	 * 
	 */
	public function add_logs($mid,$business_data,$msg=""){
		if(START_REDIS_MSG){//开启消息使用redis队列发送 到一个socket连接 然后返回
			// $di = \PhalApi\DI();
			$di = \PhalApi\DI();
			$data=['mid',$business_data];
			$di->cache_redis->lPush('log',$data);
			return true;
		}else{
			self::$Model->table="pay_log";
			$insert_data=$business_data;
			$insert_data['mid']=$mid;
			$insert_data['time']=time();
			$id=self::$Model->get_orm($mid)->insert($insert_data);
			
			//存储索引表
			if($id){
				$id=self::$Model->get_orm($mid)->insert_id();
				$flag=self::insert_index($mid,$id,$insert_data);
				if($flag==false){
					$msg="存储索引表失败";
					$this->error=$msg;
					return false;
				}
				return $id;
			}
		}
		return false;
	}
	
	public function insert_index($mid,$id,$param){

		$datas=[
			'table_id'=>$mid%REMOVED,
			'log_id'=>$id,
			'time'=>time()
		];

		if(isset($param['storeid'])){
			$datas['storeid']=$param['storeid'];
		}
		if(isset($param['withdraw_id'])){
			$datas['withdraw_id']=$param['withdraw_id'];
		}
		if(isset($param['credit'])){
			$datas['credit']=$param['credit'];
		}
		if(isset($param['mid'])){
			$datas['mid']=$param['mid'];
		}
		if(isset($param['type'])){
			$datas['type']=$param['type'];
		}
		self::$Model->table="log_index";
		$r=self::$Model->insert($datas);
		if(!$r){
			site_refund_insert_message("记录日志失败 数据为:".$mid.$credit.$money.$type.$msg,'logs');
			$msg="存储日志失败";
			$this->error=$msg;
			return false;
		}
		return true;
	}
	
	public function insert_member_log($mid,$credit,$money,$type,&$msg=""){
		$data=[
			'mid'=>$mid,
			'credit'=>$credit,
			'money'=>$money,
			'type'=>$type,
			'time'=>time()
		];
		self::$Model->table="member_log";
		$r=self::$Model->insert($data);
		if(!$r){
			site_refund_insert_message("记录日志失败 数据为:".$mid.$credit.$money.$type.$msg,'logs');
			$msg="存储日志失败";
			return false;
		}
		return true;
	}
}