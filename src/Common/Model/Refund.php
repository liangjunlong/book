<?php
namespace Common\Model;
use Common\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class Refund extends CommonModel
{
	public $table="order_refund";
	protected function getTableName($id){
		return $this->table;
	}
	
	public function get_orm(){
		return $this->getORM();
	}
	
	public function deletes($where){
		return $this->getORM()->where($where)->delete();
	}
	
	public function UserEdit($up,$where){
		return $this->edit($up,$where);
	}
	
}