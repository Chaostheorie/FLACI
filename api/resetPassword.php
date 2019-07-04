<?php require_once("include.php");
 
 header('Content-Type: application/json');
  
 usleep(250000);

 if(isset($_POST["email"]) && isset($_POST["salt"]) && isset($_POST["pass"])) {
   $user = getUserByEmail($_POST["email"]);
   if($user == null || $user["Salt"] != $_POST["salt"]){
     print '{"result":"FAILED", "error":"EMAILTOKENMISSMATCH"}';
     die();
   }
   $p = trim($_POST["pass"]);
   if(strlen($p) < 5){
     print '{"result":"FAILED", "error":"PASSWORDTOOSHORT"}';
     die();
   }
   
   updateUser($user["ID"],"NewPassword",$_POST["pass"]);
   updateUser($user["ID"],"Salt",generateSalt());
   
   $user = getUserByID($user["ID"]);

   $_SESSION["userid"] = $user["ID"];

   print '{"result":"OK", "user":{"ID":"'.$user["ID"].'", "Email":"'.$user["Email"].'", "Name":"'.$user["Name"].'", "Surname":"'.$user["Surname"].'", "Salt":"'.$user["Salt"].'","Admin":"'.$user["Admin"].'"}}';

   die();
 }

print '{"result":"FAILED", "error":"NODATA"}';

