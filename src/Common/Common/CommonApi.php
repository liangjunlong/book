<?php

namespace Common\Common;

//use App\Model\User\User as ModelUser;
//use Mobbile\Model\User\UserToken as ModelUserToken;

use PhalApi\Api;
use PhalApi\Exception\BadRequestException;
use Mobile\Domain\User\User;
use function App\param;
use Mobile\Common\CommonDomain;
class CommonApi extends Api
{
    public $user_info = null;
	private $rules;
	public $page = [
        'page' => array('name' => 'page', 'require' => false,'default'=>'1', 'desc' => '页码',),
        'pindex' => array('name' => 'pindex', 'require' => false, 'default'=>2,'desc' => '数据量',),
    ];
    /**
     * @throws BadRequestException
     * @throws \PhalApi\Exception_BadRequest
     */
    public function params($data,$datas,$file){
		$params=param($data,$datas,$file);
		return $params;
	}
	
	public function return_array($data,$errmsg,$correctmsg,$code){
		if(!$data){
			$result['result']='-1';
			$result['msg']=$errmsg;
		}else{
			$data_param=$this->check_data($data);
			extract($data_param);
			
			if($flag==1){
				$data['result']=$code;
				$data['msg']=$correctmsg;
				$result=$data;
			}else{
				$result['data']=$data;
				$result['result']=$code;
				$result['msg']=$correctmsg;
			}
		}
		return $result;
	}
		
	public function check_data($data){
		$flag=0;
		if(isset($data['count'])){
			$count=$data['count'];
			$data=self::check_data_data($data['data']);
			$data['count']=$count;
		}else{
			$data=self::check_data_data($data);
		}
		
		return ['data'=>$data,'flag'=>$flag];
	}
	
	public function check_data_data($data){
		if(!is_array($data)){
			return $data;
		}
		foreach($data as $k=>$v){
			if(!is_array($v)){
				if($k=='time'){
					$data['time']=date('Y-m-d H:i:s',$data[$k]);
				}
				if($k=='log_time'){
					$data['log_time']=date('Y-m-d H:i:s',$data['log_time']);
				}
				if($k=='up_time'){
					$data['up_time']=date('Y-m-d H:i:s',$data['up_time']);
				}
				if($k=='create_time'){
					$data['create_time']=date('Y-m-d H:i:s',$data['create_time']);
				}
				if($k=='begin_time'){
					$data['begin_time']=date('Y-m-d H:i:s',$data['begin_time']);
				}
				if($k=='endtime'){
					$data['endtime']=date('Y-m-d H:i:s',$data['endtime']);
				}
			}else{
				$flag=1;
				if(isset($v['time'])){
					$data[$k]['time']=date('Y-m-d H:i:s',$v['time']);
				}
				if(isset($v['up_time'])){
					$data[$k]['up_time']=date('Y-m-d H:i:s',$v['up_time']);
				}
				if(isset($v['log_time'])){
					$data[$k]['log_time']=date('Y-m-d H:i:s',$v['log_time']);
				}
				if(isset($v['create_time'])){
					$data[$k]['create_time']=date('Y-m-d H:i:s',$v['create_time']);
				}
				if(isset($v['begin_time'])){
					$data[$k]['begin_time']=date('Y-m-d H:i:s',$v['begin_time']);
				}
				if(isset($v['endtime'])){
					$data[$k]['endtime']=date('Y-m-d H:i:s',$v['endtime']);
				}
			}
		}
		return $data;
	}

    protected function userCheck()
    {
        parent::userCheck();
        $service = \PhalApi\DI()->request->get('service');
        if (!$service) {
            $service = \PhalApi\DI()->request->get('s');
        }
        $no_auth[] = 'Mobile.User_User.Check_loadLogin';
        $no_auth[] = "Mobile.User_User.Get_code";
        $no_auth[] = "Mobile.User_User.Register";
		$no_auth[] ="Mobile.User_User.mobile_register";
		$no_auth[]="Mobile.User_User.mobile_login";
//		echo 1;
//		echo '<Br/>';
        $service = str_replace('App.', '', $service);
        if (!in_array($service, $no_auth)) {
            $tokenArray = \PhalApi\DI()->request->getHeader('token');
            if (!$tokenArray) {
                $tokenArray = \PhalApi\DI()->request->getHeader('Token');

                if (!$tokenArray) {
                    $tokenArray = \PhalApi\DI()->request->get('TOKEN');
                }
            }
            //	var_dump($tokenArray);exit;
            //	$tokenArray = explode('&', $tokenArray);
            //     $token = $tokenArray;// token
            //     $expired_time = $tokenArray[1] / 1000;// 过期时间
            self::check_login();
            if (!$tokenArray) {
                throw new BadRequestException('请传token', 2);
            }
            self::get_userdata($tokenArray);
        }
    }

    public function check_login()
    {
        $result['result'] = '-1';
        $token = \PhalApi\DI()->request->getHeader('token');
        if (!$token) {
            $token = \PhalApi\DI()->request->getHeader('Token');
            if (!$token) {
                $token = \PhalApi\DI()->request->get('TOKEN');
            }
        }

        $r = self::get_userdata($token);
 		if (!$r) {
        	throw new BadRequestException('请登录', 99);
		}
        if (!$r) {
            $result['msg'] = '请登陆';
            $result['result'] = '-100';
            return $result;
        }
        return $r;
    }

    //获取登陆过后的用户数据
    public function get_userdata($token)
    {
        $user_obj = new User();

        $user_data = $user_obj->getUser($token);
        $this->user_info = $user_data;

        if ($user_data) {
            if ($user_data['customer_status'] == 2) {
                throw new BadRequestException('当前帐号已被禁止使用', 2);
            }
            return $user_data;
        }
        return false;

    }
}