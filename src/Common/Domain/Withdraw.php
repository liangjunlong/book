<?php
namespace Common\Domain;
use Common\Common\CommonDomain;
use Common\Model\Withdraw as ModelWithdraw;
use Mobile\Domain\User\User as ModelUser;
use Common\Domain\Log as ModelLog;
use Common\Domain\Pay as ModelPay;
class Withdraw extends CommonDomain
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
          	 self::$Model = new ModelWithdraw();
        }
		if (self::$userModel == null) {
          	 self::$userModel = new ModelUser();
        }
    }
	public function check_param($param){
		if(!isset($param['id'])){
			$this->error="未查找到ID";
			return false;
		}
		return ['id'=>$param['id'],'withdraw_type'=>$param['withdraw_type']];
	}
	//开启自动提现功能
	public function automatic_withdraw($param){
		$data=self::check_param($param);
		if($data==false){
			return false;
		}
		extract($data);
		$setting=$this->setting('select_setting');
		//1.查找当前订单数据
		$withdraw_data=self::$Model->get_data("id = ".$id);
		$money=$withdraw_data['real_money'];
		if($withdraw_data['status']>0){
			$this->error="当笔交易不能进行提现操作";
			return false;
		}
		//2.根据此参数获取用户openid
		$member=self::$userModel->getMemberById($withdraw_data['mid']);
		$type=self::gettype($member);
		$params=array(
			'openid'=>$member['from_user'],
			'amount'=>intval($money*100),
			'type'=>$type,//代表当前是什么打款
			'desc'=>"用户提现，打款给用户"
		);
		//存储打款日志
		$log_id=$this->insert_log($withdraw_data,$param);
		if($log_id==false){
			return false;
		}
		//更改订单状态
		$up_r=self::$Model->updateEdit(['withdraw_type'=>$param['withdraw_type'],'status'=>2],['id'=>$param['id']]);
		if(!$up_r){
			$this->error="更新打款状态失败 无法提现";
			return false;
		}
		$pay=new ModelPay();
		$epay=$pay->epay($params);
		if($epay['status']==0){
			//失败记录失败原因
			$this->error=$epay['info'];
			return false;
		}else{
			$this->success="支付成功";
			site_refund_insert_message(json_encode($epay),'withdraw_'.$param['id']);
			return $epay;
		}
	}

	public function insert_log($withdraw_data,$param){
		$log=new ModelLog();
		$insert_log=[
			'money'=>$withdraw_data['real_money'],
			'withdraw_id'=>$withdraw_data['id'],
			'msg'=>"商家付款,提现金额:".$withdraw_data['money']." 实际到帐:".$withdraw_data['real_money'],
			'type'=>4,
			'mid'=>$withdraw_data['mid'],
			'credit'=>'money'
		];
		$r=$log->add_logs($withdraw_data['mid'],$insert_log);
		if($r==false){
			$this->error="存储打款日志出错";
			return false;
		}
		return true;
	}
	public function gettype($member){
		$type=0;
		if($member['api']==0 || $member['api']==4){
			return 3;
		}
		return $member['api'];
	}
}