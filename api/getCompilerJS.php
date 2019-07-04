<?php require_once("include.php");
 
 header('Content-Type: application/javascript');

 if(!isset($_GET["guid"])) die();
 if(!isset($_GET["id"])) die();

 $t = getTDiagramByGUID ($_GET["guid"]);
 $c = getCompilerByID($_GET["id"]);
 if(!$c || !$t) die();

 // check if $c is part of diagram $t

 print "function generateParser (){ \n\n";
 print $c["JSCode"];
 print " return parser; }";
