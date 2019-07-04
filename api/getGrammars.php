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


 if(isset($_SESSION["userid"]) && isset($_POST["grammars"])){
  // save and update local Data, check LastChange Date for sync
  $serverGrammars = getGrammars($filter);
  $j = $_POST["grammars"];
  $localGrammars = json_decode($j,true);
  for($i = 0; $i < count($localGrammars); $i++){
   $c = $localGrammars[$i]; 
   if(preg_match("/local/",$c["ID"])){
    // local data, store as new on server
    $nc = createGrammar($_SESSION["userid"], $c["Name"], $c["Language"]);
    $nj = json_decode($c["JSON"],true);
    $nj["ID"] = $nc["ID"];
    $c["JSON"] = utf8_json_encode($nj);
    updateGrammar($nc["ID"],$c["Name"],$c["JSON"],$c["GrammarText"],$c["Language"], $_SESSION["userid"]);
   }else{
    // update server data if newer
    for($z = 0; $z < count($serverGrammars); $z++){
     if($serverGrammars[$z]["ID"] == $c["ID"]){
      if($c["Changed"] > $serverGrammars[$z]["Changed"]){
       updateGrammar($c["ID"],$c["Name"],$c["JSON"],$c["GrammarText"],$c["Language"], $_SESSION["userid"]);
      }
     }
    }
   }
  }
 }

 $grammars = getGrammars($filter);

 $r = array();
 $r["result"] = "OK";

 $filter["count"] = 1;
 $r["total"] = getGrammars($filter);

 $r["grammars"] = $grammars;

 print utf8_json_encode($r);
