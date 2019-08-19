<?php
namespace Common\Domain;
use Common\Common\CommonDomain;
use Common\Model\Reward as ModelReward;
use Common\Domain\Log as ModelLog;
use Common\Domain\Credit;
use Common\Domain\Coupon;
class Reward extends CommonDomain
{
   	private static $Model = null;
	private static $LogModel=null;
	public $flag_str="-";
	public $table="mdhr";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
    public function __construct()
    {
        if (self::$Model == null) {
          	 self::$Model = new ModelReward();
        }
		 if (self::$LogModel == null) {
          	 self::$LogModel = new ModelLog();
        }
    }
	public function check_array_data($data,&$code){
		if(!isset($data['type'])){
			$code="类型参数不能为空";
		}

		return $data;
	}
	//检测是否充值赠送金额
	public function check_addmoney($mid,$insert,$setting){
		$add_money=$insert['money']*$setting['integral_money']/100;
		if($add_money>0){
			$r=$integral->add_credit($mid,$insert['credit'],$insert['money'],$insert['type'],$msg="");
			if($r){
				$insert['add_money']=$add_money;
				$insert['msg']=" 另赠送:".$add_money.$insert['credit'];
			}
		}
		return $insert;
	}
	public function insert_data($mid,$reward_data,$type,$add_type,$msg_data){
	
		if($add_type==0){ //积分integral_money
			$insert['money']=$reward_data['integral_money'];
			$insert=[
				'credit'=>'integral',
				'money'=>$reward_data['integral_money'],//默认赠送这么多
				'type'=>$type,
			];
		}else{//余额balance
			$insert=[
				'credit'=>'credit',
				'money'=>$reward_data['credit'],//默认赠送这么多
				'type'=>$type,
			];
		}
		//如果用户 开启赠送百分比 或者当前是注册
		if($reward_data['status']==1 && $type==3){
			if($type==0){		
				$insert['money']=$reward_data['integral_money']*$money/100;
			}else{
				$insert['money']=$reward_data['integral_money']*$money/100;
			}
		}
		$insert['msg']=$msg_data."赠送:".$insert['money'].$insert['credit'];
		$insert['msg']=str_replace('balance', "余额", $insert['msg']);
		$insert['msg']=str_replace('integral', "积分", $insert['msg']);
		$integral=new Credit();
		$msg="";
		$insert_add_r=$integral->add_credit($mid,$insert['credit'],$insert['money'],$type,$msg);
		$insert_logs_r=self::$LogModel->add_logs($mid,$insert,$msg=""); 
		if(!$insert_add_r || !$insert_logs_r){
			return false;
		}
		return true;
	}
	/**
	 *@return type 1 支付 2退款 3 充值  4提现   5邀请好友 6注册 7预留  三级分销
	 * 
	 * 赠送公用方法 邀请好友 注册 充值
	 * 
	 */
	public function add_reward($mid,$array_data,&$msg){
		$setting_data=$this->setting('select_setting');
		$flag=0;
		$type_data=[];
		$code;
		extract(self::check_array_data($array_data,$code));
		if($code){
			$msg=$code;
			return false;
		}
		if(!$array_data['type']){
			return false;
		}
		$msg_data="";
		switch($type){
			case 3://充值
				$type_data['type']=2;
				$msg_data="用户充值";
				break;
			case 5: //邀请好友
				$type_data['type']=1;
				$msg_data="用户邀请好友";
				break;
			case 6://注册
				$type_data['type']=3;
				$msg_data="用户注册";
				break;
			default:
				$flag=1;
				break;		
		}
		if($flag==1){
			$msg="未获取要赠送的类型";
			return false;
		}
		//获取平台设置的赠送设置
		$reward_data=$this->setting('select_topup_setting',$type_data);
		if($type==3 && $reward_data==1){ //如果是充值 查看是否是按百分比来赠送
			$reward_data['credit']=$money*$reward_data['credit']/100;
			$reward_data['integral_money']=$money*$reward_data['integral_money']/100;
		}
		switch($setting_data['setting_register_type']){
			case 0: //赠送积分
				if($reward_data['integral_money']<=0){
					$msg="平台未开启赠送的积分金额";
					$flag=1;
				}else{
					$r=self::insert_data($mid,$reward_data,$type,$add_type=0,$msg_data);
				}
				break;
			case 1://赠送余额
				if($reward_data['credit']<=0){
					$msg="平台未设置赠送的余额金额";
					$flag=1;
				}else{
					$r=self::insert_data($mid,$reward_data,$type,$add_type=1,$msg_data);
				}
				 break;
			case 2://赠送优惠卷
				//最后写
					$Coupon=new Coupon();
					$r=$Coupon->giving_coupon($mid,$setting_data['setting_add_coupon_status'],$reward_data,$type_data['type'],$msg_data);
				break;
			default:
				$flag=1;		
		}
		if(!$r){
			$falg=1;
		}
		if($flag==1){
			return false;
		}
		return true;
		
		
	}
}