<?php
namespace Common\Domain;
use Common\Common\CommonDomain;
use Common\Model\Coupon as ModelCoupon;
use Mobile\Domain\User\User as ModelUser;
use Common\Domain\Log as ModelLog;
class Coupon extends CommonDomain
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
          	 self::$Model = new ModelCoupon();
        }
		if (self::$userModel == null) {
          	 self::$userModel = new ModelUser();
        }
    }
	/**
	 * @return type 1 支付 2退款 3 充值  4提现   5邀请好友 6注册 7预留  三级分销
	 * 
	 * 赠送优惠卷公有接口
	 * 
	 */
	 public function giving_coupon($mid,$giving_status,$reward_data,$type,$msg_data){
	 		if(empty($reward_data['coupon'])){
	 			site_refund_insert_message($mid.":未查找到需要奖励的优惠卷",'giving_coupon');	
	 			return false;
	 		}
			foreach($reward_data['coupon'] as $k=>$v){
				//如果已领取光并且该优惠卷已领取完后不能进行正常领取
					if($v['num']==$v['use_num'] && $reward_data['coupon']==0){
						//存储消息日志
						site_refund_insert_message($mid.":赠送用户优惠卷失败 当前优惠卷张数:".$v['num']."|已使用：".$v['use_num'],'giving_coupon');
						continue;
					}else{
						$data=array(
							'coupon_id'=>$v['coupon_id'],
							'money'=>$v['money'],
							'createtime'=>time(),
							'type'=>$type,
							'mid'=>$mid
						);
						self::$Model->table="user_coupon";
						$id=self::$Model->insert($data);
						self::$Model->table="coupon";
						$up['use_num']=$v['use_num']+1;
						$r=self::$Model->edit($up,['coupon_id'=>$v['coupon_id']]);
						if(!$id || !$r){
							site_refund_insert_message($mid.":添加用户优惠卷出错",'giving_coupon');
							return false;
						}	
					}		
			}
			return true;
		
	 }
	
}
	