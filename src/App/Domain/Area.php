<?php
namespace App\Domain;
use App\Common\CommonDomain;
use App\Model\Area as ModelArea;
use function App\token;
use function App\getIP;
use function App\getoverdue;
class Area extends CommonDomain
{
	public static $Model;
	public $flag_str="-";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
    public function __construct()
    {
    	parent::__construct();
        if (self::$Model == null) {
            self::$Model = new ModelArea();
        }
    }
	public function get_addr($province_id,$city_id,$area_id,$addr_details){
		$province_name=$this->select_area_data(['id'=>$province_id]);
		$city_name=$this->select_area_data(['id'=>$city_id]);
		$area_name=$this->select_area_data(['id'=>$area_id]);
		$details=$province_name[0]['name'].$city_name[0]['name'].$area_name[0]['name'].$addr_details;
		return $details;
	}
	public function select_area_data($param){
		$where=" status = 1 ";
		if(isset($param['id'])){
			$where.=" and id=".$param['id'];
		}
		if(isset($param['pid'])){
			$where.=" and pid=".$param['pid'];
		}
		if(isset($param['type'])==1 && !isset($param['pid'])){
			$where.=" and pid=1";
		}
		if(isset($param['city_id'])){
			$where.=" and id in (".$param['city_id'].")";
		}
		$limit="";
		if(isset($param['page'])){
			$limit.=($param['page']-1)*$param['pindex'].','.$param['pindex'];
		}
		$data=self::$CommonModel->table('_area')
						  ->where($where)
						  ->field('id,name,pid')
						  ->order('id desc')
						  ->limit($limit)
						  ->fetchall();		  
		return $data;
	}
	
}