<?php
namespace App\Api\User;
use App\Common\CommonApi;
use App\Domain\User\User as UserModel;
use App\Auth\Domain\User as UserAuth;
use App\Domain\User\CreateDb as dbModel;
use App\Auth\Lite;
use function App\addlog;
use function App\param;
/**
 *用户类 
 * 
 */
class User extends CommonApi
{
	private $num=1;
	private static $Domain = null;
	public function __construct()
    {
    	
        if (self::$Domain == null) {
            self::$Domain = new UserModel();
        }
		$this->getRules();
		
    }
	public $mid=array('mid'=>array('name'=>'mid','type'=>'int','require'=>false,'desc'=>'用户ID'));
	public $id=array('id'=>array('name'=>'id','type'=>'int','require'=>true,'desc'=>'ID'));
	public $username=array('username'=>array('name'=>'username','type'=>'string','require'=>false,'desc'=>'用户名'));
	public $password=array('password'=>array('name'=>'password','type'=>'string','require'=>true,'desc'=>'用户名'));
	
	public function getRules()
    {
    	$this->rules=array(
            'login'=>array_merge($this->username,$this->password,$this->page),
            'getRule'=>array_merge($this->id),
            'createdb'=>array(),
            'createcrawler'=>array(),
        );
	
        return $this->rules; 
    }
	public function createcrawler(){
		$param=param($this,$this->rules);
		//1.获取用户登陆数据
		$result=self::$Domain->createcrawler($param);
		return $result;
	}
	public function login(){
		//$param=param($this,$this->rules);
		$param=param($this,$this->rules);

		//1.获取用户登陆数据
		$result=self::$Domain->login($param);
		return $result;
	}
	
	public function getRule(){
		$param=param($this,$this->rules);
		$obj=new UserAuth();
		$rule_data=$obj->getRuleList($param,$this->user_info);
		//2.根据数据进行排序处理
		$lite=new Lite();
		$rule_data=$lite->formatList($rule_data);
		return $this->return_array($rule_data,'获取数据出错','成功',1);
	}
	
	public function createdb(){
		$obj_db=new dbModel();
		$result=$obj_db->createdb();
		return $this->return_array($result,'获取数据出错','成功',1);
	}
}
?>