<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 if(!isset($_SESSION["userid"])){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 $user = getUserByID($_SESSION["userid"]);
 
 if(!$user || $user["Publisher"] == 0){
   print '{"result":"FAILED", "error":"Permission required"}';
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
 
 if(!isset($_POST["folderid"]) && !isset($_POST["foldername"])){
   print '{"result":"FAILED", "error":"folderid required"}';
   die();
 }

 $folderid = 0;

 if(isset($_POST["folderid"])) $folderid = $_POST["folderid"];

 if(isset($_POST["foldername"])){
   $folder = createPublicFolder($_POST["foldername"]);
   $folderid = $folder["ID"]."";
   error_log("new folder POST:".$folderid);
 }

 publishAutomaton($_POST["id"],$folderid, $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
