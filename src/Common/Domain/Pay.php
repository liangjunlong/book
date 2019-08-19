<?php
namespace Common\Domain;
use Common\Common\CommonDomain;
use Common\Model\Pay as ModelPay;
use Mobile\Domain\User\User as ModelUser;
use function App\getip;
use function App\isimplexml_load_string;
class Pay extends CommonDomain
{
   	private static $Model = null;
	private static $userModel=null;
	public $flag_str="-";
	public $table="mdhr";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
    public function __construct()
    {
    		
		if (self::$userModel == null) {
          	 self::$userModel = new ModelUser();
        }
    }
	
	public function pay($param,$type,$paytype){
		$di = \PhalApi\DI();
		switch($paytype){
			case 'wechat':
				//获取不 同的支付参数
				$setting=$this->setting('select_pay_setting_fetch',['type'=>1]);
				$wx=$di->paylite->new_wexin(1,$setting);
				$code=$wx->weixin_pay($param,$type);
				if($code==false || $code['status']==-1){
					$this->msg=isset($code['msg'])?$code['msg']:'支付参数出错';
					return false;;
				}
				return $code;
			case 'alipay':
				break;
			case 'xcx':
				$setting=$this->setting('select_pay_setting_fetch',['type'=>3]);
				$wx=$di->paylite->new_wexin(3,$setting);
				$code=$wx->weixin_pay($param,$type);
				if($code==false || $code['status']==-1){
					$this->msg=isset($code['msg'])?$code['msg']:'支付参数出错';
					return false;
				}
				return $code;
				break;
			default:
				$this->msg="请传支付参数";
				break;			
		}
		return false;
	}
	
	 /**
     * 作用：生成签名
     */
    public	function getSign($Obj,$shkey)
    {
        //var_dump($Obj);//die;
        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);//方法如下
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String."&key={$shkey}";
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 作用：格式化参数，签名过程需要使用
     */
    public	function formatBizQueryParaMap($paraMap, $urlencode)
    {
        //var_dump($paraMap);//die;
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        //var_dump($reqPar);//die;
        return $reqPar;
    }

	
    //企业付款接口
    public function epay($params) {
    	$setting=$this->setting('select_pay_setting_fetch',$where='type = '.$params['type']);
		//1.查找是否设置支付参数
        $shkey	=$setting['mch_key'];
        $mch_appid=$setting['appid'];//公众账号appid
        $mchid=$setting['mch_id'];//商户号
        $nonce_str='qyzf'.rand(100000, 999999);//随机数
        $partner_trade_no='kk'.time().rand(10000, 99999);//商户订单号
        $spbill_create_ip=getip();//请求ip
        $check_name='NO_CHECK';//校验用户姓名选项，NO_CHECK：不校验真实姓名， FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账），OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
        $dataArr=array();
        $dataArr['amount']=$params['amount'];
        $dataArr['check_name']=$check_name;
        $dataArr['desc']=$params['desc'];
        $dataArr['mch_appid']=$mch_appid;//公众账号appid
        $dataArr['mchid']=$mchid;//商户号
        $dataArr['nonce_str']=$nonce_str;
        $dataArr['openid']=$params['openid'];
        $dataArr['partner_trade_no']=$partner_trade_no;
        $dataArr['spbill_create_ip']=$spbill_create_ip;
        //生成签名(是正确的)
        $sign=$this->getSign($dataArr,$shkey);//getSign($dataArr);见结尾
        //echo "-----<br/>签名：".$sign."<br/>*****";//die;
        //拼写正确的xml参数
        $data="<xml>
		<mch_appid>".$mch_appid."</mch_appid>
		<mchid>".$mchid."</mchid>
		<nonce_str>".$nonce_str."</nonce_str>
		<partner_trade_no>".$partner_trade_no."</partner_trade_no>
		<openid>".$params['openid']."</openid>
		<check_name>".$check_name."</check_name>
		<amount>".$params['amount']."</amount>
		<desc>".$params['desc']."</desc>
		<spbill_create_ip>".$spbill_create_ip."</spbill_create_ip>
		<sign>".$sign."</sign>
		</xml>";
		//记录支付日志
        //4、发出企业付款请求
        site_refund_insert_message("给用户打款".$data,'pay_money');
        $ch = curl_init ();
        $MENU_URL="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        curl_setopt ( $ch, CURLOPT_URL, $MENU_URL );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        //两个证书（必填，请求需要双向证书。）
        $zs1="../Library/app_pay/cert_xcx/apiclient_cert.pem";
        $zs2="../Library/app_pay/cert_xcx/apiclient_key.pem";
        curl_setopt($ch,CURLOPT_SSLCERT,$zs1);
        curl_setopt($ch,CURLOPT_SSLKEY,$zs2);
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        $info = curl_exec ( $ch );
        if (curl_errno ( $ch )) {
            echo 'Errno' . curl_error ( $ch );
        }
        curl_close ( $ch );
        $xml = isimplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA);
        site_refund_insert_message("打款回调",'pay_money');
		site_refund_insert_message(json_encode($xml),'pay_money');
        if($xml->return_code=='FAIL'){
            $result = array(
                'status'=>0,
                'info' => '支付通信不通！'
            );
            return $result;
        }else{
            if($xml->result_code == 'SUCCESS'){
                $result = array(
                    'status'=>1,
                    'payment_no'=>$xml->payment_no,
                    'partner_trade_no'=>$xml->partner_trade_no,
                    'payment_time'=>$xml->payment_time
                );
                return $result;
            }else{
                $result = array(
                    'status'=>0,
                    'info' => $xml->err_code_des
                );
                return $result;
            }
        }
    }
	
}