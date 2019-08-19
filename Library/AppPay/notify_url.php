<?php
define('IN_API', true);
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：1.0
 * 日期：2016-06-06
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */
	 function get_alipay_return_data(){
	 	message1('进入----22');
	 	include IA_ROOT."/addons/xt_jyd/inc/app_pay/alipay.config.php";
		include IA_ROOT."/addons/xt_jyd/inc/app_pay/lib/alipay_notify.class.php";
		include IA_ROOT."/addons/xt_jyd/inc/app_pay/lib/alipay_rsa.function.php";
		include IA_ROOT."/addons/xt_jyd/inc/app_pay/lib/alipay_core.function.php";
		//计算得出通知验证结果
			
			$alipayNotify = new AlipayNotify($alipay_config);
			message1($alipayNotify);
			message1($alipayNotify->getResponse($_POST['notify_id']));
			if($alipayNotify->getResponse($_POST['notify_id']))//判断成功之后使用getResponse方法判断是否是支付宝发来的异步通知。
			{
				message1($a='----------------------------------------------------------------------------------------------------------------------------------------------------');
				message1($_POST['sign']);
				if($alipayNotify->getSignVeryfy($_POST, $_POST['sign'])) {
					message1('进入');
					//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
		    		//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
					//商户订单号
					message1($_POST);
					$out_trade_no = $_POST['out_trade_no'];
					//支付宝交易号
					$trade_no = $_POST['trade_no'];
					//交易状态
					$trade_status = $_POST['trade_status'];
					$total_fee=$_POST['buyer_pay_amount'];
					message1($file='total_fee');
		    		if($_POST['trade_status'] == 'TRADE_FINISHED') {
		    			echo "success";
					//判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
					//注意：
					//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
					//请务必判断请求时的out_trade_no、total_fee、seller_id与通知时获取的out_trade_no、total_fee、seller_id为一致的
		    		}
		    		else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		    			message1('进来128');
		    			$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniontid`=:uniontid';
						$params = array();
						$params[':uniontid'] = $out_trade_no;
						$log = pdo_fetch($sql, $params);
						if(!empty($log) && $log['status'] == '0') {
							$log['transaction_id'] = $_POST['trade_no'];
							$record = array();
							$record['status'] = '1';
							$record['type']='alipay';
							$record['uniontid']=$_POST['trade_no'];
							pdo_update('core_paylog', $record, array('plid' => $log['plid']));
							$f=pdo_fetch("select status,totalprice from ".tablename('xiantuan_order')." where id='{$log['tid']}'");
							message1($f);
							message1($total_fee);
							if($f['status']!=0){
								echo "sign fail";exit;
							}
							$site = WeUtility::createModuleSite($log['module']);
							if($log['module']=='xt_jyd'){
									$action='pay';
									$file=IA_ROOT.'/addons/'.$log['module'].'/inc/app/' . strtolower($action) . '.class.php'; 
									include_once $file;
									$classname = "payModuleSite";
									$log['result'] = 'success';
									$log['tag'] = $tag;
									$method = 'payResult';
									$obj=new $classname();
									$obj->__define=$site->__define;
									if (method_exists($obj, $method)) {
										message1($f='进来129');
										$f=$obj->$method($log,$api=1);
										message1('回调返回');
									}
							}
							
							message1($f='进来1290');
						//	$f=payResult($ret);
						//	message1('123456789');
						//	message1($f);
							if($f==false){
								message1('错误');
								echo "sign fail";exit;
							}else{
								message1('完成');
								echo "success";	exit;
							}
						}
					message1($f='进来1291');
						echo "success";	
		    		}
					echo "success";		//请不要修改或删除
				}
				else //验证签名失败
				{
					$data='1111111111'; 
					message1($data='任务失败');
					echo "sign fail";
				}
			}
			else //验证是否来自支付宝的通知失败
			{
				message1($data='成功');
				echo "response fail";
			}
		}