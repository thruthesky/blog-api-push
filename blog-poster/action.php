<?php

//ȯ�漳�� (�������� ����ϴ� �����̴� xmlrpc.inc�� config.php�� ���� ����� include ���Ѽ� ����ϼ��� �Ѵ��� �����帮���� Ǯ�����ƽ��ϴ�.

//�ݵ�� ȯ�漳���δ� �ڽ��� ��α� �������� Ȯ���Ͻ��� ������ �����ϼž� ���� ����մϴ�.

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

// ���� ���ε�

if (!empty($_FILES['upload']['name']))

{

$tempFile = $_FILES['upload']['tmp_name'];

$fileName = $_FILES['upload']['name'];

$fileType = $_FILES['upload']['type'];

// ���� ���̳ʸ� ������ ��������

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

'bits' => new xmlrpcval($filedescription, "base64"), //���� ���̳ʸ� ��

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

$postno = $value->scalarval(); // ������ ����� ��α��� ����Ʈ �ѹ��� ��ȯ�մϴ�.

//����� Ȯ��

echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';

if(!$response->faultCode()) echo "���������� ����Ǿ����ϴ�.";

else echo htmlspecialchars($response->faultString()); //���� �޽��� ���

?>