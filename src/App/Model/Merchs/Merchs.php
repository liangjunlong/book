<?php
namespace App\Model\Merchs;
use App\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class Merchs extends CommonModel
{
	public $table="hotel_store";
	protected function getTableName($id){
		return $this->table;
	}
	
	public function get_orm(){
		return $this->getORM();
	}
	
	public function deletes($where){
		return $this->getORM()->where($where)->delete();
	}
	
}