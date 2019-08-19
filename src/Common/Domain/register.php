<?php
namespace Common\Api;

use Api\Model\Credit as ModelCredit;
class Register
{
   	private static $Model = null;
	public $flag_str="-";
	public $table="mdhr";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
    public function __construct()
    {
        if (self::$Model == null) {
            self::$Model = new ModelCredit();
        }
    }
	/**
	 *@return type 1 充值 2提现 3支付  4邀请好友
	 * 
	 * 
	 * 
	 */
	public function add_credit($mid,$credit,$money,$type){
		//添加积分
		if($credit=='integral'){
			
		}
		//添加余额
		if($credit=='credit'){
			
		}
	}
}