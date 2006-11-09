<?php
//INCLUDES
include_once('header.php');

//RETRIVE FORM VARIABLES
$values['projectId'] = (int) $_POST['projectId'];
$values['contextId'] = (int) $_POST['contextId'];
$values['timeframeId'] = (int) $_POST['timeframeId'];
$values['date'] = $_POST['date'];
$values['deadline'] = $_POST['deadline'];
$values['repeat'] = (int) $_POST['repeat'];
$values['title'] = mysql_real_escape_string($_POST['title']);
$values['description'] = mysql_real_escape_string($_POST['description']);
$values['suppress'] = $_POST['suppress']{0};
$values['suppressUntil'] = (int) $_POST['suppressUntil'];
$values['nextAction'] = $_POST['nextAction']{0};
$values['type']=$_POST['type']{0};


//CRUDE error checking
if ($values['suppress']!="y") $values['suppress']="n";
if ($values['nextaction']!="y") $values['nextaction']="n";
if ($values['projectId']<=0) die ("No project choosen. Item NOT added.");
if ($values['contextId']<=0) die ("No context choosen. Item NOT added.");
if (!isset($values['title'])) die ("No title. Item NOT added.");

//Insert new records
$result = query("newitem",$config,$values);
$values['newitemId'] = $GLOBALS['lastinsertid'];
$result = query("newitemattributes",$config,$values);
$result = query("newitemstatus",$config,$values);

if($values['nextAction']=='y') $result = query("newnextaction",$config,$values);

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=projectReport.php?projectId='.$values['projectId'].'">';

include_once('footer.php');
?>
