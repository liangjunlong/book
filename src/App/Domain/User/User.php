<?php
namespace App\Domain\User;
use App\Common\CommonDomain;
use App\Model\User\User as ModelUser;
use function App\token;
use function App\getIP;
use function App\getoverdue;
set_time_limit(0);
class User extends CommonDomain
{
   	private static $Model = null;
	public $flag_str="-";
	public $pindex=20;
	public $result=array('result'=>-1,'data'=>false,'msg'=>'未查找到数据');
    public function __construct()
    {
        if (self::$Model == null) {
            self::$Model = new ModelUser();
        }
    }
    
	public function createcrawler($param){
		$url="http://www.xbiquge.la/10/10489/";
		$name='三坟人间';
		$this->get_url_data($url,$name);
		
	}
	public function Get_data($url){
		$url_data=file_get_contents($url);
		return $url_data;
	}
	public function get_url_data($url,$name){
		$url_data=$this->Get_data($url);
		//1.存储并获取数据
		$book_id=$this->check_data_url1($url,$url_data,$name);
		if($book_id==false) return false;
		//2.查询当前book_id下那些数据没有更新内容
		$where="book_id=".$book_id." and in_stat=0";
		$field="id,book_id";
		$all_dir_data=$this->get_book_dir_data($where,$field);
		
		if($all_dir_data){
			self::$Model->begin('db_master');
			$r=$this->get_all_dir_text($all_dir_data);
			if($r==false){
				self::$Model->rollback('db_master');
				return false;
			}
			self::$Model->commit('db_master');
		}
		
		return true;
	}
	
	public function get_all_dir_text($all_dir_data){
		$html=new_dom();
		foreach($all_dir_data as $k=>$v){
			$url=$v['url'];
			$str=$this->Get_data($url);
			if($str==false) {
				$this->error="远程获取数据失败，请查看是否被该网站禁止爬取数据";
				return false;
			}
			$pattern="/<p>(\s|\S)*?<\/p>/";
			preg_match($pattern,$str,$match);
			if(isset($match[0])){
				$str=preg_replace($pattern,' ',$str);
			}
			$html->loadHTML('<?xml encoding="UTF-8">'.$str);
			$dom_path=dom_path($html);
			$flag_content="content";
			$text_text=$dom_path->query('//*[@id="'.$flag_content.'"]')->item(0)->nodeValue;
			$data=[
				'book_id'=>$v['book_id'],
				'dir_id'=>$v['id'],
				'text'=>$text_text,
				'time'=>time()
			];
			$r=self::$Model->table("book_text")->insert($data);
			$r1=self::$Model->table("book_dir")->edit(['in_stat'=>1],['id'=>$data['dir_id']]);
			if(!$r || !$r1){
				return false;
			}		
		}
		return true;
	}
	
	public $dir_url=[];
	public function get_book_issue_num($url,$name="其他"){
		$re=self::$Model->table("book_name")
					->where("url='".$url."' or name='".$name."'")
					->fetch();
		if($re){
			return $re;
		}
		return false;			
	}
	
	public function insert_book($url,$name){
		$data_insert=[
			'name'=>$name,
			'url'=>$str,
			'time'=>time()
		];
		$r=self::$Model->table("book_name")->insert($data_insert);
		$id=self::$Model->get_orm()->insert_id();
		$re=$this->get_book_data($id);
		return $re;
	}
	public function get_book_dir_data($where,$field="*"){
		$re=self::$Model->table("book_dir")
					->field("*")
					->where($where)
					->fetchAll();
		return $re;
	}
	public function get_book_data($id){
		$re=self::$Model->table("book_name")
					->where("id=".$id)
					->fetch();
		return $re;
	}
	public function update_book_name($up,$where){
		
		$r=self::$Model->table("book_name")->edit($up,$where);
		return $r;
	}
	public function check_data_url1($url,$str,$name="其他"){
		//1.解析数据
		$html=new_dom();
		$html->loadHTML('<?xml encoding="UTF-8">'.$str);
		$dom_path=dom_path($html);
		//1.设置开启标签 
		$num=0;
		$leng=$dom_path->query('//dd/a')->length;
		//获取当前章节最新数据并获取是否存储
		$book_data=$this->get_book_issue_num($url,$name);
		if($book_data!=false){
			$i=$book_data['num'];
		}else{
			$book_data=$this->insert_book($url,$name);
			$i=0;
		}
		$number=0;
		for($i;$i<$leng;$i++){
			$this->dir_url[$number]['url']="http://www.xbiquge.la".$dom_path->query('//dd/a')->item($i)->getAttribute('href');
			$this->dir_url[$number]['title']=$dom_path->query('//dd/a')->item($i)->nodeValue;
			$number++;
		}
		//拿取到最新数据并获取存储
		if(@!empty($this->dir_url)){
			$up_num=0;
			foreach($this->dir_url as $k=>$v){
				$dir_data[$k]=[
					'book_id'=>$book_data['id'],
					'name'=>$v['title'],
					'url'=>$v['url'],
					'time'=>time()
				];
				$r=self::$Model->table("book_dir")->insert($dir_data[$k]);
				if(!$r){
					$this->error="存储数据失败";
					return false;
				}
				$dir_data[$k]['id']=self::$Model->get_orm()->insert_id();
				$up_num++;
			}
			//更新书的最新数量
			$r_book=$this->update_book_name(['num'=>$up_num+$book_data['num']],['id'=>$book_data['id']]);
			if($r_book){
				$this->error="更新书最新章节数量失败";
				return false;
			}
		}
		return $book_data['id'];	
	}
	
	
	public function login(array $param):?array
	{
		//1.查找用户是否存在
		if(!empty($param['username'])){
			$user=self::$Model->getusername($param['username']);
		}
		//1.验证是否密码正确
		if(md5($param['password'])!=$user['admin_password']){
			$this->result['msg']='密码不正确，请重新输入'.md5($param['password']);
			return $this->result;
		}
		$token=token();
		$up=[
			'admin_token'=>$token,
			'admin_login_time'=>time(),
			'admin_login_ip'=>getIP(),
			'admin_visits'=>$user['admin_visits']+1
		];
		$where=[
			'id'=>1
		];
		//2.更新用户登陆数据
		$r=self::$Model->update_admin($up,$where);
		$result=$this->check_select_data($r,'更新用户数据出错','登陆成功');
		if($result['result']==1){
			$result['data']['token']=$token.'&'.getoverdue();
			$result['data']['id']=$user['id'];
			$result['data']['admin_auth_group_id']=$user['admin_auth_group_id'];
		}
		return $result;
	}
	public function getIDuser($id,$field='*'){
		if($id){
			$user=self::$Model->getIDuser($id,$field);
		}
		$result=$this->check_select_data($user,'未查找到用户','查找用户成功');
		if(!$result['result']=='-1'){
			return $result;
		}
		return $user;
	}
}