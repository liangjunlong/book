<?php
namespace Common\Common;
use PhalApi\Model\NotORMModel as NotORM;
class CommonModel extends NotORM
{
	public $where;
	public $filed="*";
	public $join=[];
	public $table;
	public $alias;
	public $orderby;
	public $limit;
	public $sql=[];
	public $groupby;
	public function limit($limit){
		$this->limit=$limit;
		return $this;
	}
	public function table($table){
		$this->table=$table;
		return $this;
	}
	public function alias($alias){
		$this->alias=$alias;
		return $this;
	}
	public function where($where){
		$this->where="	where " .$where;
		return $this;
	}
	public function field($filed="*"){
		$this->filed=$filed;
		return $this;
	}
	public function join($join){
		$this->join[]=$join;
		return $this;
	} 
	public function order($orderby){
		$this->orderby=$orderby;
		return $this;
	}
	public function groupby($groupby){
		$this->groupby=$groupby;
		return $this;
	}
	public function begin(){
		return \PhalApi\DI()->notorm->beginTransaction(DBNAME);
	}
	public function commit(){
		return \PhalApi\DI()->notorm->commit(DBNAME);
	}
	public function rollback(){
		return \PhalApi\DI()->notorm->rollback(DBNAME);
	}
	public function edit($up,$where){
		$r=$this->getORM()->where($where)->update($up);
		return $r;
	}
	
	public function fetch(){
		$sql="select ".$this->filed." from ".TABLE.$this->table;
		$this->filed="*";
		if(substr($this->table,0,1)=='_'){
			$this->table=substr($this->table,1);
		}
		if($this->alias){
			$sql.=" as ".$this->alias;
		}
		if($this->join){
			foreach($this->join as $k=>$v){
				if(stripos($v,'join')){
					$sql.=' '.$v;
				}else{
					$sql.="	join ".$v;
				}
			}
			$this->join=[];
		}
		if($this->where){
			$sql.=$this->where;
			$this->where="";
		}
		if($this->orderby){
			$sql.=" order by ".$this->orderby;
			$this->orderby="";
		}
		if($this->limit){
			$sql.= $this->limit;
			$this->limi="";
		}
		$this->sql[]=$sql;
		return $this->getORM()->queryone($sql,[]);
	}
	
	public function fetchall(){
		$sql="select ".$this->filed." from ".TABLE.$this->table;
		$this->filed="*";
		if(substr($this->table,0,1)=='_'){
			$this->table=substr($this->table,1);
		}
		if($this->alias){
			$sql.=" as ".$this->alias;
			$this->alias="";
		}
		if($this->join){
			foreach($this->join as $k=>$v){
				if(stripos($v,'join')){
					$sql.=' '.$v;
				}else{
					$sql.="	join ".$v;
				}
			}
			$this->join=[];
		}
		
		if($this->where){
			$sql.=$this->where;
			$this->where="";
		}
		if($this->orderby){
			$sql.=" order by ".$this->orderby;
			$this->orderby="";
		}
		if($this->limit){
			$sql.= $this->limit;
			$this->limit="";
		}
		$this->sql[]=$sql;
	//	var_dump($this->sql);
		return $this->getORM()->queryall($sql,[]);
	}

    /**
     * @param $param
     * @return array total：记录总数  rows:分段结果集
     * @internal param int $page_size 页面大小
     * @internal param int $page_num 页码
     * @internal param array $where 查询条件
     * @internal param string $order 排序
     * @internal param string $field 字段
     */
    public function getList($param)
    {
        if (!is_array($param)) {
            $param = get_object_vars($param);
        }
        if (!isset($param['page_num'])) {
            $param['page_num'] = 1;
        }
        if (!isset($param['page_size'])) {
            $param['page_size'] = PAGE_SIZE;
        }
        if (!isset($param['field'])) {
            $param['field'] = '*';
        }
        if (!isset($param['order'])) {
            $param['order'] = 'id desc';
        }
        if (!isset($param['where'])) {
            $param['where'] = array();
        }
        $total = $this->getORM()->where($param['where'])->count();
        $offset = ($param['page_num'] - 1) * $param['page_size'];
        $orm = $this->getORM()->where($param['where'])->select($param['field'])->limit($offset, $param['page_size'])->order($param['order']);
        //读取缓存
        $di = \PhalApi\DI();
        $index = md5($orm->__toString().implode($orm->getParameters()));
        $lists = $di->cache->get($index);
        if(!$lists) {
            //没有缓存，则从数据库读取
            $lists = $orm->fetchAll();
            if($lists) {
                $di->cache->set($index, $lists, CACHE_TIMEOUT) ;
            }
        }
       
        return array('count' => $total, 'list' => $lists);
    }

    public function getCount($condition = array())
    {
        $orm = $this->getORM()->where($condition);
        //读取缓存
        $di = \PhalApi\DI();
        $index = md5($orm->__toString().implode($orm->getParameters()));
        $count = $di->cache->get($index);
        if(!$count) {
            //没有缓存，则从数据库读取
            $count = $orm->count();
            if($count) {
                $di->cache->set($index, $count, CACHE_TIMEOUT) ;
            }
        }
        return $count;
    }

    /**
     * 根据查询条件获取单条数据
     * @param array $condition
     * @param string $field
     * @return mixed
     */
    public function getInfo($condition = array(), $field = '*')
    {
        $orm = $this->getORM()->where($condition)->select($field);
        //读取缓存
        $di = \PhalApi\DI();
        $index = md5($orm->__toString().implode($orm->getParameters()));
        $result = $di->cache->get($index);
        if(!$result) {
            //没有缓存，则从数据库读取
            $result = $orm->fetchOne();
            if($result) {
                $di->cache->set($index, $result, CACHE_TIMEOUT) ;
            }
        }
        return $result;
    }

    /**
     * 根据查询条件获取所有数据
     * @param array $condition
     * @param string $field
     * @return mixed
     */
    public function getListByWhere($condition = array(), $field = '*', $order = 'id desc')
    {
        $orm = $this->getORM()->where($condition)->select($field)->order($order);
        //读取缓存
        $di = \PhalApi\DI();
        $index = md5($orm->__toString().implode($orm->getParameters()));
        $lists = $di->cache->get($index);
        if(!$lists) {
            //没有缓存，则从数据库读取
            $lists = $orm->fetchAll();
            if($lists) {
                $di->cache->set($index, $lists, CACHE_TIMEOUT) ;
            }
        }
        return $lists;
    }


    /**
     * 根据查询条件更新数据
     * @param array $condition
     * @param array $data
     * @return boolean|int 执行成功返回影响条数，失败返回false
     */
    public function updateByWhere($where, $data)
    {
        $this->formatExtData($data);
        return $this->getORM()->where($where)->update($data);
    }
    public function updateByIds($ids, $data)
    {
        $r = $this->getORM()->where('id', $ids)->update($data);
        return $r === false ? false : true;
    }
    public function deleteByWhere($where)
    {
        $this->formatExtData($data);
        return $this->getORM()->where($where)->delete();
    }

    /**
     * 直接执行手写sql语句更新或者插入
     * @param string $sql sql更新或者插入语句
     * @param array $params 更新或者插入条件和更新或者插入数据
     * @return boolean|int 执行成功返回影响条数，失败返回false
     */
    public function queryExecute($sql, $params = array())
    {
        return $this->getORM()->query($sql, $params);
    }

    /** 删除
     * @param $ids
     * @return bool
     */
    public function delItems($ids)
    {
        $r = $this->getORM()->where('id', $ids)->delete();
        return $r === false ? false : true;
    }

    public function getListByIds($ids,$keys = 'id',$order = 'id desc',$field='*')
    {
        $orm = $this->getORM()->where($keys, $ids)->select($field)->order($order);
        //读取缓存
        $di = \PhalApi\DI();
        $index = md5($orm->__toString().implode($orm->getParameters()));
        $lists = $di->cache->get($index);
        if(!$lists) {
            //没有缓存，则从数据库读取
            $lists = $orm->fetchAll();
            if($lists) {
                $di->cache->set($index, $lists, CACHE_TIMEOUT) ;
            }
        }
        return $lists;
    }

}