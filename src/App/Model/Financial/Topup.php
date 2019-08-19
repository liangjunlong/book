<?php
namespace App\Model\Financial;
use App\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class Topup extends CommonModel
{
	public $table="topup_log";
	public $id;
	public function getTableName($id){
		 if ($id !== null) {
            $this->table.= '_' . ($id % REMOVED);
        }else{
			$this->table=$this->table;
		}
		return $this->table;
	}
	
	//获取分表的数据ID
	public function getNameById($id) {
        $row = $this->getORM($id)->select('name')->fetchRow();
        return !empty($row) ? $row['name'] : '';
    }
	  
	public function get_orm($id){
		if($id!=null){
			return $this->getORM($id);
		}else{
			return $this->getORM();
		}
	}
	
	public function getcounts($where){
		$count= $this->getORM()->where($where)->count();
		echo $count;exit;
        return $count;
	}
	
	public function deletes($where){
		return $this->getORM()->where($where)->delete();
	}
	
}