<?php require_once("include.php");
 
 header('Content-Type: application/json');
  
 usleep(250000);

 if(isset($_GET["id"])) {
   $user = getUserByID($_GET["id"]);
   if($user == null){
     print '{"result":"FAILED", "error":"Login failed"}';
     die();
   }
   $_SESSION["userid"] = $user["ID"];

   // return user object
   print '{"result":"OK", "user":{"ID":"'.$user["ID"].'", "Email":"'.$user["Email"].'", "Name":"'.$user["Name"].'", "Surname":"'.$user["Surname"].'", "Salt":"'.$user["Salt"].'"}}';
   setcookie("salt", $user["Salt"],time()+3600,"/");
   setcookie("userid", $user["ID"],time()+3600,"/");
   die();
 }

print '{"result":"FAILED", "error":"No user ID"}';


