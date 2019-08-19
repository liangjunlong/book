<?php
namespace App\Model\Setting;
use App\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class setting extends CommonModel
{
	public $table='setting';
	protected function getTableName($id=0){
		return $this->table;
	}
	
	public function get_orm(){
		return $this->getORM();
	}
	
	public function select($where,$type=0){
		if($type==1){
			$data=$this->getORM()->select('*')->where($where)->fetchAll();
		}else{
			$data=$this->getORM()->select('*')->where($where)->fetchOne();
		}
		return $data;
	}
	
	public function edit($where,$data){
		$data=$this->getORM()->where($where)->update($data);
		return $data;
	}
	
	public function insert_data($param){
		$data=$this->getORM()->insert($param);
		return $data;
	}
	public function select_count($where){
		$count=$this->getORM()->where($where)->count();
		return $count;
	}
	
	public function select_ad_data($where,$orderby,$limit,$field='*'){
		$sql="select ".$field." from ".TABLE."_ad where ".$where.$orderby.$limit;
		$data=self::get_orm()->queryAll($sql);	
		return $data;
	}
	
	public function query_coupon_count($where){
		$sql="select count(c.coupon_id) num from ".TABLE."_coupon c 
			join ".TABLE."_coupon_detail s on s.coupon_id=c.coupon_id
			where ".$where;
		
		$count=self::get_orm()->queryAll($sql);	
		//var_dump($count);exit;
		return $count[0]['num'];
	}
	/**/
	public function query_coupon($where,$orderby,$limit,$field='*'){
		$sql="select ".$field." from ".TABLE."_coupon c 
			join ".TABLE."_coupon_detail s on s.coupon_id=c.coupon_id
			where ".$where.$orderby.$limit;
		$data=self::get_orm()->queryAll($sql);	
		return $data;
	}
	
	public function select_all_data($table,$where,$orderby,$limit,$field='*'){
		$sql="select ".$field." from ".TABLE.$table." where ".$where.$orderby.$limit;
		$data=self::get_orm()->queryAll($sql);
		return $data;
	}
}