<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 if(!isset($_SESSION["userid"])){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 if(!isset($_POST["id"])){
   print '{"result":"FAILED", "error":"id required"}';
   die();
 }

 $grammar = getGrammarByID($_POST["id"]);
 if(!$grammar){
   print '{"result":"FAILED", "error":"grammar not found"}';
   die();
 }
  
 if($grammar["Owner"] != $_SESSION["userid"]){
   print '{"result":"FAILED", "error":"not own grammar"}';
   die();
 }
 
 unpublishGrammar($_POST["id"], $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
