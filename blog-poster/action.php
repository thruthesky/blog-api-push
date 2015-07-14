<?php

//환경설정 (공동으로 사용하는 파일이니 xmlrpc.inc나 config.php로 파일 만들어 include 시켜서 사용하세요 한눈에 보여드리려고 풀어헤쳤습니다.

//반드시 환경설정부는 자신의 블로그 관리에서 확인하신후 정보를 변경하셔야 정상 출력합니다.

$q_blogApiUrl = array(

"tistory"=>"http://blog.qnibus.com/api",

"naver"=>"https://api.blog.naver.com/xmlrpc",

"egloos"=>"https://rpc.egloos.com/rpc1",

"wordpress"=>"http://qnibus.wordpress.com/xmlrpc.php"

);

$q_blogId = array(

"tistory"=>"123456",

"naver"=>"blogid",

"egloos"=>"blogid",

"wordpress"=>"blogid"

);

$q_userName = array(

"tistory"=>"blog@example.com",

"naver"=>"id",

"egloos"=>"blog@example.com",

"wordpress"=>"id"

);

$q_password = array(

"tistory"=>"password",

"naver"=>"api_key",

"egloos"=>"api_key",

"wordpress"=>"password"

);

require_once 'xmlrpc/lib/xmlrpc.inc';

$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';

// 파일 업로드

if (!empty($_FILES['upload']['name']))

{

$tempFile = $_FILES['upload']['tmp_name'];

$fileName = $_FILES['upload']['name'];

$fileType = $_FILES['upload']['type'];

// 파일 바이너리 데이터 가져오기

$fp=fopen($tempFile, "rb");

if (!$fp) return null; // file open failure !!

while( !feof($fp))

{

$filedescription .= fread( $fp, 1024); // 1024 is the server compatible buffer size

flush();

@ob_flush();

}

fclose($fp);

$client = new xmlrpc_client($q_blogApiUrl[$q]);

$newMediaObject = new xmlrpcmsg("metaWeblog.newMediaObject",

array(

new xmlrpcval($q_blogId[$q], "string"),

new xmlrpcval($q_userName[$q], "string"),

new xmlrpcval($q_password[$q], "string"),

new xmlrpcval(

array(

'name'  => new xmlrpcval($fileName, "string"),

'type'  => new xmlrpcval($fileType, "string"),

'bits' => new xmlrpcval($filedescription, "base64"), //파일 바이너리 값

), "struct")

)

);

$newMediaObject->request_charset_encoding = 'UTF-8';

$tempResponse = $client->send($newMediaObject);

$uploadFileName = $tempResponse->value()->me['struct']['url']->me['string'];

$uploadFileSize = getimagesize($tempFile);

$description = '<img src="'.$uploadFileName.'" '.$uploadFileSize[3].' /><br />'.$description.'<p>'.$_SERVER['REMOTE_ADDR'].'</p>';

}

$content = array(

'title'   => new xmlrpcval($title, "string"),

'description'   => new xmlrpcval($description, "string"),

        'dateCreated'  => new xmlrpcval(date("Ymd")."T".date("H:i:s"), "dateTime.iso8601"),

'categories' => new XMLRPCval(array(new XMLRPCval($categories,"string")), "array"),

($q=='naver') ? 'tags' : 'mt_keywords' => new xmlrpcval($keywords),

);

$client = new xmlrpc_client($q_blogApiUrl[$q]);

$message = new xmlrpcmsg("metaWeblog.newPost",

array(

new xmlrpcval($q_blogId[$q], "string"),

new xmlrpcval($q_userName[$q], "string"),

new xmlrpcval($q_password[$q], "string"),

new xmlrpcval($content, "struct"),

new xmlrpcval(true, "boolean")

)

);

$message->request_charset_encoding = 'UTF-8';

$response = $client->send($message);

$value = $response->value();

$postno = $value->scalarval(); // 발행한 결과로 블로그의 포스트 넘버를 반환합니다.

//디버그 확인

echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';

if(!$response->faultCode()) echo "성공적으로 발행되었습니다.";

else echo htmlspecialchars($response->faultString()); //에러 메시지 출력

?>