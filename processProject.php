<?php

//INCLUDES
include_once('header.php');

//RETRIEVE FORM VARIABLES
$values=array();
$values['projectId']=(int) $_POST['projectId'];
$values['name']=mysql_real_escape_string($_POST['name']);
$values['description']=mysql_real_escape_string($_POST['description']);
$values['desiredOutcome']=mysql_real_escape_string($_POST['outcome']);
$values['categoryId']=(int) $_POST['categoryId'];
$values['isSomeday']=$_POST['isSomeday']{0};
$values['deadline']=$_POST['deadline'];
$values['repeat']=(int) $_POST['repeat'];
$values['suppress']=$_POST['suppress']{0};
$values['suppressUntil']=(int) $_POST['suppressUntil'];
$values['type']=$_POST['type']{0};

//CRUDE error checking
if ($values['type']=="s") $typename="Someday/Maybe";
else  $typename="Project";
if ($values['suppress']!="y") $values['suppress']="n";
if ($values['isSomeday']!="y") $values['isSomeday']="n";
if ($values['categoryId']<=0) die ("No category choosen. Project NOT added.");

//SQL CODE AREA
$result=query("newproject",$config,$values,$options,$sort);
$values['newprojectId'] = $GLOBALS['lastinsertid'];
$result=query("newprojectattributes",$config,$values,$options,$sort);
$result=query("newprojectstatus",$config,$values,$options,$sort);

if ($values['type']=="s") echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listProjects.php?type=s" />';
else echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=listProjects.php?type=p" />';

include_once('footer.php');
?>
