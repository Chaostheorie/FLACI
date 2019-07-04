<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 $widgets = array();

 if(!isset($_SESSION["userid"])){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 if(!isset($_POST["id"])){
   print '{"result":"FAILED", "error":"id required"}';
   die();
 }

 deleteAutomaton($_POST["id"], $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";

 print utf8_json_encode($r);
