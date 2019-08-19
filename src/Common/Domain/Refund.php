<?php
namespace Common\Domain;
use Common\Common\CommonDomain;
use Common\Model\Refund as ModelRefund;
use Common\Domain\Log as ModelLog;
use Common\Domain\Credit;
use Mobile\Domain\User\Coupon;
use Mobile\Model\User\AddOrder;
use Mobile\Domain\User\AddOrder as AddOrderDomain;
class Refund extends CommonDomain
{
   	private static $Model = null;
	private static $LogModel=null;
	public $flag_str="-";
	public $table="mdhr";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
	public $table_id=0;
    public function __construct()
    {
        if (self::$Model == null) {
          	 self::$Model = new ModelRefund();
        }
		 if (self::$LogModel == null) {
          	 self::$LogModel = new ModelLog();
        }
    }
	public function refund_order($param){
		
		$id=$param['id'];
		$re=self::$Model->table("_order_refund")
					->where(" id =".$id)
					->fetch();						
		if($re['status']==1){
			$this->error='当前用户已退款不需要再进行该操作';
			return false;
		}
		$refund_goods=self::$Model->table("_order_refund_goods")
					->field("id,num,status,goodsid,orderid,storeid")
					->where('refund_id='.$re['id'])
					->fetchAll();		
		$mid=$re['mid'];
			
		$this->table_id=$this->getuser_id($mid);
		$table_id=$this->table_id;
		//再次进行核 对是否产品达到最大退款数量
		$goods_num=self::$Model->table("_order_refund_goods")
					->field("sum(num) num,goodsid")
					->where('orderid='.$re['orderid']." and status=1 group by goodsid")
					->fetchAll();
		if(!empty($goods_num)){			
			$orderModel=new AddOrder();
			foreach($goods_num as $k=>$v){
			
				$goods=$orderModel->table("_order_details_".$table_id)
						   ->field('num,refund_num,use_num,kou_num')
						   ->where('id = '.$v['goodsid'])
						   ->fetch();
				if(!$goods){
					$this->error='未查找到要退款的门票数据';
					return false;
				}
				if($goods['num']<($v['num']+$goods['use_num']+$goods['kou_num'])){
					$this->error='当前门票购买数据出错，如需继续退款，请连接管理人员进行核查';
					return false;
				}
			}
		}
		site_refund_insert_message('检则数据完成'.microtime(),'refund_data');
		$flag=self::refund_fan_money($re,$refund_goods);
		return $flag;
	}

	public function refund_fan_money($re,$refund_goods){
		$money=$re['real_money'];
		$mid=$re['mid'];
		$orderModel=new AddOrder();
		$table_id=$this->table_id;
		$order_data=$orderModel->table("_order_".$table_id)
				   ->alias(' o ')
				   ->field("o.id,m.from_user,p.transid,o.paytype,o.ordersn,o.totalprice,oo.coupon_id,oo.integral,oo.balance,
				   oo.rmb,oo.refund_coupon_status,oo.refund_integral_status")
				   ->join(TABLE."_paylog_".$table_id." p on p.tid=o.id")
				   ->join(TABLE."_order_or_".$table_id." oo on oo.orderid=o.id")
				   ->join(TABLE."_member m on m.id=o.mid")
				   ->where(" o.id = ".$re['orderid'])
				   ->fetch();   
		if(!$order_data['transid'] && $order_data['paytype']!=4){
			$this->error='获取支付单号失败无法进行退款操作';return false;
		}
		if($money<0.01){
			$this->error='最后实际退款金额小于0.1不能进行该操作';return false;
		}
		$order_data['suboid']=$re['refund_ordersn'];
		site_refund_insert_message('进入支付判断'.microtime(),'refund_data');
		switch($order_data['paytype']){
			case 1://微信
				$wx=self::get_wx();
				$pay=self::refund_wechat($order_data,$re,$wx,$money,$data,$add_score);
				//退款失败更新用户退款状态并声明失败原因
				break;
			case 2://支付宝
				$alipay=self::get_alipay();
				$pay=self::refund_alipay($order_data,$re,$alipay,$money,$data,$add_score);
				//支付宝
				break;
			case 3://小程序
				$wx=self::get_wx();
				$pay=self::refund_wechat($order_data,$re,$wx,$money,$data,$add_score);
				//退款失败更新用户退款状态并声明失败原因
				break;
			case 4:	//余额
				$pay=self::refund_balance($order_data,$re,$refund_goods,$money);
				//支付宝
				break;
			case 5:	
				//数字王府井
				$wang=self::get_wang();
				$pay=self::number_wang($order_data,$re,$wang,$money,$data,$add_score);
				//支付宝
				break;	
		}
		return $pay;
	}
	public function refund_insert_logs($order,$re,$refund_goods,$money){
		//1.更新订单退款数量和退款数据状态
		$orderModel=new AddOrder();
		$table_id=$this->table_id;
		$mid=$re['mid'];
		if($re['totalprice']!=$re['real_money']){
			$kou_money=$re['totalprice']-$re['real_money'];
			$msg="退款手续费为：".$kou_money;
		}
		foreach($refund_goods as $k=>$v){
			$goods=$orderModel->table('_order_details_'.$table_id)
					   ->where('id = '.$v['goodsid']." and orderid=".$v['orderid'])
					   ->fetch();
			$where=['id'=>$goods['id'],'orderid'=>$goods['orderid']];
			$up=[
				'kou_num'=>$goods['kou_num']-$v['num'],
				'refund_num'=>$v['num']+$goods['refund_num']
			];
			//2.更新子订单退款数量
			$goods_r=$orderModel->edit($up,$where);
			//2.更新退款订单的状态   
			self::$Model->table="order_refund_goods";
			$refund_r=self::$Model->edit(['status'=>1],['id'=>$v['id']]);
			if(!$goods_r || !$refund_r){
				$this->error="更新订单预扣除数量失败";
				return false;
			}
		}
		//2.更新退款主订单数据
		self::$Model->table="order_refund";
		$refund_order_r=self::$Model->edit(['status'=>1],['id'=>$re['id']]);
		if(!$refund_order_r){
			$this->error="更新退款主订单状态失败";return false;
		}
		//3.更新用户积分
		if($order['integral']>0 && $order['refund_integral_status']==0){
			$money=$this->refund_integral($mid,$order,$re,$money);
			if($money==false) return false;
		}
		$msg="";
		//4.更新用户优惠卷
		if($order['coupon_id']>0 && $order['refund_coupon_status']==0){
			$money=$this->refund_coupon($mid,$order,$re,$money);
			$msg="其中优惠卷已正常退还";
			if($money==false) return false;
		}
		//5.存储订单退款日志
		if($order['balance']>0){
			$type="balance";
		}elseif($order['rmb']>0){
			$type="rmb";
		}else{
			$type="or_dontno";//不知道什么类型但不应该出现
		}
		$log_data=[
			'type'=>2,
			'credit'=>$type,//根据用户下单是用余额还是现金来处理
			'refund_id'=>$re['id'],
			'orderid'=>$order['id']
		];
		$log=self::$LogModel->select_logs($mid,$log_data);
		if($log){
			$this->error="该订单已存在日志请核实该订单是否已退款";return false;
		}
		$insert_log_integral=[
			'type'=>2,
			'credit'=>$type,
			'orderid'=>$order['id'],
			'msg'=>'用户申请退款 平台退还用户金额：'.$money.$msg,
			'money'=>$money,
			'refund_id'=>$re['id']
		];
		$log_integral=self::$LogModel->add_logs($mid,$insert_log_integral);
		if($log_integral==false){
			$this->error=self::$LogModel->error;return false;
		}
		return $money;
	}

	public function refund_coupon($mid,$order,$re,$money){
		//1.获取当前优惠卷
		$coupon=new Coupon();
		$coupon_data=$coupon->getCouponById(['mid'=>$mid,'id'=>$order['coupon_id'],'is_use'=>1],2);
		if($coupon_data==false)
		{
			$this->error=$coupon->error; 
			return false;
		}
		if($money<$coupon_data['money'])
		{
			$this->error="当前使用优惠卷金额大于单笔退款金额ERROR-1";
			return false;
		}
		$money=$money-$coupon_data['money'];
		$where=[
			'mid'=>$mid,
			'id'=>$order['coupon_id'],
		];
		$up=[
			'is_use'=>0,
			'whos_table'=>0,
			'orderid'=>0,
			'usetime'=>0
		];
		$coupon_up=$coupon->update_coupon($where,$up);
		if($coupon_up==false){
			$this->error=$coupon_error; return false;
		}
		//更新用户退款状态
		$addOrderDomain=new AddOrderDomain();
		$up_or=$addOrderDomain->update_or('order_or',['mid'=>$mid,'orderid'=>$order['id']],['refund_coupon_status'=>1]);
		if($up_or==false){
			$this->error=$addOrderDomain->error;return false;
		}
		return $money;
	}
	
	//退积分
	public function refund_integral($mid,$order,$re,$money){
		$credit=new Credit();
		if($money<$order['integral']){
			$this->error="订单积分使用金额不能大于单笔退款金额ERROR-1";return false;
		}
		$update_integral=$credit->add_credit($mid,'integral',$order['integral'],2);
		if($update_integral==false){
			$this->error=$credit->error;return false;
		}
		$money=$money-$order['integral'];
		//添加日志
		$insert_log_integral=[
			'type'=>2,
			'credit'=>'integral',
			'orderid'=>$order['id'],
			'msg'=>'用户申请退款优先退其中积分',
			'money'=>$order['integral'],
			'refund_id'=>$re['id']
		];
		$log_integral=self::$LogModel->add_logs($mid,$insert_log_integral);
		if($log_integral==false){
			$this->error=self::$LogModel->error;return false;
		}
		//更新用户退款状态
		$addOrderDomain=new AddOrderDomain();
		$up_or=$addOrderDomain->update_or('order_or',['mid'=>$mid,'orderid'=>$order['id']],['refund_integral_status'=>1]);
		if($up_or==false){
			$this->error=$addOrderDomain->error;return false;
		}
		return $money;
	}
	
	public function refund_balance($order_data,$re,$refund_goods,$money){
		$table_id=$this->table_id;
		$mid=$re['mid'];
//		$flag=0;
//		if(($order_data['coupon_id']>0 && $order_data['refund_coupon_status']==0)
//		 || ($order_data['integral']>0 && $order_data['refund_integral_status']==0)){
//			$flag=1;
//		}
		$money=self::refund_insert_logs($order_data,$re,$refund_goods,$money);
		if($money==false){
			return false;
		}
		$credit=new Credit();
		$credit_up=$credit->add_credit($mid,'credit',$money,2);
		if($credit_up==false){
			$this->error=$credit->error;return false;
		}else{
			$this->success="退款成功，资金已原路返还 .详情请进入日志查看";
//			if($flag==1){
//				$this->success.="其中优惠卷/积分已返还至帐户 积分请查看日志，优惠卷请进入优惠中心查看";
//			}
		}
		return true;
//		$content="你申请的退款已原路返回~~ 请查看";
//		$this->sendregistNotice22($order['form_user'],$content);
//		$data['result']='1';
//		$data['msg']='退款成功:退款金额'.$money."已原路返还".$data['msg'];
//		$this->site_refund_insert_message('存支付记录完成'.microtime(),'refund_data');
	}
	
	public function refund_wechat($order_data,$re,$refund_goods,$money){
		$table_id=$this->table_id;
		$money=self::refund_insert_logs($order_data,$re,$refund_goods,$money);
		if($money==false){
			return false;
		}
		//退用户余额
		
		//2.存储文件日志
		$str1=$this->do_array($code);
		site_refund_insert_logs($str1,$order['id'].'_'.$goods['id']);
		$str=json_encode($code);
		if($code->code==10000){
			$content="你申请的退款已原路返回~~ 请查看";
			$this->sendregistNotice22($order['form_user'],$content);
			$data['result']='1';
			$data['msg']='退款成功:退款金额'.$money."已原路返还".$data['msg'];
		}else{
			$data['result']='-1';
			$data['msg']='退款失败,失败原因:'.$str1;
		}
		if($data['result']==1){
			//记录用户退款订单号
			$datas=array(
				'transid'=>$code->trade_no,
				'refund_id'=>$goods['id'],
				'status'=>1,
				'time'=>time()
			);
			pdo_insert('yhg_refund_trans', $datas);
		}
		$this->site_refund_insert_message('存支付记录完成'.microtime(),'refund_data');
	}
}