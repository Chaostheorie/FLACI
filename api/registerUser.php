<?php require_once("include.php");
 
 header('Content-Type: application/json');
  
 usleep(250000);

 if(isset($_POST["email"]) && isset($_POST["pass"])) {
   if(!preg_match("/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\\.[a-zA-Z0-9-.]+$/",$_POST["email"])){
     print '{"result":"FAILED", "error":"Keine gültige Emailadresse."}';
     die();
   }
   if(strlen($_POST["pass"])< 5){
     print '{"result":"FAILED", "error":"Passwort muss mindestens 5 Zeichen haben."}';
     die();
   }

   $user = getUserByEmailPassword($_POST["email"],$_POST["pass"]);
   if($user != null){
     $_SESSION["userid"] = $user["ID"];
     print '{"result":"OK", "user":{"ID":"'.$user["ID"].'", "Email":"'.$user["Email"].'", "Name":"'.$user["Name"].'", "Surname":"'.$user["Surname"].'", "Salt":"'.$user["Salt"].'"}}';
     die();
   }
   $user = getUserByEmail($_POST["email"]);
   if($user != null){
     print '{"result":"FAILED", "error":"Emailadresse bereits verwendet."}';
     die();
   }
   
   $user = createUser($_POST["email"],$_POST["pass"],$_POST["name"],$_POST["surname"]);  
   // return user object
   $_SESSION["userid"] = $user["ID"];
   print '{"result":"OK", "user":{"ID":"'.$user["ID"].'", "Email":"'.$user["Email"].'", "Name":"'.$user["Name"].'", "Surname":"'.$user["Surname"].'", "Salt":"'.$user["Salt"].'"}}';
   
   die();
 }

print '{"result":"FAILED", "error":"No user ID"}';

