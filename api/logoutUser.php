<?php require_once("include.php");
 
 header('Content-Type: application/json');
  
 unset($_SESSION["userid"]);
 
 print '{"result":"OK"}';
  
