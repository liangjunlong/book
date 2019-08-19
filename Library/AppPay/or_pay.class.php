<?php
class pay{
	public $ceshi_url="https://qr.chinaums.com/netpay-route-server/api/";//"https://qr-test2.chinaums.com/netpay-route-server/api/";
	public $url="https://qr.chinaums.com";
	public $mid=898110273922636;
	public $tid=77189227;
	public $refund_url="";
	public $returnUrl="";
	public $msgSrcId=5079;
	public $key="Q8Rarz3pGwDK2iRSXDC2THK8syGneNkWDeKc7dx2eFSZ6zAs";
	public $msgSrc="WWW.TEST.COM";
	
	public function __construct (){
		global $_W,$_GPC;
		$sql="select app_payment,payment from ".tablename('uni_settings')." where uniacid=5";
		$data=pdo_fetch($sql);
		$number=unserialize($data['payment'])['number'];
		if($number){
			$this->mid=$number['mid'];
			$this->tid=$number['tid'];
			$this->msgSrcId=$number['msgSrcId'];
			$this->key=$number['key'];
			$this->msgSrc=$number['msgSrc'];
		}
	}
	public function get_pay($params){
		global $_W,$_GPC;
		$data=self::check_data($params);
		return $data;
	}
	
	public function getordersn(){
		$ordersn=$this->msgSrcId.date('YmdHis',time()).mt_rand(10000,99999);
		return $ordersn;
	}
	
	public function check_data($params){
		$time=time();
		$data=array(
			'msgSrc'=>$this->msgSrc,
			'msgType'=>'bills.getQRCode',
			'requestTimestamp'=>date('Y-m-d H:i:s',$time),
			'mid'=>$this->mid,
			'tid'=>$this->tid,
			'instMid'=>'QRPAYDEFAULT',
			'signType'=>'MD5',
			'billNo'=>$this->msgSrcId.$params['ordersn'],
			'billDate'=>date('Y-m-d',$time),
			'totalAmount'=>$params['total_amount']*100,
		//	'divisionFlag'=>false,
			'memberId'=>$params['out_trade_no'],
			'notifyUrl'=>'https://'.$_SERVER['HTTP_HOST']."/or_pay.php",
			'returnUrl'=>'https://'.$_SERVER['HTTP_HOST']."/refund.php",
			'walletOption'=>'SINGLE',
		);
	//	var_dump($data);exit;
		$data['sign']=$this->getSign($data);
	//	echo json_encode($data);exit;
		$result=$this->curl($this->ceshi_url,$data);
	//	var_dump($result);exit;
		$data_result=json_decode($result,true);
	//	var_dump($data_result);exit;
		if($data_result['qrCodeId']){
			$data_result['result']='1';
		}else{
			$data_result['msg']=$data_result['errMsg'];
			$data_result['result']='-1';
		}
		return $data_result;
		
	}
	
	protected function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //有post参数-设置
        if (is_array($postFields) && 0 < count($postFields)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        }

        $header[] = "Content-type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $reponse = curl_exec($ch);
      //  var_dump($reponse);exit;
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);

        return $reponse;
    }
	public function getSign($data){
		$obj=$data;
		foreach ($obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
		$String=$this->formatBizQueryParaMap($Parameters);
		$sign = md5(trim($String));
		return $sign;
	}
	
	 public function formatBizQueryParaMap($paraMap, $urlencode=""){
        //sign不参与计算
        $params['sign'] = '';

        //排序
        ksort($paraMap);

        $paramsToBeSigned = [];
       
        foreach ($paraMap as $k=>$v) {
    		if(is_array($paraMap[$k])){
    			$v = json_encode($v,JSON_UNESCAPED_UNICODE);
    		}else if(trim($v) == ""){
				continue;
			}
    		$paramsToBeSigned[] = $k.'='. $v;
        }
        unset ($k, $v);

        //签名字符串
        $stringToBeSigned = (implode('&', $paramsToBeSigned));
		//str_replace('¬','&not',$stringToBeSigned);
        $stringToBeSigned .= $this->key;
        return $stringToBeSigned;
    }
	function verify($data) {
        //返回参数生成sign
        $signType = empty($data['signType']) ? 'md5' : $data['signType'];
		var_dump($data);
        $sign = $this->getSign($data);
		var_dump($sign);
		echo '<br/>';
		echo $data['sign'];exit;
        //返回的sign
        $returnSign = $data['sign'];
        if ($returnSign != $sign) {
            return false;
        }

        return true;
    }
	/*
	 *退款 
	 * 
	 * 
	 **/
	public function refund($order,$re,$money){
		$time=time();
		$data=array(
			'msgSrc'=>$this->msgSrc,
			'msgType'=>'bills.refund',
			'requestTimestamp'=>date('Y-m-d H:i:s',$time),
			'mid'=>$this->mid,
			'tid'=>$this->tid,
			'instMid'=>'QRPAYDEFAULT',
			'signType'=>'MD5',
			'billDate'=>date('Y-m-d',$time),
			'billNo'=>$order['transid'],
			'refundOrderId'=>$order['suboid'],
			'refundAmount'=>$money*100,
		);
		$data['sign']=$this->getSign($data);
		$result=$this->curl($this->ceshi_url,$data);
		$data_result=json_decode($result,true);
		
		if($data_result['errCode']=='SUCCESS' && $data_result['billStatus']=='REFUND'){
			$data_result['result']='1';
		}else{
			$data_result['result']='-1';
		}
		return $data_result;
	}
	
	public function select_order_status($ordersn,&$result){
		$time=time();
		if(substr($ordersn,0,4)!=$this->msgSrcId){
			$ordersn=$this->msgSrcId.$ordersn;
		}
		$data=array(
			'msgSrc'=>$this->msgSrc,
			'msgType'=>'bills.query',
			'requestTimestamp'=>date('Y-m-d H:i:s',$time),
			'mid'=>$this->mid,
			'tid'=>$this->tid,
			'instMid'=>'QRPAYDEFAULT',
			'signType'=>'MD5',
			'billNo'=>$ordersn,
			'billDate'=>date('Y-m-d',$time),
		);
		$data['sign']=$this->getSign($data);
		$results=$this->curl($this->ceshi_url,$data);
		
		$data_result=json_decode($results,true);
	
		if(isset($data_result['billPayment']['status']) && $data_result['billPayment']['status']=='TRADE_SUCCESS'){
			$result['result']='1';
			$result['msg']='成功请重新进入订单页面查看';
			//去调接口
			$flag=$this->notify_select($data_result,$result);
			return $flag;
		}else{
			$result['result']='-1';
			if(!empty($data_result['errMsg'])){
				$result['msg']=$data_result['errMsg']."请确认该订单是否已支付";
			}else{
				$result['msg']="未支付";
			}
			return false;
		}
	}
	
	public function notify_select($params,&$result){
		$out_trade_no = $params['memberId'];
		//支付宝交易号
		$trade_no = $params['billPayment']['merOrderId'];
		//交易状态
		$total_fee=$params['totalAmount']/100;
		message1($file='total_fee');
		if($params['billPayment']['status']=='TRADE_SUCCESS' && $params['billStatus'] == 'PAID'){
	    	message1('进来128');
	    	$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE tid='.$out_trade_no;
			$log = pdo_fetch($sql);
			message1($log);
			message1($sql);
			message1($total_fee);
			if($log['fee']!=$total_fee){
				$result['result']='-1';
				$result['msg']='支付金额和订单金额不相等！';
				return false;
			}
			if(!empty($log)) {
				$log['transaction_id'] = $trade_no;
				$record = array();
				$record['status'] = '1';
				$record['uniontid']=$params['billNo'];
				pdo_update('core_paylog', $record, array('plid' => $log['plid']));
				$f=pdo_fetch("select status,totalprice from ".tablename('yhg_order')." where id='{$log['tid']}'");
				message1($f);
				if($f['status']!=0){
					$result['result']='-1';
					$result['msg']='订单状态已支付，无法进行该操作';
					return false;
				}
				$site = WeUtility::createModuleSite($log['module']);
				if($log['module']=='yhg'){
						$action='pay';
						$file=IA_ROOT.'/addons/'.$log['module'].'/inc/app/' . strtolower($action) . '.class.php'; 
						include_once $file;
						$classname = "payModuleSite";
						$log['result'] = 'success';
						$log['tag'] = $tag;
						if($params['billPayment']['targetSys']=='WXPay'){
							$log['pay_status']=1;
						}elseif($params['billPayment']['targetSys']=='Alipay 1.0' || $params['billPayment']['targetSys']=='Alipay 2.0'){
							$log['pay_status']=2;
						}else{
							$log['pay_status']=3;
						}
						$log['payTime']=$params['billPayment']['payTime']?$params['billPayment']['payTime']:date('Y-m-d H:i:s',time());
						$method = 'payResult';
						$obj=new $classname();
						$obj->__define=$site->__define;
						if (method_exists($obj, $method)) {
							message1($f='进来129');
							$fs=$obj->$method($log,$api=1);
							message1('回调返回');
						}
				}
				if($fs==false){
					$result['msg']='更改订单数据失败';
					$result['result']='-1';
				}
				return $fs;
			}
	    }
	    $result['result']='-1';
		$result['msg']='查询订单返回状态不对';
		return false;
	
}
	/*
	 * 支付回调
	 * 
	 **/
	public function notify($params){
			$out_trade_no = $params['memberId'];
			//支付宝交易号
			$trade_no = $params['billPayment']['merOrderId'];
			//交易状态
			$total_fee=$params['totalAmount']/100;
			message1($file='total_fee');
			if($params['billPayment']['status']=='TRADE_SUCCESS' && $params['billStatus'] == 'PAID'){
		    	message1('进来128');
		    	$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE tid='.$out_trade_no;
				$log = pdo_fetch($sql);
				message1($log);
				message1($sql);
				message1($total_fee);
				if($log['fee']!=$total_fee){
					echo 'failed';exit;
				}
				if(!empty($log) && $log['status'] == '0') {
					$log['transaction_id'] = $trade_no;
					$record = array();
					$record['status'] = '1';
					$record['uniontid']=$params['billNo'];
					pdo_update('core_paylog', $record, array('plid' => $log['plid']));
					$f=pdo_fetch("select status,totalprice from ".tablename('yhg_order')." where id='{$log['tid']}'");
					message1($f);
					if($f['status']!=0){
						echo "failed";exit;
					}
					$site = WeUtility::createModuleSite($log['module']);
					if($log['module']=='yhg'){
							$action='pay';
							$file=IA_ROOT.'/addons/'.$log['module'].'/inc/app/' . strtolower($action) . '.class.php'; 
							include_once $file;
							$classname = "payModuleSite";
							$log['result'] = 'success';
							$log['tag'] = $tag;
							if($params['billPayment']['targetSys']=='WXPay'){
								$log['pay_status']=1;
							}elseif($params['billPayment']['targetSys']=='Alipay 1.0' || $params['billPayment']['targetSys']=='Alipay 2.0'){
								$log['pay_status']=2;
							}else{
								$log['pay_status']=3;
							}
							$log['payTime']=$params['billPayment']['payTime']?$params['billPayment']['payTime']:time();
							$method = 'payResult';
							$obj=new $classname();
							$obj->__define=$site->__define;
							if (method_exists($obj, $method)) {
								message1($f='进来129');
								$fs=$obj->$method($log,$api=1);
								message1('回调返回');
							}
					}
					message1('进来1290');
					message1($fs);
					if($fs==false){
						message1('错误');
						echo "failed";exit;
					}else{
						message1('成功');
						echo "success";	exit;
					}
				}
					message1($f='进来1291');
					echo "success";	
		    	}
					echo "success";		//请不要修改或删除
	
//			else{
//			$data='1111111111'; 
//			message1($data='任务失败');
//			echo "sign fail";exit;
//		}
//			echo 'success';
		}
		public function __destruct(){
			
		}
}
if(!function_exists('message1')){
	function message1($data){
		$str="";
		if(is_array($data)){
		foreach($data as $k=>$v){
			$str.=$k."=".$v.',';
			}
		}else{
			$str=$data;
		}
		$str.='.............................................'."<br/>";
		$date=date('Y-m-d',time());
		$file="";
		$file=IA_ROOT."/api_error/$date/";
		if(!file_exists($file)){
			$re=mkdir($file,0777,true);
		}
		$time=time();
		$file.="2.txt";
		$handle=fopen($file, 'a+');
		$str.="\r\n";
		fwrite($handle,$str);
		fclose($handle);
		return true;
	}
}
?>