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

 $grammar = getGrammarByID($_POST["id"]);
 if(!$grammar){
   print '{"result":"FAILED", "error":"grammar not found"}';
   die();
 }
  
 if($grammar["Owner"] != $_SESSION["userid"]){
   print '{"result":"FAILED", "error":"not own grammar"}';
   die();
 }


 if(!isset($_POST["folderid"]) && !isset($_POST["foldername"])){
   print '{"result":"FAILED", "error":"folderid required"}';
   die();
 }

 if(isset($_POST["foldername"])){
   $folder = createPublicFolder($_POST["foldername"]);
   $_POST["folderid"] = $folder["ID"];
 }

 publishGrammar($_POST["id"],$_POST["folderid"], $_SESSION["userid"]);

 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
