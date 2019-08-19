<?php
namespace Common\Domain;
use Common\Common\CommonDomain;
use Common\Model\Credit as ModelCredit;
use Mobile\Domain\User\User as ModelUser;
use Common\Domain\Log as ModelLog;
class Credit extends CommonDomain
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
          	 self::$Model = new ModelCredit();
        }
		if (self::$userModel == null) {
          	 self::$userModel = new ModelUser();
        }
    }
	/**
	 * @return type 1 支付 2退款 3 充值  4提现   5邀请好友 6注册 7预留  三级分销
	 * 
	 * 
	 * 
	 */
	//更新用户积分或者余额 
	public function add_credit($mid,$credit,$money,$type,&$msg="",$flag=0){
		$member=self::$userModel->getMemberFromId($mid);
		//添加积分 flag等于1是可以强制扣 最后计算结果小于0
		if($credit=='integral'){
			$integral=$member['integral']+$money;
			if($integral<0 && $flag!=1){
				$msg="当前积分金额小于0不能进行操作";
				$this->error=$msg;
				return false;
			}
			$up=['integral'=>$integral];
		}
		//添加余额
		if($credit=='credit'){
			$balance=$member['balance']+$money;
			if($balance<0){
				$msg="当前积分金额小于0不能进行操作";
				$this->error=$msg;
				return false;
			}
			$up=['balance'=>$balance];
		}
		self::$Model->table_name="member_info";
		$re=self::$Model->UserEdit($up,['mid'=>$mid]);
		//给redis发送信号 然后单独处理日志
		if(!$re){
			$msg="更新用户积分失败";
			$this->error=$msg;
			return false;
		}
		$log=new ModelLog();
		$log_r=$log->insert_member_log($mid,$credit,$money,$type,$msg);
		if(!$log_r){
			return true;
		}
		//存储日志记录资金流水
		return true;
	}	
}