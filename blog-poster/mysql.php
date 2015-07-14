<?php
/**
 * MySQL 데이터베이스
 *
 *
 * 2009년 2월 6일 이후, mysql.php 클래스는 센터프로젝트의 것이 기본이된다.
 * 2009년 2월 6일 로그 기록 정보를 수정했다.
 *
 * $db->SetLogQuery("c:\\tmp\\sql.log") 와 같이 호출하면 로그가 기록된다.
 *
 * 
 *
 * 데이터베이스 작업을 쉽게 도와주는 클래스이다.
 * @since 2007/01/15 lib/mysql.php 클래스가 siteapi/mysql.php 로 복사되어서 사용이 되었다. siteapi/mysql.php 의 코드가 업그레이드되어서 lib/mysql.php 로 복사가 되었다. 달라진 내용은 http://kldp.net/plugins/scmcvs/cvsweb.php/siteapi/mysql.php.diff?r1=1.3;r2=1.4;cvsroot=siteapi;f=h 에서 확인을 할 수 있다.
 *
 * @author thruthesky thruthesky@yahoo.co.kr
 * @package library
 */
/**
 *
 * 이 클래스를 통해서 MySQL 데이터베이의 작업을 쉽게 할 수 있다.
 *
 * @note	2006-10-15 11:15오전 시점부터 더이상 본 스크립트에서 종료를하지 않는다.
 *			즉, 에러가 발생되면 그 상태를 리턴한다.
 *			리턴값은 가능한 mysql_xxxxx 함수의 리턴 값을 그대로 유지한다.
 * @changed 쿼리를 할 때, 종료 옵션이 따로 있다.
 * @note $db->SetDebug(true) 일 경우, 쿼리를 기록한다.
 * @note 생성자에 값이 생략될 경우,		global $_db_user, $_db_password, $_db_database, $_db_host; 의 변수를 사용한다.
 *
 * 
 * @see mysql.php
 * @note 사용예제
 * <code>
 *  $db = new MySQL("root", "xxxx", "db_name");
 *  $db->query("SELECT * FROM post");
 *	while ($row = $db->row())
 *		echo $row['idx'] . " " . $row['writer'] . "\n";
 * </code>
 * @example ../src/ex/mysql-1.php 클래스 사용 예제 1
 *
 * 다음의 코드를 통해서 접근하기 바란다.
 * <code>
 * lib('mysql');
 * if ( isset($db) && is_object($db) ) return;
 * $db = new MySQL();
 * $rc = $db->connect($system['db_user'], $system['db_password'], $system['db_database'], $system['db_host']);
 * if ( ! $rc ) g($db->error());
 * if ( $db->errno() ) goBack("lib/db:: 데이터베이스 접속(또는 파일 선택)에 실패하였습니다. 데이터베이스 아이디,비밀번호,파일,호스트가 올바르지 체크하십시오.");
 * </code>
 * @since 2007/03/19 쿼리 디버깅 정보를 글로벌 변수에 보관한다.
 *	$GLOBALS['db_queries'][] = substr($q, 0, 255); 와 같이 최대 길이를 제한한다.
 *
 * @add 2009 02 06 쿼리가 이루어질 때마다 $query_count 값이 증가하도록 했다.
 * @add DOS 공격을 방어하기 위해서, 쿼리가 특정 회수가 넘으면 넘으면 10초를 sleep 했다가 자동 종료를 시켜버린다.
 *          $db->dont_stop_on_many_quries() 를 통해서 계속 쿼리를 실행 할 수 있다.
 *
 *
 *
 *
 */
class MySQL {
	var $link;
	var $result;
	var $debug;
	var $logfile;
	var $query_count;
	var $dont_stop_on_many_queries;
	var $prev_query;
	
	
	
	/**
	 * 생성자
	 *
	 * 생성자에서 값을 리턴할 수 없다. 따라서 접속에 실패했는지 echo $db->link; 로 체크를 해야한다.
	 *
	 * $link 내부 변수에 값이 없으면 접속 실패를 한 것이다.
	 * 다음의 예는 접속이 될 때까지 루프는 도는 것이다.
while ( 1 ) {
  $db = new MySQL("root", "7777", "domain");      // DB 연결
  if ( ! $db->link ) echo "connection failed. retrying...\r\n";
  sleep(5);
}
	 *
	 * @note 입력값이 모두 없을 경우, 전역 변수를 이용한다. 가능하면 생성자를 통해서 직접 데이터베이스에 접속을 시도하지 않는다. 생성자는 값을 리턴할 수 없으며 이것은 에러 체크를 어렵게한다.
	 *
	 */
	function MySQL($user='', $pass='', $database='', $host='')
	{
		// 기본적으로 사용되는 글로벌 변수
		global $_db_user, $_db_password, $_db_database, $_db_host;
		
		if ( !empty($user) )
		{
			$this->connect($user, $pass, $database, $host);
		}
		// 입력값과 글로벌 변수 모두 DB 연결 정보가 없을 경우,
		else if ( empty($user) && empty($_db_user) )
		{
			;
		}
		else
		{
			$this->connect($_db_user, $_db_password, $_db_database, $_db_host);
		}
	}
	/**
	 *
	 *
	 * @note MySQL DB 에 직접 접속한다. 참고 Attach()
	 *
	 * @return boolean 실패시 false 아니면 참의 값
	 */
	function connect($user, $pass, $database, $host='')
	{
		if ( ! $host ) $host = 'localhost';
		
		$this->link = mysql_connect( $host, $user, $pass );
		if ( ! $this->link ) return false;

		//mysql_query('set names utf8');

		return mysql_select_db($database, $this->link);
	}
	
	/** 쿼리를 많이 날릴 때는 이 함수를 호출 해야한다.
	 *
	 */
	function dont_stop_on_many_quries()
	{
	  $this->dont_stop_on_many_queries=true;
	}
	/**
	 *
	 *
	 * @note 모든 질의는 이 함수를 통해서 이루어져야한다.
	 * @param $exit
	 * SQL 쿼리에 문제가 발생했을 경우, 이 값이 true 이면 에러 메세지를 내고, 스크립트 실행을 종료시킨다.
	 * 원하지 않는다면 false 를 직접 입력해야한다.
	 *
	 * @note $exit=false 로 입력되고, 에러가 발생했을 경우, $object->result 를 가지고 에러 체크를 하는게 좋다.
	 *
	 * @return resource same as mysql_query. 어떤 경우에든지 에러가 있으면 FALSE 가 리턴된다.
	 */
	function query($q, $exit=true)
	{
	  
	  /**임시처리: 에러가 발생하면, 쿼리가 화면에 뜬다. 이것을 보이지 않도록 임시 처리를 했다. 나중에 공격이 멈추면 다시 풀 것.
	   *
	   */
		if ( ! $q ) return false;		// 쿼리 없음
		$time_start = microtime();        /** 쿼리 시간 계산 */
		
		
		/** 상당 설명 참고 */
		$this->query_count++;
		$down = NULL;
		if ( ! $this->dont_stop_on_many_queries && $this->query_count > 444 ) $down=1;
		if ( $down ) {
		  echo "Too many queries... DB server is going to shutdown in 10 seconds...";
  		if ($this->logfile) $this->logQuery("Too many query. Going to exit. query: $q");
		  exit;
		}
		
		/**
		 * MySQL 데이터베이스 질의
		 *
		 * 유일한 쿼리 문장이다.
		 * 시스템의 모든 쿼리 문장은 오직 아래의 구문에 의해서 실행이된다.
		 */
		$this->result = mysql_query($q, $this->link);
		if ($this->logfile) $this->logQuery($q);


    /** 쿼리 시간 계산 */
    $time_end = microtime();
    $str = explode(" ",$time_start);
    $time_start = $str[0] + $str[1];
    
    $str = explode(" ",$time_end);
    $time_end = $str[0] + $str[1];

    $this->qelaps = $time_end - $time_start;

    

		
		/**
		 * SQL 쿼리와 관련된 디버깅 정보를 변수에 담는다.
		 *
		 * @since 2007/03/19 쿼리 디버깅 정보를 글로벌 변수에 보관한다.
		 * @since 2007/03/27 디버깅 모드가 활성화 되어 있지 않으면, 쿼리 갯수만 세도록 했다.
		 * @since 2007/04/12 범용성을 위해서 debug 함수가 존재하지 않는 경우를 체크하도록 했다.
		 */
		if ( function_exists('debug') && debug() )
		{
			$ar = debug_backtrace();
			$dbgline = NULL;
			foreach( $ar as $e )
			{
				$dbgline .= $e['file'] . " " . $e['line'];
			}
			$GLOBALS['db_queries'][] = substr($q, 0, 255) . " " . $dbgline;
		}
		if ( !isset($GLOBALS['db_count_queries']) ) $GLOBALS['db_count_queries'] = 0;
		$GLOBALS['db_count_queries'] ++;

		//
		if ( $exit && ! $this->result )
		{
			echo "<pre>\n";
			echo "MySQL query error on MySQL::query ";
			$sapi_type = php_sapi_name();
			if (substr($sapi_type, 0, 3) == 'cgi') {
		    echo "(You are using CGI PHP)\n";
		  } else {
		  	echo "(You are using $sapi_type)\n";
		  }

			echo "mysql error message: <font color=darkred>" .
			  iconv("EUC-KR", "UTF-8", mysql_error($this->link)) . "</font>\n";
			echo "* 만약 위 메세지에 테이블이나 필드가 존재하지 않는다는 에러 메세지가 나온다면, ";
			echo "[<a href='?cate=install&mode=check'>설치검사</a>]를 참고하십시오.\n";
			echo "\n";
			if (function_exists('debug_print_backtrace')) {
				echo @debug_print_backtrace();
			}
			exit;
		}
		return $this->result;
	}
	
	/**
	 * 
	 * @return int 에러가 있으면 에러 번호 리턴. 없으면 0을 리턴. 데이터 접속 관련 문제이면 NULL 리턴.
	 */
	function errno() {
		if ( ! $this->link ) return NULL;
		return mysql_errno($this->link);
	}
	function error() {
		return @mysql_error($this->link);
	}

  function free_result() { return mysql_free_result($this->result); }


	/**
	 * 쿼리 결과 셋에서 하나의 레코드(행)을 리턴한다.
	 *
	 * @param string $fetch 행을 추출하는 방법. empty 이거나 assoc 이면 연관 배열로 추출, row 이면 배열, object 이면 객체로 리턴한다.
	 * @return mixed 추출할 레코드가 더 없으면 FALSE 를 리턴한다.
	 */
	function row( $fetch = '' )
	{
		if ( empty($fetch) || $fetch == 'assoc' )
			return mysql_fetch_assoc($this->result);
		if ( $fetch == 'row' )
			return mysql_fetch_row($this->result);
		if ( $fetch == 'object' )
			return mysql_fetch_object($this->result);
	}
	/**
	 *
	 * 2009 년 02 월 06 일 업데이트
	 *
	 * 동일한 쿼리가 반복해서 연속으로 들어오면, 이전의 값을 리턴한다.
	 *
	 * @change $this->query 함수와 별개의 결과셋을 사용하여, query 를 통해 질의 중에 result 함수를 사용해도
	 * 질의 내용에 변화가 없도록 변경을 했다.
	 * 즉, $this->query 를 통해서 질의를 하고 루프를 통해서 결과 셋을 사용하는 중에 result 함수를 사용해도 영향이 없다는 것이다.
	 *
	 *
	 */
	function result($q)
	{
	  /**
	   * 동일 쿼리가 연속으로 들어왔는지 체크를 한다.
	   */
	  if ( $this->prev_query == $q && $this->prev_query_number == $this->query_count - 1) {
	    
  		if ($this->logfile) $this->logQuery("+same SQL of prev result()");
	    return $this->prev_result_value;
	  }
	  else {
	    $this->prev_query = $q;
	    $this->prev_query_number = $this->query_count;
	  }
	  
	  /**
	   *
	   */
		$back = $this->result;
		$this->query($q);
		$row = mysql_fetch_row($this->result);
		$this->result = $back;
    $this->prev_result_value = $row[0];
		return $row[0];
	}
	/**
	 * result() 는 하나의 필드를 리턴하는데에 비해, 이 함수는 하나의 행을 리턴한다.
	 *
	 * @참고 중첩 쿼리를 해도 기존 쿼리 결과셋의 변화가 없다.
	 *        따라서 중첩 쿼리가 필요할 때, 이 함수를 사용한다.
	 *
	 * @return same as mysql_fetch_assoc
	 */
	function r($q)
	{
		$back = $this->result;
		$this->query($q);
		$row = mysql_fetch_assoc($this->result);
		$this->result = $back;
		return $row;
	}
	
	/** r() 은 하나의 행을 리턴하는데 비해, rows() 는 모든 행을 2차원 배열로 리턴한다.
	 *
	 *
	 */
	function rows($q)
	{
	  $this->query($q);
	  $rows = array();
	  while ( $row = $this->row() )
	  {
	    $rows[] = $row;
	  }
	  return $rows;
	}
	
	/**
	 * mysql_insert_id 함수의 역활을 한다.
	 *
	 * @return int mysql_insert_id 와 같다. 이전 쿼리로 인해서 id 가 만들어지지 않았으면 0, link identifier 가 잘못되었으면 false 가 리턴된다.
	 */
	function insert_id()
	{
		return mysql_insert_id($this->link);
	}
	function affected()
	{
		return mysql_affected_rows($this->link);
	}
	
	  
	/**
	 * 테이블 존재 체크
	 *
	 * @param string $table_name 테이블 이름
	 * @return boolean 테이블 존재시, true. 아니면 false
	 */
	function checkTableExists($table_name) {
		if(!$this->link) return false;
		$query = "show tables like '{$table_name}'";
		$rs = $this->result($query);
		return ! empty($rs);
	}
	/**
	 * @see checkTableExists
	 */
	function checkTable($name) { return $this->checkTableExists($name); }
	/**
	 * 특정 테이블의 특정 필드가 존재하는지 체크한다.
	 *
	 * @param string $table 테이블 이름
	 * @param string $field 검사하고자 하는 테이블 필드
	 * @return boolean 필드가 존재하면 참, 아니면 거짓
	 * <code>
	 * if ( $db->checkField('category', 'dateT2ime') && $db->checkField('post','dateTime') && $db->checkField('user', 'dateTime') )
	 * </code>
	 */
	function checkField($table, $field) {
		if ( $this->checkTable($table) === false ) return false;
		$q = "show columns from $table like '$field'";
		$rs = $this->result($q);
		return ! empty($rs);
	}
	
	/**
	 * queryEx(...) 기존 쿼리에 영향을 주지 않고 쿼리를 날린다. Attach(), Detach() 로 가능
	 * @param bool $exit true 이면 쿼리 에러시 종료한다. false 이면 계속 진행.
	 * @since 2007/04/30 $exit 변수 추가
	 *
	 * @note DB 질의에서 원하지 않는 답이 나온다면, 한번 쯤 이중 쿼리를 하는지 체크해 볼 필요가 있다.
	 *
	 * $db->query(...);		// 여기서 쿼리 한 것을
	 * $db->queryEx(...);	// 여기서 쿼리 해도
	 * $db->row();			// 먼저 쿼리 날린 것을 그대로 사용할 수 있다.
	 */
	function queryEx($q, $exit=true)
	{   
		$back = $this->result;
		$rs		= $this->query($q, $exit);
		$this->result = $back;
		/**@sice 2007/01/04 리턴값 조정. 메인 소스에 적용할 것.*/
		return $rs;
	}
	
	
	
	/**
	 * 중첩 쿼리의 결과셋 파괴를 회피한다.
	 *
	 * 중첩 쿼리를 할 경우, 이전 쿼리의 결과셋이 같이 사용되어 올바른 수행을 할 수 없다.
	 * 문제가 되는 경우는 아래와 같다.
	 *
	 * 쿼리 루프 내에서 다시 쿼리를 할 경우, 이전 결과셋이 파괴된다.
	 * <code>
	 *		$db->query("SELECT word FROM wordcount WHERE done=0 ORDER BY count DESC LIMIT 0 , 25");
	 *		while ( $row = $db->row() )
	 *			$db->query("UPDATE wordcount SET done=1 WHERE word='$row[word]'");
	 * </code>
	 * 이에 대한 해결책으로는 다음과 같다.
	 *
	 * PHP 버젼 5 에서는 아래와 같이 객체 복사를 통해서 할 수 있다.
	 * <code>
	 *		$db->query("SELECT word FROM wordcount WHERE done=0 ORDER BY count DESC LIMIT 0 , 25");
	 *		$dbx = clone $db;
	 *		while ( $row = $db->row() )
	 *			$dbx->query("UPDATE wordcount SET done=1 WHERE word='$row[word]'");
	 * </code>
	 * 그러나 객체 복사식을 사용할 수 없는 경우(버젼이 낮은 경우) 아래와 같이 이 함수를 이용할 수 있다.
	 * Attach, Detach 함수를 사용한다. 버젼에 상관없이 사용가능하다.
	 * <code>
	 *		$db->query("SELECT word FROM wordcount WHERE done=0 ORDER BY count DESC  LIMIT 0 , 25");
	 *		$dbx = new MySQL();
	 *		$dbx->Attach($db->Detach());
	 *		while ( $row = $db->row() )
	 *			$dbx->query("UPDATE wordcount SET done=1 WHERE word='$row[word]'");
	 * </code>
	 *
	 *
	 */
	function Attach($link) {   
		$this->link = $link;   
	}   
	function Detach()   
	{   
		return $this->link;   
	} 

	/**
	 * 인서트 함수
	 *
	 * REPLACE INTO 구문을 사용하지 않는다. 이것은 auto_increment 의 값을 변경 시킬 수 있으므로 (특히 다른 데이터베이스와의 호환에서)  삼가한다.
	 *
	 * @param string $table_name 테이블
	 * @param associative-array $values 키/값을 구성하는 연관 배열
	 *
	 * @add queryEx 를 통해서 중첩 쿼리에서 기존의 결과셋이 변하지 않도록 수정했다.
	 */
	function insert($table_name, $values) {
		foreach($values as $key => $val) {
			$key_list[] = $key;
			$val_list[] = $this->addquotes($val);
		}
	
		$keys = "`".implode("`,`",$key_list)."`";
		$vals = "'".implode("','",$val_list)."'";

		$query = "INSERT INTO `{$table_name}` ({$keys}) VALUES ({$vals})";
		return $this->queryEx($query);
	}
	/**
	 * SQL UPDATE 구문을 실행하는 함수
	 *
	 * @param string $table 테이블 이름
	 * @param associative-array $kvs 필드와 값의 정보를 가지는 키/값 연관 배열
	 * @param associative-array $conds 조건절을 표현하는 연관 배열
	 * @return resource same as query()
	 */
	function update($table, $kvs, $conds)
	{
		foreach($kvs as $k => $v) {
			$v = $this->addquotes($v);
			$sets[] = "`$k`='$v'";
		}
		$set = implode(", ", $sets);
		foreach($conds as $k => $v )
		{
			$arc[] = "`$k`='$v'";
		}
		$cond = implode(" AND ", $arc);
		$q = "UPDATE $table SET $set WHERE $cond";
		/** @since 2007/01/04 query 에서 queryEx 로 실행되게 했다. insert() 에도 적용하고, 메인 소스에도 적용을 시킨다. */
		return $this->queryEx($q);
	}
	
	
	
	function addquotes($data) {
		if(get_magic_quotes_gpc()) $data = stripslashes(str_replace("\\","\\\\",$data));
		if(!is_numeric($data)) $data = @mysql_escape_string($data);
		return $data;
	}


  /** 쿼리 구문을 파일에 기록하게 한다.
   *
   */
	function SetLogQuery($logfile)
	{
		$this->logfile = $logfile;
		$this->uniqid = uniqid(rand());
		if (!$this->logfile_handle = fopen($logfile, 'a')) {
			echo "Cannot open logfile file ($logfile)";
		}
	}
	
	/** 공백으로 구분하여 쿼리 정보를 저장한다.
	 *
	 * 호스트 소모된시간 아이피 +하나의접속이면1부터번호가증가 cate.action 쿼리
	 */
	function logQuery($query)
	{
	  global $cate,$action;
		if (empty($this->logfile_handle)) return;
		$elaps = round($this->qelaps,3);
		
		if ( strlen($query) > 300 ) $query = substr($query, 0, 300);
    // if query_count reaches 100, it add url

    /*
    if ( !  ( $this->query_count % 100)  ) $uri = $_SERVER['REQUEST_URI'];
    else $uri="$cate.$action";
    */
    $uri = $_SERVER['REQUEST_URI'];
    
    $ars = debug_backtrace();
    $scriptname = NULL;
    foreach ( $ars as $back ) {
      if ( strpos($back['file'], "lib") === false ) {
        $scriptname = $back['file'];
        break;
      }
    }
    
		
		/** +2 와 같이 동일한 번호로 들어올 경우, uniqid 로 동일한 접속인지 아닌지를 구분할 수 있다. */
    $sql_log = "$_SERVER[HTTP_HOST] $elaps $_SERVER[REMOTE_ADDR] +{$this->query_count} {$this->uniqid} $scriptname $uri $query\n";
		
		
		
		if (fwrite($this->logfile_handle, $sql_log) === FALSE) {
			echo "Cannot write to logfile";
		}
		// fclose($handle); // 여기서 파일을 닫지 않고, 자동으로 프로그램 종료에 의해서 닫히게한다.
	}
} // eo class



?>