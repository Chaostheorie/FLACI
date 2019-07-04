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

 $compiler = getCompilerByID($_POST["id"]);
 if(!$compiler){
   print '{"result":"FAILED", "error":"compiler not found"}';
   die();
 }
 if($compiler["Owner"] != $_SESSION["userid"]){
   print '{"result":"FAILED", "error":"not own compiler"}';
   die();
 }
 
 if(!isset($_POST["name"])){
   print '{"result":"FAILED", "error":"name required"}';
   die();
 }

 addCompilerHistory($_POST["id"],$_SESSION["userid"]);
 updateCompiler($_POST["id"],$_POST["name"],isset($_POST["JSON"]) ? $_POST["JSON"] : null,
                isset($_POST["jscode"]) ? $_POST["jscode"] : null, isset($_POST["lastinput"]) ? $_POST["lastinput"] : null, 
                isset($_POST["input"]) ? $_POST["input"] : "", isset($_POST["output"]) ? $_POST["output"] : "", isset($_POST["generator"]) ? $_POST["generator"] : "", $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 $r["compiler"] = getCompilerByID($_POST["id"]);
 
 print utf8_json_encode($r);
