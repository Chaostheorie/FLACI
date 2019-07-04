<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 if(!isset($_SESSION["userid"])){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 $user = getUserByID($_SESSION["userid"]);

 if(!$user){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 if($user["Admin"] != "1"){
   print '{"result":"FAILED", "error":"Admin required"}';
   die();
 }

 if(!isset($_POST["JSON"])){
   print '{"result":"FAILED", "error":"JSON required"}';
   die();
 }
 
 if(!isset($_POST["lang"])){
   print '{"result":"FAILED", "error":"language required"}';
   die();
 }

 file_put_contents("../i18n/".$_POST["lang"].".json",prettyPrintJSON($_POST["JSON"]));
  

 $r = array();
 $r["result"] = "OK";
 print utf8_json_encode($r);
