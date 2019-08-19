<?php
namespace App\Api;
use App\Common\CommonApi;
use App\Domain\Area as AreaModel;
use function App\addlog;
use function App\param;
/**
 *城市类 
 * 
 */
class Area extends CommonApi
{
	private $num=1;
	private static $Domain = null;
	public function __construct()
    {
   // 	echo 22;exit;
        if (self::$Domain == null) {
            self::$Domain = new AreaModel();
        }
		$this->getRules();
    }

	public function getRules()
    {
    	$this->rules=array(
           'select'=>array(
		   	'id'=>array('name'=>'id','type'=>'int','require'=>false,'desc'=>'需要查的ID'),
		   	'pid'=>array('name'=>'pid','type'=>'int','require'=>false,'desc'=>'需要查找的当前下面的所有城市'),
		   	'type'=>array('name'=>'type','type'=>'int','require'=>false,'desc'=>'1查找当前所有省 二查找所有市 三查找所有区  如果要查找固定城市下面的省市区全部数据请传PID'),
		   ),
        );
		
        return $this->rules; 
    }
	
	public function select(){
		$params=param($this,$this->rules,'select_area_data');
		$data=self::$Domain ->select_area_data($params);
		return $this->return_array($data,'查找数据失败','成功',1);
	}
}