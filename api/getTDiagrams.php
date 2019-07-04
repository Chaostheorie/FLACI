<?php require_once("include.php");
 
 header('Content-Type: application/json');
 
 $filter = array();  
 foreach($_POST as $key => $value){
  $filter[$key] = $value;
 }

 if(isset($_POST["public"]) || isset($_POST["GUID"])) {
  // does not require login
 }else
 if(!isset($_SESSION["userid"])){
  print '{"result":"FAILED", "error":"Authentication required"}';
  die();
 }else
  $filter["userid"] = $_SESSION["userid"]; // override for sure

 $oldNewID = array();

 if(isset($_SESSION["userid"]) && isset($_POST["compilers"])){
  // save and update local Data, check LastChange Date for sync
  $serverCompilers = getCompilers($filter);
  $j = $_POST["compilers"];
  $localCompilers = json_decode($j,true);
  for($i = 0; $i < count($localCompilers); $i++){
   $c = $localCompilers[$i]; 
   if(preg_match("/local/",$c["ID"])){
    // local data, store as new on server
    $nc = createCompiler($_SESSION["userid"], $c["Name"], $c["Type"], $c["InputLanguage"],$c["OutputLanguage"]);
    $oldNewID[] = array("old" => $c["ID"], "new" => $nc["ID"]);
    $nj = json_decode($c["JSON"],true);
    $nj["ID"] = $nc["ID"];
    $c["JSON"] = utf8_json_encode($nj);
    updateCompiler($nc["ID"],$c["Name"],$c["JSON"],$c["LastInput"],$c["InputLanguage"],$c["OutputLanguage"], $_SESSION["userid"]);
   }else{
    // update server data if newer
    for($z = 0; $z < count($serverCompilers); $z++){
     if($serverCompilers[$z]["ID"] == $c["ID"]){
      //error_log("Updating already saved Compiler ".$c["ID"]);
      if($c["Changed"] > $serverCompilers[$z]["Changed"]){
       updateCompiler($c["ID"],$c["Name"],$c["JSON"],$c["LastInput"],$c["InputLanguage"],$c["OutputLanguage"], $_SESSION["userid"]);
      }
     }
    }
   }
  }
 }

 if(isset($_SESSION["userid"]) && isset($_POST["diagrams"])){
  $serverDiagrams = getTDiagrams($filter);
  $j = $_POST["diagrams"];
  // replace new compiler id's
  $localDiagrams = json_decode($j,true);
  for($i = 0; $i < count($localDiagrams); $i++){
   $d = $localDiagrams[$i]; 
   for($w = 0; $w < count($oldNewID); $w++)
    $d["JSON"] = preg_replace('#"'.$oldNewID[$w]["old"].'"#','"'.$oldNewID[$w]["new"].'"',$d["JSON"]);

   if(preg_match("/local/",$d["ID"])){
    // local data, store as new on server
    $nd = createTDiagram($_SESSION["userid"], $d["Name"]);
    $nj = json_decode($d["JSON"],true);
    $nj["ID"] = $nc["ID"];
    $d["JSON"] = utf8_json_encode($nj);
    updateTDiagram($nd["ID"],$d["Name"],$d["JSON"], $_SESSION["userid"]);
   }else{
    // update server data if newer
    for($z = 0; $z < count($serverDiagrams); $z++){
     if($serverDiagrams[$z]["ID"] == $d["ID"]){
      //error_log("Updating already saved Diagram ".$d["ID"]." from:".$d["Changed"]." and Server:".$serverDiagrams[$z]["Changed"]);
      if($d["Changed"] > $serverDiagrams[$z]["Changed"]){
       updateTDiagram($d["ID"],$d["Name"],$d["JSON"], $_SESSION["userid"]);
      }
     }
    }
   }
  }
 }


 $diagrams = getTDiagrams($filter);

 $r = array();
 $r["result"] = "OK";

 $filter["count"] = 1;
 $r["total"] = getTDiagrams($filter);

 $r["diagrams"] = $diagrams;

 print utf8_json_encode($r);
