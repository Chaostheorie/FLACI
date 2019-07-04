<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 if(!isset($_POST["type"])){
   print '{"result":"FAILED", "error":"type required"}';
   die();
 }

 $r = array();
 $r["result"] = "OK";
 $r["folders"] = getPublicFolders($_POST["type"],isset($_POST["all"]));
 
 print utf8_json_encode($r);
