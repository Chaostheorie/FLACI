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
 
 if(!isset($_POST["name"])){
   print '{"result":"FAILED", "error":"name required"}';
   die();
 }
 if(!isset($_POST["GrammarText"])){
   print '{"result":"FAILED", "error":"GrammarText required"}';
   die();
 }
 if(!isset($_POST["JSON"])){
   print '{"result":"FAILED", "error":"JSON required"}';
   die();
 }

 addGrammarHistory($_POST["id"],$_SESSION["userid"]);
 updateGrammar($_POST["id"],$_POST["name"],isset($_POST["description"])?$_POST["description"]:null,$_POST["JSON"],$_POST["GrammarText"],isset($_POST["language"]) ? $_POST["language"] : "", $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 $r["grammar"] = getGrammarByID($_POST["id"]);
 
 print utf8_json_encode($r);
