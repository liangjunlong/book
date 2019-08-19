<?php
namespace App\Model\Financial;
use App\Common\CommonModel;
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
	public function update_data($where,$update){
		return $this->getORM()->where($where)->update($update);
	}
}