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

 if(!isset($_POST["folderid"]) && !isset($_POST["foldername"])){
   print '{"result":"FAILED", "error":"folderid required"}';
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

 if(isset($_POST["foldername"])){
   $folder = createPublicFolder($_POST["foldername"]);
   $_POST["folderid"] = $folder["ID"];
 }

 publishTDiagram($_POST["id"],$_POST["folderid"], $_SESSION["userid"]);

 // also publish all included compilers?
 $d = json_decode($diagram["JSON"],true);
 for($i = 0; $i < count($d); $i++){
   if( !$d[$i]["preset"] && ($d[$i]["type"] == 'compiler' || $d[$i]["type"] == 'interpreter')){
     publishCompiler($d[$i]["source"],$_SESSION["userid"]);
   }
 }
 
 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
