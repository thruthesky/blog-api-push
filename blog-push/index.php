<?php
include("xmlrpc.inc"); // Make sure the XMLRPC library is in your path or give the full path

// Get this value from EE under Modules -> Metaweblog API -> URL


$blogs = array();
$blogs[] = array("api_path"=>"http://siteapi.philgo.org/", "username"=>"trycle", "password"=>"asdf99", "id"=>"freetalk");




    $content['title']="This is 4th writing...";
    $content['categories'] = array("");
    $content['description']="DESCRIPTION GOES HERE";
    $content['mt_text_more']="MORE GOES HERE";
    $content['mt_keywords']="KEYWORDS GO HERE";
    
foreach ( $blogs as $blog ) {

    print_r($blog);
        
    $c = new xmlrpc_client($blog["api_path"]);
    //$c->debug = true; // Uncomment this line for debugging info


    $x = new xmlrpcmsg("metaWeblog.newPost",
        array(php_xmlrpc_encode($blog["id"]),
        php_xmlrpc_encode($blog["username"]),
        php_xmlrpc_encode($blog["password"]),
        php_xmlrpc_encode($content),
        php_xmlrpc_encode("1")));

    $c->return_type = "phpvals";
    $r =$c->send($x);
    if ($r->errno=="0")
        echo "<br>Successfully Posted ";
    else {
        echo "<br>There was an error";
        print_r($r);
    }
}

?> 