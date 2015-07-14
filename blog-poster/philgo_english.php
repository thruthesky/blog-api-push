<?php
include_once "./xmlrpc/xmlrpc.inc";
function newPost($g_blog_url, $user_id, $password, $blogid, $title,$description)
{
  
  $publish = true;
  $client = new xmlrpc_client($g_blog_url);
  $client->setSSLVerifyPeer(false); // 기본값은 true 인데,false로 설정하지 않은면 ssl에러남
  $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
  $struct = array(
    'title' => new xmlrpcval($title,"string"),
    'description' => new xmlrpcval($description,"string")
  );
  $f = new xmlrpcmsg("metaWeblog.newPost",
    array(
      new xmlrpcval($blogid,"string"),
      new xmlrpcval($user_id,"string"),
      new xmlrpcval($password,"string"),
      new xmlrpcval($struct,"struct"),
      new xmlrpcval($publish,"boolean")
    )
  );
  
  
  /*
  $f = new xmlrpcmsg("blogger.getUsersBlogs",
    array(
      new xmlrpcval("ffffffabffffffce6dffffff93ffffffac29ffffffc9fffffff826ffffffdeffffffc9ffffffe43c0b763036ffffffa0fffffff3ffffffa963377716","string"),
      new xmlrpcval($user_id,"string"),
      new xmlrpcval($password,"string")
    )
  );
  */
  
  
  
  
  $f->request_charset_encoding = 'UTF-8';
  //echo '<pre>'; printf($f); exit;
  return $response = $client->send($f);
}






// db connect
include "./mysql.php";
$db = new MySQL("root", "7777", "tmp_witheng", "localhost");
$db->query("set names utf8");
$db->dont_stop_on_many_quries();





//
$bbs_id = "proverb";


$idx_data_group = $db->result("select idx_data_group from board_config where id='$bbs_id'");

$q = "select idx, title, body from board_data where idx_data_group=$idx_data_group and idx_parent=0 and done=0";

print_r("q: $q");
echo "\n";


$rows = $db->rows($q);


// print_r($rows);

print_r(count($rows));
echo "\n";





// 네이버

// 필고
$rpc = "http://siteapi.philgo.com";
$id = "프리셀";
$pw = "asdf99";
$blogid = "english";

// 네이버 두번째 블로그. 영어 공부
$rpc = "https://api.blog.naver.com/xmlrpc";
$id = "thruthesky28";
$pw = "4d27fbb3b6fee70e8f6919be77fb636f";
$blogid = "thruthesky28";



$header =<<<EOH
[  <a href="http://ontue.com/?lesson_ko" target="_blank">온튜 http://www.ontue.com</a> - <a href="http://ontue.com/?lesson_ko" target="_blank">영어 공부 정보 제공 사이트</a> <a href="http://ontue.com/?lesson_ko" target="_blank">http://ontue.com/?lesson_ko</a> ]
<p>
EOH;
$footer =<<<EOH
&nbsp;<p>
<div style="border: 1px solid #ff0090; padding:10px;">
<a href="http://ontue.com/?cate=search&action=user&idx_curriculum=9010&nationality=&city=Input+city+name&gender=a&webcam=a&major=&orderby=v&uid=" target="_blank">
온튜 OnTue.COM
</a>
<br>
<a href="http://ontue.com/?cate=search&action=user&idx_curriculum=9010&nationality=&city=Input+city+name&gender=a&webcam=a&major=&orderby=v&uid=" target="_blank">
수 만명의 온라인 영어 강사 실시간 채팅 및 강사 정보 제공. 사진, 동영상, 이력서, 전화번호 정보 공개!
</a>
<br>
<a href="http://ontue.com" target="_blank">http://ontue.com</a>
</div>
<p>
EOH;

$header =<<<EOH
[  <a href="http://witheng.com/?lesson_ko" target="_blank">온라인영어 http://www.witheng.com</a> - <a href="http://witheng.com/?lesson_ko" target="_blank">가장 저렴하고 가장 효과 높은 온라인 일대일 화상 영어</a> ]
<p>
EOH;
$footer =<<<EOH
&nbsp;<p>
<div style="border: 1px solid #ff0090; padding:10px;">
<a href="http://witheng.com/" target="_blank">
온라인영어 witheng.com
</a>
<br>
<a href="http://witheng.com/?cate=schedule&action=teacher_list" target="_blank">
원어민 영어 강사와 일대일 맞춤 화상 수업!
</a>
<br>
<a href="http://witheng.com" target="_blank">http://witheng.com</a>
</div>
<p>
EOH;


$title_header = "[영어속담] ";


foreach ( $rows as $row ) {
  $return = newPost($rpc, $id, $pw, $blogid, $title_header.$row["title"],$header.$row["body"].$footer);
  $db->query("update board_data set done=1 where idx=$row[idx]");
  echo "Posting... [ $row[idx] ] OK!\n";
  //print_r($return);
  //break;
  
  sleep(60*2);  // 시간적 여유를 가지고 천천히 하나씩 등록해야지 방문자가 많이 찾아온다.
}


?>