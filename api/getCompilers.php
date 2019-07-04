<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 $widgets = array();

 if(!isset($_SESSION["userid"]) && !isset($_POST["withpublics"]) ){
   print '{"result":"FAILED", "error":"Authentication required"}';
   die();
 }

 $filter = array();  
 foreach($_POST as $key => $value){
  $filter[$key] = $value;
 }
 unset($filter["userid"]); 
 if(isset($_SESSION["userid"]))
   $filter["userid"] = $_SESSION["userid"]; else
   $filter["userid"] = 0;

 $compilers = getCompilers($filter);

 $r = array();
 $r["result"] = "OK";
 $r["compilers"] = $compilers;

 print utf8_json_encode($r);
