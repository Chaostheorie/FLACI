<?php require_once("include.php");
 
 header('Content-Type: application/json');

 if(!isset($_SESSION["userid"])){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 if(!isset($_POST["name"])){
   print '{"result":"FAILED", "error":"name required"}';
   die();
 }

 if(isset($_POST["JSON"]))
   $diagram = createTDiagram($_SESSION["userid"], $_POST["name"],$_POST["description"],isset($_POST["CreatedFrom"]) ? $_POST["CreatedFrom"] : "",$_POST["JSON"] ); else 
   $diagram = createTDiagram($_SESSION["userid"], $_POST["name"],$_POST["description"],isset($_POST["CreatedFrom"]) ? $_POST["CreatedFrom"] : "" );

 $r = array();
 $r["result"] = "OK";
 $r["diagram"] = $diagram;

 print utf8_json_encode($r);
