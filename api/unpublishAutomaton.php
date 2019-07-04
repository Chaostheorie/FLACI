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

 $automaton = getAutomatonByID($_POST["id"]);
 if(!$automaton){
   print '{"result":"FAILED", "error":"automaton not found"}';
   die();
 }
  
 if($automaton["Owner"] != $_SESSION["userid"]){
   print '{"result":"FAILED", "error":"not own automaton"}';
   die();
 }
 
 unpublishAutomaton($_POST["id"], $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
