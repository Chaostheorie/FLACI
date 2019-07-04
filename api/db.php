<?php
///////////////////////////////////////////////////////////////////////////////
session_start();
///////////////////////////////////////////////////////////////////////////////
$mysql = null;
$debugSQL = false;
function errorEnd ($txt){
 error_log($txt);
 die();
}
///////////////////////////////////////////////////////////////////////////////
// DB connection functions
///////////////////////////////////////////////////////////////////////////////
function openMysqlDB(){
 global $mysql;
 $mysql_username = "...";
 $mysql_password = "...";
 $mysql_host     = "localhost";
 $mysql_dbname   = "FLACI";

 if($mysql == null){
    $mysql = mysqli_connect($mysql_host,$mysql_username,$mysql_password,$mysql_dbname);
    mysqli_set_charset($mysql,"utf8");
 }
}
///////////////////////////////////////////////////////////////////////////////
function log_mysql_query($Query){
 global $mysql,$debugSQL;
 openMysqlDB();
 if($mysql == null) die();
 // dedect time for each sql query performed
 if($debugSQL){
   $time1 = microtime(true);
   $r = mysqli_query($mysql, $Query );
   $time2 = microtime(true);
   $timespent = $time2 - $time1;
   $myFile = "sqllog.txt";
   $fh = fopen($myFile, 'a');
   fwrite($fh, $timespent.":".$Query."\n");
   fclose($fh);
 } else {
   $r = mysqli_query($mysql, $Query );
 }
 return $r;
}
///////////////////////////////////////////////////////////////////////////////
function mysql_escape($s){
 global $mysql;
 openMysqlDB();
 return mysqli_real_escape_string($mysql,$s);
}
///////////////////////////////////////////////////////////////////////////////
function mysql_lastid(){
 global $mysql;
 openMysqlDB();
 return mysqli_insert_id($mysql);
}
///////////////////////////////////////////////////////////////////////////////
function mysql_get($query,$multi = false){
 $r = null;
 if($multi) $r = array();
 $sql = log_mysql_query($query); 
 if($sql && $sql !== true){
  while ($data = mysqli_fetch_array($sql,MYSQLI_ASSOC)){ if($multi) $r[] = $data; else $r = $data; }
  mysqli_free_result($sql);
 } 
 return $r;
}
///////////////////////////////////////////////////////////////////////////////
function mysql_insert($query){
 log_mysql_query($query); 
 return mysql_lastid();
}
///////////////////////////////////////////////////////////////////////////////
// Main DB functions
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// User management
///////////////////////////////////////////////////////////////////////////////
function generateSalt(){
 while(true){
  $salt = uniqid();
  if(getUserBySalt($salt) == null) return $salt;
 }  
}

function hashPassword($pass) {
 $salt = substr(md5(uniqid(rand(), true)), 0, 25);
 $secure_pass = $salt . sha1($salt . $pass);
 return $secure_pass;
}
function hashPasswordMatch($pass,$input){
 $salt = substr($pass, 0, 25);
 $secure_pass = $salt . sha1($salt . $input);
 return $pass == $secure_pass;
}

function getUserCount(){
 $r = mysql_get("SELECT COUNT(ID) AS Count FROM Users"); 
 return $r["Count"];
}

function getUserByID ($userid) {
 if(!is_numeric($userid)) errorEnd("userid not numeric");
 return mysql_get("SELECT * FROM Users WHERE ID = ".$userid); 
}

function getUserByEmail ($useremail) {
 return mysql_get("SELECT * FROM Users WHERE Email = '".mysql_escape(strtolower($useremail))."'"); 
}

function getUserByPassword ($userid, $password) {
 if(!is_numeric($userid)) errorEnd("userid not numeric");
 $u = getUserByID($userid);
 if(hashPasswordMatch($u["Password"],$password)){
   return $u;
 }
 return null;
}

function getUserByEmailPassword($useremail, $password) {
 $u = getUserByEmail($useremail);
 if($u)
   if(hashPasswordMatch($u["Password"],$password)){
     return $u;
   }
 return null;
}

function getUserBySalt ($salt) {
 return mysql_get("SELECT * FROM Users WHERE Salt = '".mysql_escape($salt)."'"); 
}

function createUser($useremail,$userpassword,$username = "", $usersurname = ""){
 // returns 0 on error
 $uid = mysql_insert("INSERT INTO Users (Email,Password,Salt,Name,Surname) VALUES ('".
        mysql_escape(strtolower($useremail))."','".
        mysql_escape(hashPassword($userpassword))."','".
        mysql_escape(generateSalt())."','".
        mysql_escape($username)."','".
        mysql_escape($usersurname)."')");
 return getUserByID($uid);       
}

function updateUser($userid, $param, $value){
 if(!is_numeric($userid)) errorEnd("userid not numeric");
 if($param == "NewPassword") {
   if($value == "") return;
   $value = hashPassword($value);
   $param = "Password";
   // also add a new Salt to this user for security
   mysql_get("UPDATE Users SET Salt = '".mysql_escape(generateSalt())."' WHERE ID = ".$userid); 
 }
 mysql_get("UPDATE Users SET ".$param." = '".mysql_escape($value)."' WHERE ID = ".$userid); 
}

function sendForgotPasswordLink ($user,$lang = "DE"){
  sleep(1); // spam protection
  $link = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME']."/resetPassword?email=".rawurlencode($user["Email"])."&GUID=".$user["Salt"];
  $translation = file_get_contents("../i18n/".$lang.".json");
  $t = json_decode($translation,true);  

  sendEmail($user["Email"],$t["PASSWORTEMAILSUBJECT"],preg_replace("/\\{\\{LINK\\}\\}/",$link,$t["PASSWORTEMAILBODY"]));  
}


//createUser("mail@michael-hielscher.de","12345","Michael","Hielscher");
///////////////////////////////////////////////////////////////////////////////
function getByGUID($guid){
 $r = mysql_get("SELECT * FROM AutoEditAutomatons WHERE GUID = '".$guid."'");
 if($r) return $r;
 $r = mysql_get("SELECT * FROM kfgeditGrammars WHERE GUID = '".$guid."'");
 if($r) return $r;
 $r = mysql_get("SELECT * FROM TDiagDiagrams WHERE GUID = '".$guid."'");
 if($r) return $r;
 return null;
}

function getNewGUID () {
 $GUID = alphaID( (mt_rand(10000, 65535).'0000000'),false,6,"FLACI"); // FLACI = pass 
 while(getByGUID($GUID)){
   $GUID = alphaID( (mt_rand(10000, 65535).'0000000'),false,6,"FLACI"); // FLACI = pass 
 }
 return $GUID;
}
///////////////////////////////////////////////////////////////////////////////
// TDiagrams
///////////////////////////////////////////////////////////////////////////////
function getTDiagrams($filters){
 $offset = -1;
 $where = ""; 
 $limit = 15;
 $user = isset($filters["userid"])&& is_numeric($filters["userid"]) ? $filters["userid"] : 0;
 if(isset($filters["offset"]) && is_numeric($filters["offset"])) $offset = $filters["offset"];
 if(isset($filters["limit"]) && is_numeric($filters["limit"])) $limit = $filters["limit"];

 if(isset($filters["GUID"]))
   $where .= " AND GUID = '".mysql_escape($filters["GUID"])."' ";  
 else // only check user without GUID
 if(isset($filters["userid"])){
   //$u = getUserByID($filters["userid"]);
   if(isset($filters["withpublics"])) 
     $where .= ' AND (Owner = '.$user.' OR Public = 1) '; else
     $where .= ' AND Owner = '.$user.' ';  
 } 

 if(isset($filters["search"]) && trim($filters["search"]) != "")
   $where .= " AND (MATCH (JSON) AGAINST ('".mysql_escape($filters["search"])."' IN NATURAL LANGUAGE MODE) OR Name LIKE '%".mysql_escape($filters["search"])."%') ";

 if(isset($filters["folderid"]) && is_numeric($filters["folderid"])){
   $where .= " AND (Public = 1 AND PublishFolder = ".$filters["folderid"].")";
 }

 if(isset($filters["count"])){
   $count = mysql_get("SELECT COUNT(*) AS Count FROM TDiagDiagrams WHERE Active = 1 ".$where); 
   return $count["Count"];
 }  

 return mysql_get("SELECT * FROM TDiagDiagrams WHERE Active = 1 ".$where." ORDER BY Changed DESC ".($offset != -1 ? " LIMIT ".$limit." OFFSET ".($offset*$limit) : ''),true); 
}

function getTDiagramByGUID ($guid) {
 return mysql_get("SELECT * FROM TDiagDiagrams WHERE GUID = '".mysql_escape($guid)."'"); 
}

function getTDiagramByID ($diagramid) {
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 return mysql_get("SELECT * FROM TDiagDiagrams WHERE ID = ".$diagramid); 
}

function createTDiagram($ownerid, $name, $description, $CreatedFrom = "", $JSON = "[]"){
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $did = mysql_insert("INSERT INTO TDiagDiagrams (GUID, Name, Description, Owner, CreatedFrom, JSON, Changed) VALUES('".mysql_escape(getNewGUID())."','".mysql_escape($name)."','".mysql_escape($description)."', ".$ownerid.", '".mysql_escape($CreatedFrom)."','".mysql_escape($JSON)."', NOW())"); 
 return getTDiagramByID($did);       
}

function duplicateTDiagram($diagramid, $ownerid){
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $g = getTDiagramByID($diagramid);
 if($g && ($g["Owner"] == $ownerid || $g["Public"] == "1")){
  return createTDiagram($ownerid,$g["Name"],$g["Description"],$g["GUID"],$g["JSON"]);
 }
 return null;
}

function publishTDiagram($diagramid, $folderid, $ownerid){
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 if(!is_numeric($folderid)) errorEnd("folderid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE TDiagDiagrams SET Public = 1, PublishFolder = ".$folderid." WHERE ID = ".$diagramid." AND Owner = ".$ownerid);   
 // publish all embedded compilers as well
}

function unpublishTDiagram($diagramid, $ownerid){
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE TDiagDiagrams SET Public = 0 WHERE ID = ".$diagramid." AND Owner = ".$ownerid);   
 // unpublish all embedded compilers as well
}

function updateTDiagram($diagramid, $name, $description = null, $JSON, $ownerid){
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 if($description){
   mysql_get("UPDATE TDiagDiagrams SET Name = '".mysql_escape($name)."', Description = '".mysql_escape($description)."', JSON = '".mysql_escape($JSON)."', Changed = NOW() WHERE ID = ".$diagramid." AND Owner = ".$ownerid);   
 }else{
   mysql_get("UPDATE TDiagDiagrams SET Name = '".mysql_escape($name)."', JSON = '".mysql_escape($JSON)."', Changed = NOW() WHERE ID = ".$diagramid." AND Owner = ".$ownerid);   
 }
}

function addTDiagramHistory($diagramid, $ownerid){
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $t = getTDiagramByID($diagramid);
 if($t["Owner"] != $ownerid) errorEnd("ownerid not match");
 $history = getTDiagramHistory($diagramid, $ownerid, true);
 if(isset($history[0])) if($history[0]["JSON"] == $t["JSON"]) return false;
 mysql_get("INSERT INTO TDiagDiagramsHistory (Diagram,JSON) VALUES (".$diagramid.",'".mysql_escape($t["JSON"])."')");   
 return true;
}

function getTDiagramHistory($diagramid, $ownerid, $last = false){
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $t = getTDiagramByID($diagramid);
 if($t["Owner"] != $ownerid) errorEnd("ownerid not match");

 $history = mysql_get("SELECT * FROM TDiagDiagramsHistory WHERE Diagram = ".$diagramid." ORDER BY ID DESC ".($last ? "LIMIT 1":""),true);   
 return $history; 
}

function deleteTDiagram($diagramid, $ownerid){
 if(!is_numeric($diagramid)) errorEnd("diagramid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE TDiagDiagrams SET Active = 0, Changed = NOW() WHERE ID = ".$diagramid." AND Owner = ".$ownerid); 
}

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// VCC Compilers
///////////////////////////////////////////////////////////////////////////////
function getCompilers($filters){
 $offset = -1;
 $where = ""; 
 $limit = 15;
 $user = isset($filters["userid"])&& is_numeric($filters["userid"]) ? $filters["userid"] : 0;
 if(isset($filters["offset"]) && is_numeric($filters["offset"])) $offset = $filters["offset"];
 if(isset($filters["limit"]) && is_numeric($filters["limit"])) $limit = $filters["limit"];

/*
 if(isset($filters["userid"])){
   //$u = getUserByID($filters["userid"]);
   if(isset($filters["withpublics"])) 
     $where .= ' AND (Owner = '.$user.' OR Public = 1) '; else
     $where .= ' AND Owner = '.$user.' ';  
 }else{
   //$where .= ' AND Public = 1 '; TODO: make also public = 1 for compilers 
 } 
*/

 if(isset($filters["search"]) && trim($filters["search"]) != "")
   $where .= " AND (MATCH (JSON) AGAINST ('".mysql_escape($filters["search"])."' IN NATURAL LANGUAGE MODE) OR Name LIKE '%".mysql_escape($filters["search"])."%') ";

 if(isset($filters["ID"]) && is_numeric($filters["ID"]))
   $where .= " AND ID = ".$filters["ID"]." ";

 if(isset($filters["count"])){
   $count = mysql_get("SELECT COUNT(*) AS Count FROM VCCCompilers WHERE Active = 1 ".$where); 
   return $count["Count"];
 }  
// error_log("SELECT * FROM VCCCompilers WHERE Active = 1 ".$where." ORDER BY Changed DESC ".($offset != -1 ? " LIMIT ".$limit." OFFSET ".($offset*$limit) : ''));
 $cs = mysql_get("SELECT * FROM VCCCompilers WHERE Active = 1 ".$where." ORDER BY Changed DESC ".($offset != -1 ? " LIMIT ".$limit." OFFSET ".($offset*$limit) : ''),true); 
 for($i=0; $i < count($cs); $i++){
   unset($sc[$i]["JSCode"]);
 }
 return $cs;
}

function getCompilerByID ($compilerid) {
 if(!is_numeric($compilerid)) errorEnd("compilerid not numeric");
 return mysql_get("SELECT * FROM VCCCompilers WHERE ID = ".$compilerid); 
}

// {"name":"expressions","rhs":[[ [{"name":"e", "type":"nt"}],   "alert($1); return $1;"  ]]}
function createCompiler($ownerid, $name, $type = "compiler",$input = "L-in", $output = "L-out", $generator = "LALR", $lastInput = "",
                        $JSON = '{"lex":{"rules":[{"name":"IGNORE","expression":""}]}, "bnf":[{"name":"Start","rhs":[[[],""]]}]}',
                        $jscode=""){
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $sql = "INSERT INTO VCCCompilers (Name, Owner, JSON, Type, InputLanguage, OutputLanguage, Generator, Changed, LastInput, JSCode) VALUES('".mysql_escape($name)."', ".$ownerid.", '".mysql_escape($JSON)."','".mysql_escape($type)."','".mysql_escape($input)."','".mysql_escape($output)."','".mysql_escape($generator)."', NOW(), '".mysql_escape($lastInput)."','".mysql_escape($jscode)."')";
 $did = mysql_insert($sql); 
 return getCompilerByID($did);       
}

function duplicateCompiler($compilerid, $ownerid){
 if(!is_numeric($compilerid)) errorEnd("compilerid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $g = getCompilerByID($compilerid);
 if($g && ($g["Owner"] == $ownerid || $g["Public"] == "1")){
  return createCompiler($ownerid,$g["Name"],$g["Type"],$g["InputLanguage"],$g["OutputLanguage"],$g["Generator"],$g["LastInput"],$g["JSON"],$g["JSCode"]);
 }
 return null;
}

function publishCompiler($compilerid, $ownerid){
 if(!is_numeric($compilerid)) errorEnd("compilerid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE VCCCompilers SET Public = 1 WHERE ID = ".$compilerid." AND Owner = ".$ownerid);   
}

function updateCompiler($compilerid, $name, $JSON, $jscode, $lastinput,$inputLang, $outputLang, $generator, $ownerid){
 if(!is_numeric($compilerid)) errorEnd("compilerid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");

 mysql_get("UPDATE VCCCompilers SET Name = '".mysql_escape($name)."', ".($JSON ? "JSON = '".mysql_escape($JSON)."',":"").($jscode || $jscode === "" ? "JSCode = '".mysql_escape($jscode)."',":"").($lastinput || $lastinput === "" ? " LastInput = '".mysql_escape($lastinput)."',":"").($inputLang || $inputLang === "" ? " InputLanguage = '".mysql_escape($inputLang)."',":"").($outputLang || $outputLang === "" ? " OutputLanguage = '".mysql_escape($outputLang)."',":"").($generator || $generator === "" ? " Generator = '".mysql_escape($generator)."',":"")." Changed = NOW() WHERE ID = ".$compilerid." AND Owner = ".$ownerid);  
}

function addCompilerHistory($compilerid, $ownerid){
 if(!is_numeric($compilerid)) errorEnd("compilerid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $c = getCompilerByID($compilerid);
 if($c["Owner"] != $ownerid) errorEnd("ownerid not match");
 $history = getCompilerHistory($compilerid, $ownerid, true);
 if(isset($history[0])) if($history[0]["JSON"] == $c["JSON"]) return false;
 mysql_get("INSERT INTO VCCCompilersHistory (Compiler,JSON) VALUES (".$compilerid.",'".mysql_escape($c["JSON"])."')");   
 return true;
}

function getCompilerHistory($compilerid, $ownerid, $last = false){
 if(!is_numeric($compilerid)) errorEnd("compilerid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $c = getCompilerByID($compilerid);
 if($c["Owner"] != $ownerid) errorEnd("ownerid not match");

 $history = mysql_get("SELECT * FROM VCCCompilersHistory WHERE Compiler = ".$compilerid." ORDER BY ID DESC ".($last ? "LIMIT 1":""),true);   
 return $history; 
}

function deleteCompiler($compilerid, $ownerid){
 if(!is_numeric($compilerid)) errorEnd("compilerid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE VCCCompilers SET Active = 0, Changed = NOW() WHERE ID = ".$compilerid." AND Owner = ".$ownerid); 
}

///////////////////////////////////////////////////////////////////////////////
// kfgedit Grammars
///////////////////////////////////////////////////////////////////////////////
function getGrammars($filters){
 $offset = -1;
 $where = ""; 
 $limit = 15;
 $user = isset($filters["userid"])&& is_numeric($filters["userid"]) ? $filters["userid"] : 0;
 if(isset($filters["offset"]) && is_numeric($filters["offset"])) $offset = $filters["offset"];
 if(isset($filters["limit"]) && is_numeric($filters["limit"])) $limit = $filters["limit"];

 if(isset($filters["GUID"]))
   $where .= " AND GUID = '".mysql_escape($filters["GUID"])."' ";  
 else // only check user without GUID
 if(isset($filters["userid"])){
   //$u = getUserByID($filters["userid"]);
   if(isset($filters["withpublics"])) 
     $where .= ' AND (Owner = '.$user.' OR Public = 1) '; else
     $where .= ' AND Owner = '.$user.' ';  
 } 

 if(isset($filters["CreatedFrom"]))
   $where .= " AND CreatedFrom = '".mysql_escape($filters["CreatedFrom"])."' ";  


 if(isset($filters["search"]) && trim($filters["search"]) != "")
   $where .= " AND (MATCH (JSON) AGAINST ('".mysql_escape($filters["search"])."' IN NATURAL LANGUAGE MODE) OR Name LIKE '%".mysql_escape($filters["search"])."%') ";

 if(isset($filters["folderid"]) && is_numeric($filters["folderid"])){
   $where .= " AND (Public = 1 AND PublishFolder = ".$filters["folderid"].")";
 }


 if(isset($filters["count"])){
   $count = mysql_get("SELECT COUNT(*) AS Count FROM kgfeditGrammars WHERE Active = 1 ".$where); 
   return $count["Count"];
 }  

 return mysql_get("SELECT * FROM kgfeditGrammars WHERE Active = 1 ".$where." ORDER BY Changed DESC ".($offset != -1 ? " LIMIT ".$limit." OFFSET ".($offset*$limit) : ''),true); 
}

function getGrammarByID ($grammarid) {
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 return mysql_get("SELECT * FROM kgfeditGrammars WHERE ID = ".$grammarid); 
}

function createGrammar($ownerid, $name, $description, $language = "L", $GrammarText = "", $CreatedFrom = "", $JSON = '{"bnf": []}'){
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $sql = "INSERT INTO kgfeditGrammars (GUID, Name, Description, Owner, JSON, Language, CreatedFrom, GrammarText, Changed) VALUES('".mysql_escape(getNewGUID())."','".mysql_escape($name)."','".mysql_escape($description)."', ".$ownerid.", '".mysql_escape($JSON)."','".mysql_escape($language)."','".mysql_escape($CreatedFrom)."','".mysql_escape($GrammarText)."', NOW())";
 $did = mysql_insert($sql); 
 return getGrammarByID($did);       
}

function duplicateGrammar($grammarid, $ownerid){
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $g = getGrammarByID($grammarid);
 if($g && ($g["Owner"] == $ownerid || $g["Public"] == "1")){
  return createGrammar($ownerid,$g["Name"],$g["Description"],$g["Language"],$g["GrammarText"],$g["GUID"],$g["JSON"]);
 }
 return null;
}

function publishGrammar($grammarid, $folderid, $ownerid){
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 if(!is_numeric($folderid)) errorEnd("folderid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE kgfeditGrammars SET Public = 1, PublishFolder = ".$folderid." WHERE ID = ".$grammarid." AND Owner = ".$ownerid);   
}

function unpublishGrammar($grammarid, $ownerid){
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE kgfeditGrammars SET Public = 0 WHERE ID = ".$grammarid." AND Owner = ".$ownerid);   
}

function updateGrammar($grammarid, $name, $description = null, $JSON, $GrammarText, $language, $ownerid){
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 if($description){
   mysql_get("UPDATE kgfeditGrammars SET Name = '".mysql_escape($name)."', Description = '".mysql_escape($description)."', JSON = '".mysql_escape($JSON)."', GrammarText = '".mysql_escape($GrammarText)."', Language = '".mysql_escape($language)."', Changed = NOW() WHERE ID = ".$grammarid." AND Owner = ".$ownerid);   
 }else{
   mysql_get("UPDATE kgfeditGrammars SET Name = '".mysql_escape($name)."', JSON = '".mysql_escape($JSON)."', GrammarText = '".mysql_escape($GrammarText)."', Language = '".mysql_escape($language)."', Changed = NOW() WHERE ID = ".$grammarid." AND Owner = ".$ownerid);   
 }
}

function addGrammarHistory($grammarid, $ownerid){
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $g = getGrammarByID($grammarid);
 if($g["Owner"] != $ownerid) errorEnd("ownerid not match");
 $history = getGrammarHistory($grammarid, $ownerid, true);
 if(isset($history[0])) if($history[0]["JSON"] == $g["JSON"]) return false;
 mysql_get("INSERT INTO kgfeditGrammarsHistory (Grammar,JSON) VALUES (".$grammarid.",'".mysql_escape($g["JSON"])."')");   
 return true;
}

function getGrammarHistory($grammarid, $ownerid, $last = false){
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $g = getGrammarByID($grammarid);
 if($g["Owner"] != $ownerid) errorEnd("ownerid not match");

 $history = mysql_get("SELECT * FROM kgfeditGrammarsHistory WHERE Grammar = ".$grammarid." ORDER BY ID DESC ".($last ? "LIMIT 1":""),true);   
 return $history; 
}

function deleteGrammar($grammarid, $ownerid){
 if(!is_numeric($grammarid)) errorEnd("grammarid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE kgfeditGrammars SET Active = 0, Changed = NOW() WHERE ID = ".$grammarid." AND Owner = ".$ownerid); 
}

///////////////////////////////////////////////////////////////////////////////
// autoedit Automatons
///////////////////////////////////////////////////////////////////////////////
function getAutomatons($filters){
 $offset = -1;
 $where = ""; 
 $limit = 15;
 $user = isset($filters["userid"])&& is_numeric($filters["userid"]) ? $filters["userid"] : 0;
 if(isset($filters["offset"]) && is_numeric($filters["offset"])) $offset = $filters["offset"];
 if(isset($filters["limit"]) && is_numeric($filters["limit"])) $limit = $filters["limit"];

 if(isset($filters["GUID"]))
   $where .= " AND GUID = '".mysql_escape($filters["GUID"])."' ";  
 else // only check user without GUID
 if(isset($filters["userid"])){
   //$u = getUserByID($filters["userid"]);
   if(isset($filters["withpublics"])) 
     $where .= ' AND (Owner = '.$user.' OR Public = 1) '; else
     $where .= ' AND Owner = '.$user.' ';  
 } 

 if(isset($filters["search"]) && trim($filters["search"]) != "")
   $where .= " AND (MATCH (JSON) AGAINST ('".mysql_escape($filters["search"])."' IN NATURAL LANGUAGE MODE) OR Name LIKE '%".mysql_escape($filters["search"])."%') ";

 if(isset($filters["folderid"]) && is_numeric($filters["folderid"])){
   $where .= " AND (Public = 1 AND PublishFolder = ".$filters["folderid"].")";
 }

 if(isset($filters["count"])){
   $count = mysql_get("SELECT COUNT(*) AS Count FROM AutoEditAutomatons WHERE Active = 1 ".$where); 
   return $count["Count"];
 }  

 return mysql_get("SELECT * FROM AutoEditAutomatons WHERE Active = 1 ".$where." ORDER BY Changed DESC ".($offset != -1 ? " LIMIT ".$limit." OFFSET ".($offset*$limit) : ''),true); 
}

function getAutomatonByID ($automatonid) {
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 return mysql_get("SELECT * FROM AutoEditAutomatons WHERE ID = ".$automatonid); 
}

function createAutomaton($ownerid, $name, $description, $type = "DEA", $CreatedFrom = "", $JSON = '{}'){
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $sql = "INSERT INTO AutoEditAutomatons (GUID, Name, Description, Owner, CreatedFrom, JSON, Type, Changed) VALUES('".mysql_escape(getNewGUID())."','".mysql_escape($name)."','".mysql_escape($description)."', ".$ownerid.", '".mysql_escape($CreatedFrom)."','".mysql_escape($JSON)."','".mysql_escape($type)."', NOW())";
 $did = mysql_insert($sql); 
 return getAutomatonByID($did);       
}

function duplicateAutomaton($automatonid, $ownerid){
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $a = getAutomatonByID($automatonid);
 if($a && ($a["Owner"] == $ownerid || $a["Public"] == "1")){
  return createAutomaton($ownerid,$a["Name"],$a["Description"],$a["Type"],$a["GUID"],$a["JSON"]);
 }
 return null;
}

function publishAutomaton($automatonid, $folderid, $ownerid){
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 if(!is_numeric($folderid)) errorEnd("folderid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE AutoEditAutomatons SET Public = 1, PublishFolder = ".$folderid." WHERE ID = ".$automatonid." AND Owner = ".$ownerid);   
}

function unpublishAutomaton($automatonid, $ownerid){
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE AutoEditAutomatons SET Public = 0 WHERE ID = ".$automatonid." AND Owner = ".$ownerid);   
}

function updateAutomaton($automatonid, $name, $description = null, $type, $JSON, $ownerid){
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 if($description){
   mysql_get("UPDATE AutoEditAutomatons SET Name = '".mysql_escape($name)."', Description = '".mysql_escape($description)."', JSON = '".mysql_escape($JSON)."', Type = '".mysql_escape($type)."', Changed = NOW() WHERE ID = ".$automatonid." AND Owner = ".$ownerid);   
 }else{
   mysql_get("UPDATE AutoEditAutomatons SET Name = '".mysql_escape($name)."', JSON = '".mysql_escape($JSON)."', Type = '".mysql_escape($type)."', Changed = NOW() WHERE ID = ".$automatonid." AND Owner = ".$ownerid);   
 }
}

function addAutomatonHistory($automatonid, $ownerid){
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $a = getAutomatonByID($automatonid);
 if($a["Owner"] != $ownerid) errorEnd("ownerid not match");
 $history = getAutomatonHistory($automatonid, $ownerid, true);
 if(isset($history[0])) if($history[0]["JSON"] == $a["JSON"]) return false;
 mysql_get("INSERT INTO AutoEditAutomatonsHistory (Grammar,JSON) VALUES (".$automatonid.",'".mysql_escape($a["JSON"])."')");   
 return true;
}

function getAutomatonHistory($automatonid, $ownerid, $last = false){
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 $a = getAutomatonByID($automatonid);
 if($a["Owner"] != $ownerid) errorEnd("ownerid not match");

 $history = mysql_get("SELECT * FROM AutoEditAutomatonsHistory WHERE Automaton = ".$automatonid." ORDER BY ID DESC ".($last ? "LIMIT 1":""),true);   
 return $history; 
}

function deleteAutomaton($automatonid, $ownerid){
 if(!is_numeric($automatonid)) errorEnd("automatonid not numeric");
 if(!is_numeric($ownerid)) errorEnd("ownerid not numeric");
 mysql_get("UPDATE AutoEditAutomatons SET Active = 0, Changed = NOW() WHERE ID = ".$automatonid." AND Owner = ".$ownerid); 
}

///////////////////////////////////////////////////////////////////////////////
// public folders
///////////////////////////////////////////////////////////////////////////////

function createPublicFolder ($name){
 $sql = "INSERT INTO PublicFolders (Name) VALUES('".mysql_escape($name)."')";
 $did = mysql_insert($sql); 
 return getPublicFolderByID($did);       
}

function getPublicFolderByID($folderid){
 if(!is_numeric($folderid)) errorEnd("folderid not numeric");
 return mysql_get("SELECT * FROM PublicFolders WHERE ID = ".$folderid); 
}

function getPublicFolderByName($name){
 return mysql_get("SELECT * FROM PublicFolder WHERE Name = '".mysql_escape($name)."'"); 
}

function getPublicFolders($type,$all = false){
 if($type == "automaton") $where = ' ID IN (SELECT PublishFolder FROM AutoEditAutomatons WHERE Active = 1 AND Public = 1)';
 if($type == "grammar") $where = ' ID IN (SELECT PublishFolder FROM kgfeditGrammars WHERE Active = 1 AND Public = 1)';
 if($type == "diagram") $where = ' ID IN (SELECT PublishFolder FROM TDiagDiagrams WHERE Active = 1 AND Public = 1)';
 return mysql_get("SELECT * FROM PublicFolders ".(!$all ? 'WHERE '.$where : ''), true); 
}

