<?php
class Data{
	static $instance;
	static protected $db;

	private function __construct(){
		$server = DB_SERVER;//"localhost";
		$user = DB_USER;//"root";
		$password = DB_PASS;//"";
		$database = DB_NAME;//"traffic_app";
		$db=self::$db = mysql_connect($server, $user, $password, $database);
		mysql_select_db($database) or die(mysql_error());
		return $db;
	}

	protected static function getInstance(){
		if (!(self::$instance instanceof self)){
			$i=self::$instance =new self();
			self::$db =$i::$db;
		}
		return self::$db;
	}
}

class DB extends Data{
	private $DB;//Database resource appended to  queries to ensure that an existing connection shou;ld be used
	protected static $tablename;
	
	public static function conn(){
		return self::getInstance();
	}

	public static function q($qrystr,$mode='r',$errMsg=''){
		//$mode='null|c|r|u|d';
		//echo $qrystr."\n";
		$rows=mysql_query($qrystr,self::conn()) or die($errMsg);

		if (!$mode) return $rows;

		if ($mode=='c') return mysql_insert_id();

		if($mode=='r'){
			if(@mysql_num_rows($rows)==0) return array();//false;
			$result=array();
			while ($row=mysql_fetch_assoc($rows)) {
				$result[]=$row;
			}
			return $result;
		}

		if ($mode=='u' || $mode=='d') return mysql_affected_rows();
	}

	public static function insert($tablename='',$values=array(),$where=array(),$op='AND'){
		if(empty($tablename) || empty($values)) die("DB error : : no table or colomn/values pairs supplied for Data::insert { table= {$tablename}, values='".implode(', ',$values)."' }");
		$q='';
		foreach($values as $key=>$value){
			$q.=", ".mysql_real_escape_string($key)."=\"".mysql_real_escape_string($value)."\"";
		}
		$q=substr($q,2);
		if(!empty($where)) return self::update($tablename,$q,$where,$op);
		$sqlstr="INSERT INTO {$tablename} SET ".$q;
		//mysql_query($sqlstr,self::conn()) or die("DB error while doing INSERT INTO {$tablename} SET ".$q);
		DB::q($sqlstr,'c',"DB error while doing INSERT INTO {$tablename} SET ".$q);
		return mysql_insert_id();
	}

	private static function update($tablename='',$values='',$where=array(),$op='AND'){
		if(empty($tablename) || empty($values)) die("DB error : no table or colomn/values pairs supplied as string for Data::update");
		$w='';
		foreach($where as $key=>$value){
			$w.="{$op} ".mysql_real_escape_string($key)."=\"".mysql_real_escape_string($value)."\"";
		}
		$w=substr($w,strlen($op));
		$sqlstr="UPDATE {$tablename} SET {$values} WHERE ".$w;
		//mysql_query($sqlstr,self::conn()) or die("DB error while doing UPDATE {$tablename} SET {$values} WHERE ".$w);
		DB::q($sqlstr,'u',"DB error while doing UPDATE {$tablename} SET {$values} WHERE ".$w);
		return true;
	}
	
	public static function getDataByField($field,$value,$tablename=''){
		$field=mysql_real_escape_string($field);
		$value=mysql_real_escape_string($value);
		//print_r($value);
		$cls=get_called_class();
		$result=array();
		if ($tablename=='') $tablename=$cls::$tablename;
		$tablename=mysql_real_escape_string($tablename);
		$sql="SELECT * FROM {$tablename} WHERE `{$field}`='{$value}'";
		$result=DB::q($sql);
		return $result;
	}

	public static function getDataByFields($fields,$values,$tablename='', $op='AND'){
		if (count($fields)!=count($values)) return false;
		$whstr='WHERE ';
		for ($i=0; $i < count($fields); $i++) { 
			$field=mysql_real_escape_string($fields[$i]);
			$value=mysql_real_escape_string($values[$i]);
			$whstr.="`{$field}`='{$value}' {$op}";
		}
		if (empty($fields)) {$whstr='WHERE 1 ';$op='';}
		$limstr=-1*(strlen($op)+1);
		$whstr=substr($whstr, 0, $limstr);
		$cls=get_called_class();
		$result=array();
		if ($tablename=='') $tablename=$cls::$tablename;
		$tablename=mysql_real_escape_string($tablename);
		$sql="SELECT * FROM {$tablename} ".$whstr;
		$result=DB::q($sql);
		return $result;
	}

}
