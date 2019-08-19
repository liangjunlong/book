<?php
namespace App\Model\Log;
use App\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class log extends CommonModel
{
	
	protected function getTableName($id=0){
		return 'log';
	}
	public function get_orm(){
		return $this->getORM();
	}
}