<?php

namespace App\Common;

use App\Model\User\User as ModelUser;

use PhalApi\Api;
use PhalApi\Exception\BadRequestException;
use function App\param;

//use App\Model\Assets\Assets as ModelAssets;

class CommonApi extends Api
{
    public $user_info = null;
	private $rules;
	public $day=7;
    /**
     * @throws BadRequestException
     * @throws \PhalApi\Exception_BadRequest
     */
	// 用户角色
    public $page = [
        'page' => array('name' => 'page', 'require' => false,'default'=>'1', 'desc' => '页码',),
        'pindex' => array('name' => 'pindex', 'require' => false, 'default'=>10,'desc' => '数据量',),
    ];
	
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
			if(isset($or_data)){
				foreach($or_data[0] as $k=>$v){
					$result[$k]=$v;
				}
			}
		}
		return $result;
	}
	
	public function check_data($data){
		$flag=0;
		if(isset($data['count'])){
			$count=$data['count'];
			unset($data['count']);
			$old_data=$data['data'];
			$or_data[0]=$data;
			unset($or_data[0]['data']);
			$data=self::check_data_data($old_data);
			$data['count']=$count;
		}else{
			$data=self::check_data_data($data);
		}
		if(isset($or_data)){
			return ['data'=>$data,'flag'=>$flag,'or_data'=>$or_data];
		}else{
			return ['data'=>$data,'flag'=>$flag];
		}
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
      //  parent::userCheck();
     //   $this->loginCheck();
		
        $service = \PhalApi\DI()->request->get('service');
        if (!$service) {
            $service = \PhalApi\DI()->request->get('s');
        }
		
        $no_auth = \PhalApi\DI()->config->get('app.service_no_auth');
		return true;
		$service=str_replace('App.','',$service);
	//	var_dump($service);
	//	var_dump($no_auth);exit;
        if (!in_array($service, $no_auth) and $this->user_info['admin_name'] != 'admin') {
        	$service=strtolower($service);
			
            $auth_check = \PhalApi\DI()->auth->check($service, $this->user_info['id']);
			
            if (!$auth_check) {
         //      throw new BadRequestException('您没有访问权限', 2);
            }
        }
    }
	/**
	 *检测用户身份证明 
	 * 
	 */
	 public function checkUserAuth(){
	 	$userObj=new ModelAssets();
	 	$user_info=$this->user_info;
		//经办人
		
		if($user_info['user_auth_group_id']==2){
			return $user_info['id'];
		}
		return false;
	 }
    /**
     * 用户登录检测
     * @desc 用户登录检测
     * @return bool
     * @throws BadRequestException
     */
    public function loginCheck()
    {
        $service = \PhalApi\DI()->request->get('service');
        if (!$service) {
            $service = \PhalApi\DI()->request->get('s');
        }
		
        //过滤不需要登录token的请求
        $no_login = \PhalApi\DI()->config->get('app.service_no_login');
		
        if (in_array($service, $no_login)) {
            return true;
        }
       
        $tokenArray = \PhalApi\DI()->request->getHeader('token');
	
        //todo mac和windows对 token 大小写处理不一样？？
        if (!$tokenArray) {
            $tokenArray = \PhalApi\DI()->request->getHeader('Token');
				
            if (!$tokenArray) {
                $tokenArray = \PhalApi\DI()->request->get('TOKEN');
            }
        }
		
        if ($tokenArray) {
            $tokenArray = explode('&', $tokenArray);
            $token = $tokenArray[0];// token
            $expired_time =$tokenArray[1];// 过期时间
			$model_user_token = new ModelUser();
            $user_token_info = $model_user_token->getUserToken($token);
            if ($user_token_info) {
                $user_name = $user_token_info['admin_name'];
                if ($user_name) {
                    \PhalApi\DI()->cookie->set('user_info', json_encode($user_token_info), $_SERVER['REQUEST_TIME'] + 600);
                    $this->user_info = $user_token_info;
                }
            }
			
            if ($service != 'User_User.Login' && $service !='App.User_User.Login') {
                if (empty($user_token_info)) {
                    throw new BadRequestException('登录超时..', 99);
                }
                if ($expired_time == 0) {
                    $expired_time = $user_token_info['login_time'] + 7 * 86400;// 有效期默认一天
                }
                if ($expired_time < time()) {
                    throw new BadRequestException('登录超时.', 99);
                }
            }
        } else {
            if ($service != 'User_User.Login' && $service !='App.User_User.Login') {
               throw new BadRequestException('请登录..', 99);
            }
        }
    }


}
