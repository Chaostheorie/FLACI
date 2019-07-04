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

 $diagram = getTDiagramByID($_POST["id"]);
 if(!$diagram){
   print '{"result":"FAILED", "error":"diagram not found"}';
   die();
 }
 if($diagram["Owner"] != $_SESSION["userid"]){
   print '{"result":"FAILED", "error":"not own diagram"}';
   die();
 }
 
 unpublishTDiagram($_POST["id"], $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
