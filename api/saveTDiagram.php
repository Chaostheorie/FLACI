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
 
 $diagram = getTDiagramByID($_POST["id"]);
 if(!$diagram){
   print '{"result":"FAILED", "error":"diagram not found"}';
   die();
 }
 if($diagram["Owner"] != $_SESSION["userid"]){
   print '{"result":"FAILED", "error":"not own diagram"}';
   die();
 }
 
 if(!isset($_POST["name"])){
   print '{"result":"FAILED", "error":"name required"}';
   die();
 }
 if(!isset($_POST["JSON"])){
   print '{"result":"FAILED", "error":"JSON required"}';
   die();
 }

 addTDiagramHistory($_POST["id"],$_SESSION["userid"]);
 updateTDiagram($_POST["id"],$_POST["name"],isset($_POST["description"])?$_POST["description"]:null,$_POST["JSON"], $_SESSION["userid"]);

/*
[{"id":1,"type":"compiler","x":110,"y":90,"input":"BC","output":"L-out","written":"VCC","source":"1451","name":"Compiler","dockedTop":null,"dockedLeft":null,"dockedRight":null,"dockedBottom":null,"dockedBottomLeft":null,"dockedBottomRight":null,"code":"","preset":false},{"id":2,"type":"compiler","x":170,"y":230,"input":"L-In","output":"L-Out","written":"L","source":"","name":"Compiler 1","dockedTop":null,"dockedLeft":null,"dockedRight":null,"dockedBottom":null,"dockedBottomLeft":null,"dockedBottomRight":null,"code":""}]
*/
 $json = json_decode($_POST["JSON"],true);
 for($i=0; $i < count($json); $i++){
   if(isset($json[$i]["source"]) && $json[$i]["source"] != "" && (!isset($json[$i]["preset"]) || $json[$i]["preset"] != "true")){
     updateCompiler($json[$i]["source"], $json[$i]["name"], null, null, null,$json[$i]["input"], $json[$i]["output"], "", $_SESSION["userid"]);

   }
 }

 $r = array();
 $r["result"] = "OK";
 
 print utf8_json_encode($r);
