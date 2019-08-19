<?php
namespace Common\Model;
use Common\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class Withdraw extends CommonModel
{
	public $table="member_withdraw";
	protected function getTableName($id){
		return $this->table;
	}
	
	public function get_orm(){
		return $this->getORM();
	}
	
	public function deletes($where){
		return $this->getORM()->where($where)->delete();
	}
	public function get_data($where){
		return $this->get_orm()->where($where)->fetchOne();
	}
	public function updateEdit($up,$where){
		return $this->edit($up,$where);
	}
}