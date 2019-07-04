<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 $filter = array();  
 foreach($_POST as $key => $value){
  $filter[$key] = $value;
 }

 if(isset($_POST["public"]) || isset($_POST["GUID"])){
  // does not require login
 }else
 if(!isset($_SESSION["userid"])){
  print '{"result":"FAILED", "error":"Authentication required"}';
  die();
 }else
  $filter["userid"] = $_SESSION["userid"]; // override for sure


 if(isset($_SESSION["userid"]) && isset($_POST["automatons"])){
  // save and update local Data, check LastChange Date for sync
  $serverAutomatons = getAutomatons($filter);
  $j = $_POST["automatons"];
  $localAutomatons = json_decode($j,true);
  for($i = 0; $i < count($localAutomatons); $i++){
   $c = $localAutomatons[$i]; 
   if(preg_match("/local/",$c["ID"])){
    // local data, store as new on server
    $nc = createAutomaton($_SESSION["userid"], $c["Name"], $c["Type"]);
    $nj = json_decode($c["JSON"],true);
    $nj["ID"] = $nc["ID"];
    $c["JSON"] = utf8_json_encode($nj);
    updateAutomaton($nc["ID"],$c["Name"],$c["Type"],$c["JSON"], $_SESSION["userid"]);  
   }else{
    // update server data if newer
    for($z = 0; $z < count($serverAutomatons); $z++){
     if($serverAutomatons[$z]["ID"] == $c["ID"]){
      if($c["Changed"] > $serverAutomatons[$z]["Changed"]){
       updateAutomaton($c["ID"],$c["Name"],$c["Type"],$c["JSON"], $_SESSION["userid"]);
      }
     }
    }
   }
  }
 }

 $automatons = getAutomatons($filter);

 $r = array();
 $r["result"] = "OK";

 $filter["count"] = 1;
 $r["total"] = getAutomatons($filter);

 $r["automatons"] = $automatons;

 print utf8_json_encode($r);
