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
 
 if(!isset($_POST["name"])){
   print '{"result":"FAILED", "error":"name required"}';
   die();
 }
 if(!isset($_POST["type"])){
   print '{"result":"FAILED", "error":"name required"}';
   die();
 }
 if(!isset($_POST["JSON"])){
   print '{"result":"FAILED", "error":"JSON required"}';
   die();
 }

 addAutomatonHistory($_POST["id"],$_SESSION["userid"]);
 updateAutomaton($_POST["id"],$_POST["name"],isset($_POST["description"])?$_POST["description"]:null,$_POST["type"],$_POST["JSON"], $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
