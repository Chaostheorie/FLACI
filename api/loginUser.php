<?php require_once("include.php");
 
 header('Content-Type: application/json');
  
 usleep(250000);
 $user = null;

 if(isset($_POST["uid"]) && isset($_POST["salt"])) {
   $user = getUserBySalt($_POST["salt"]);
   if($user == null){
     print '{"result":"FAILED", "error":"LOGINFAILED"}';
     die();
   }
   if($_POST["uid"] != $user["ID"]){
     print '{"result":"FAILED", "error":"LOGINFAILED"}';
     die();
   }
   $_SESSION["userid"] = $user["ID"];
 }else
 if(isset($_POST["email"]) && isset($_POST["pass"])) {
   $user = getUserByEmailPassword($_POST["email"],$_POST["pass"]);
   if($user == null){
     print '{"result":"FAILED", "error":"LOGINFAILED"}';
     die();
   }
   // return user object
   $_SESSION["userid"] = $user["ID"];
 }else{
  print '{"result":"FAILED", "error":"NOUSER"}';
  die();
 }



 print '{"result":"OK", "user":{"ID":"'.$user["ID"].'", "Email":"'.$user["Email"].'", "Name":"'.$user["Name"].'", "Surname":"'.$user["Surname"].'", "Salt":"'.$user["Salt"].'","Admin":"'.$user["Admin"].'","Publisher":"'.$user["Publisher"].'"}}';

