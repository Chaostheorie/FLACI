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
   $grammar = createGrammar($_SESSION["userid"], $_POST["name"],$_POST["description"], isset($_POST["language"]) ? $_POST["language"] : "L", isset($_POST["GrammarText"]) ? $_POST["GrammarText"] : "", isset($_POST["CreatedFrom"]) ? $_POST["CreatedFrom"] : "", $_POST["JSON"] ); else
   $grammar = createGrammar($_SESSION["userid"], $_POST["name"],$_POST["description"], isset($_POST["language"]) ? $_POST["language"] : "L", isset($_POST["GrammarText"]) ? $_POST["GrammarText"] : "", isset($_POST["CreatedFrom"]) ? $_POST["CreatedFrom"] : "");

 $r = array();
 $r["result"] = "OK";
 $r["grammar"] = $grammar;

 print utf8_json_encode($r);
