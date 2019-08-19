<?php
namespace App\Model;
use App\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class Area extends CommonModel
{
	public $table='area';
	protected function getTableName($id=0){
		return $this->table;
	}
	
	public function get_orm(){
		return $this->getORM();
	}
}