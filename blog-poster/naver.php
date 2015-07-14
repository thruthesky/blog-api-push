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


// 네이버
$rpc = "https://api.blog.naver.com/xmlrpc";
$id = "thruthesky";
$pw = "1373751a9d3562317676a0bfe17ac33d";
$blogid = "thruthesky";

// 필고
/*
$rpc = "http://siteapi.philgo.com";
$id = "흔적";
$pw = "asdf99";
$blogid = "pds";
*/

$return = newPost($rpc, $id, $pw, $blogid, '글 쓰기 테스트!','글 쓰기 테스트를 해 봅니다.^^; Hello! '. date("r"));

print_r($return);

?>