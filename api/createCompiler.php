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

 if(isset($_POST["JSON"]))
   $compiler = createCompiler($_SESSION["userid"], $_POST["name"], isset($_POST["type"]) ? $_POST["type"] : "compiler", isset($_POST["input"]) ? $_POST["input"] : "L-in", isset($_POST["output"]) ? $_POST["output"] : "L-out", isset($_POST["generator"]) ? $_POST["generator"] : "LALR",isset($_POST["LastInput"]) ? $_POST["LastInput"] : "", $_POST["JSON"],$_POST["jscode"] ); else
   $compiler = createCompiler($_SESSION["userid"], $_POST["name"], isset($_POST["type"]) ? $_POST["type"] : "compiler", isset($_POST["input"]) ? $_POST["input"] : "L-in", isset($_POST["output"]) ? $_POST["output"] : "L-out", isset($_POST["generator"]) ? $_POST["generator"] : "LALR",isset($_POST["LastInput"]) ? $_POST["LastInput"] : "" );

 $r = array();
 $r["result"] = "OK";
 $r["compiler"] = $compiler;

 print utf8_json_encode($r);
