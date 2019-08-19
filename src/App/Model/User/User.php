<?php
namespace App\Model\User;
use App\Common\CommonModel;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23 0023
 * Time: 9:37
 */
class User extends CommonModel
{
	public $table='admin';
	protected function getTableName($id=0){
		return $this->table;
	}
	
	public function get_orm(){
		return $this->getORM();
	}
	
	public function getusername($username){
			
		return $this->getORM()->select('*')->where(array('admin_name'=>$username))->fetch();
		
	}
	public function update_admin($data,$where){
		return $this->getORM()->where($where)->update($data);
	}
	
	public function getIDuser($id,$field='*')
	{
		return $this->getORM()->select($field)->where(array('id'=>$id))->fetch();
	}
	
	public function getUserToken($token)
    {
        if (empty($token)) {
            return null;
        }
        return $this->getORM()->select('*')->where('admin_token = ?', $token)->fetchOne();
    }
}