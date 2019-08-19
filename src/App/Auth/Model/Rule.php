<?php
namespace App\Auth\Model;
use function App\addLog;
use PhalApi\Model\NotORMModel as NotORM;

/*
/**
 * 规则模型类
 * @author: hms 2015-8-6
 */

class Rule extends NotORM
{

    protected function getTableName($id)
    {
        return \PhalApi\DI()->config->get('app.auth.auth_rule');
    }
	/**
	 *获取全部权限，给admin用户使用 
	 * 
	 */
	public function getallDataAuth($where,$field='*'){
		$data=$this->getORM()->select($field)->where($where)->fetchRows();
		return $data;
	}

    /**获取列表
     * @param $param
     * @return mixed
     */
    public function getList($param)
    {
        $r = $this->getORM()->select($param['field'])->where('auth_rule_title LIKE ?', "%" . $param['keyWord'] . "%")
            ->or('auth_rule_name LIKE ?', "%" . $param['keyWord'] . "%")
//            ->limit($param['limitPage'], $param['limitCount'])
            ->order('listorder asc')
            ->fetchAll();
		
        return $r;
    }

    public function getInfo($id)
    {
        $r = $this->getORM()->where('id', $id)->fetchOne();
        return $r;
    }

    /**获取总数
     * @param $keyWord
     * @return mixed
     */
    public function getCount($keyWord)
    {
        $r = $this->getORM()->where('auth_rule_title LIKE ? ', "%" . $keyWord . "%")
            ->or('auth_rule_name LIKE ?', "%" . $keyWord . "%")
            ->count();
        return $r;
    }

    public function getLimitCount($keyWord, $pid)
    {
        if (isset($pid)) {
//            $r = $this->getORM()->where('pid = ?',$pid)->where('title LIKE ? ', "%" . $keyWord . "%")
//                ->or('name LIKE ?', "%" . $keyWord . "%")
//                ->count();
            $r = $this->getORM()->where('auth_rule_pid = '.$pid)->count();
			
        } else {
            $r = $this->getORM()->where('auth_rule_title LIKE ? ', "%" . $keyWord . "%")
                ->or('auth_rule_name LIKE ?', "%" . $keyWord . "%")
                ->count();
        }
        return $r;
    }

    /**获取列表
     * @param $param
     * @return mixed
     */
    public function getLimitList($param)
    {
    	//var_dump($param);exit;
        $param['limitPage'] = ($param['limitPage'] - 1) * $param['limitCount'];
		
        if (isset($param['auth_rule_pid'])) {
        
            $r = $this->getORM()->where('auth_rule_pid = '.$param['auth_rule_pid'])->limit($param['limitPage'], $param['limitCount'])
                ->order('listorder asc')
                ->fetchAll();;
//            
        } else {
        	
            $r = $this->getORM()->select($param['field'])->where('auth_rule_title LIKE ?', "%" . $param['keyWord'] . "%")
                ->or('auth_rule_name LIKE ?', "%" . $param['keyWord'] . "%")
                ->limit($param['limitPage'], $param['limitCount'])
                ->order($param['order'])
                ->fetchAll();
        }
        return $r;
    }

    /**添加规则
     * @param $param
     * @return bool
     */
    public function addRule($param)
    {
        $rom = $this->getORM();
        $r=$rom->insert($param);
        $id = $rom->insert_id();
        if (!empty($id)) {
            addLog("新增菜单，编号：$id");
        }
        return empty($id) ? false : true;
    }

    /**修改规则
     * @param $id
     * @param $info
     * @return bool
     */
    public function editRule($id, $info)
    {
        $r = $this->getORM()->where('id', $id)->update($info);
        return $r === false ? false : true;
    }

    /** 删除规则
     * @param $ids
     * @return bool
     */
    public function delRule($ids)
    {
    
        $r = $this->getORM()->where('id in ('. $ids.")")->delete();
        return $r === false ? false : true;
    }

    /**
     * 检测规则标识是否重复
     * @param string $name
     * @param int $id
     * @return boolean
     */
    public function checkRepeat($name, $id = 0)
    {
        $r = $this->getORM()->select('id')->where('auth_rule_name', $name)->where('id != ?', $id)->fetchOne();
        return !empty($r) ? true : false;
    }

    public function getRulesInGroups($gids)
    {
    	
        $rules = $this->getORM()->select('*')
            ->where(array('id' => $gids, 'auth_rule_status' => 1))
            ->fetchAll();
        return $rules;
    }

    public function getRulesInGroupsCache($gids)
    {
    	
        $rules = \PhalApi\DI()->cache->get('rulesInGroups'); //缓存读取
        if ($rules == null) {
            $rules = self::getRulesInGroups($gids);
            \PhalApi\DI()->cache->set('rulesInGroups', $rules);
        }
        return $rules;
    }


}
