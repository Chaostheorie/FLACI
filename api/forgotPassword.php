<?php require_once("include.php");
 
 header('Content-Type: application/json');
  
 usleep(250000);
 $user = null;

 if(isset($_POST["email"])) {
   $user = getUserByEmail($_POST["email"]);
   if($user == null){
     print '{"result":"FAILED", "error":"EMAILUNKNOWN"}';
     die();
   }
   sendForgotPasswordLink($user,isset($_POST["language"]) ? $_POST["language"] : "DE");

   print '{"result":"OK"}';
 }else 
  print '{"result":"FAILED", "error":"NOEMAIL"}';

