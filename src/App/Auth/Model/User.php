<?php
namespace App\Auth\Model;
use PhalApi\Model\NotORMModel as NotORM;
/*
/**
 * 用户模型
 * @author: hms 2015-8-6
 */

class User extends NotORM
{

    protected function getTableName($id)
    {
        return 'auth_rule';
    }
    
	public function select_admin_auth_rule(){
		return $this->getORM()->select('*')->fetchall();
	}
	
	public function select_or_auth_rule($rules){
		return $this->getORM()->where('id in ('.$rules.")")->select('*')->fetchall();
	}
}
