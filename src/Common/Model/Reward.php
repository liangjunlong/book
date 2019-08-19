<?php
namespace Common\Model;
use Common\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class Reward extends CommonModel
{
	public $table="member_credit";
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