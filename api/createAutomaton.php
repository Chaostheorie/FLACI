<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 $widgets = array();

 if(!isset($_SESSION["userid"])){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 if(!isset($_POST["name"])){
   print '{"result":"FAILED", "error":"name required"}';
   die();
 }
 if(!isset($_POST["type"])){
   print '{"result":"FAILED", "error":"type required"}';
   die();
 }

 if(isset($_POST["JSON"]))
   $automaton = createAutomaton($_SESSION["userid"], $_POST["name"],$_POST["description"], $_POST["type"], isset($_POST["CreatedFrom"]) ? $_POST["CreatedFrom"] : "", $_POST["JSON"] ); else
   $automaton = createAutomaton($_SESSION["userid"], $_POST["name"],$_POST["description"], $_POST["type"], isset($_POST["CreatedFrom"]) ? $_POST["CreatedFrom"] : "");

 $r = array();
 $r["result"] = "OK";
 $r["automaton"] = $automaton;

 print utf8_json_encode($r);
