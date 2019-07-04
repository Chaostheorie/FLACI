<?php require_once("include.php");
 
 header('Content-Type: application/json');

 $c = getCompilerByID($_POST["id"]);

 if(!$c){
   print '{"result":"FAILED", "error":"compiler not found"}';
   die();
 }

 $r = array();
 $r["result"] = "OK";
 $r["compiler"] = $c;

 print utf8_json_encode($r);
