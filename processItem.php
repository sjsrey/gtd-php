<?php
//INCLUDES
include_once('header.php');

//RETRIVE FORM VARIABLES
$projectId = (int) $_POST['projectId'];
$contextId = (int) $_POST['contextId'];
$timeframeId = (int) $_POST['timeframeId'];
$date = $_POST['date'];
$deadline = $_POST['deadline'];
$repeat = (int) $_POST['repeat'];
$title = mysql_real_escape_string($_POST['title']);
$description = mysql_real_escape_string($_POST['description']);
$suppress = $_POST['suppress']{0};
$suppressUntil = (int) $_POST['suppressUntil'];
$nextAction = $_POST['nextAction']{0};
$type=$_POST['type']{0};


//CRUDE error checking
if ($suppress!="y") $suppress="n";
if ($nextaction!="y") $nextaction="n";
if ($projectId<=0) die ("No project choosen. Item NOT added.");
if ($contextId<=0) die ("No context choosen. Item NOT added.");
if (!isset($title)) die ("No title. Item NOT added.");

//Insert new record





$addquery = "INSERT INTO `items` (title,description) VALUES ('$title','$description')";
	$addresult = mysql_query($addquery) or die ("Error in query.");
	//Retrieve autoincrement value for itemId
	$itemId = mysql_insert_id();

	$addquery = "INSERT INTO `itemattributes` (itemId,type,projectId,contextId,timeframeId,deadline,`repeat`,suppress,suppressUntil) VALUES ('$itemId','$type','$projectId','$contextId','$timeframeId','$deadline','$repeat','$suppress','$suppressUntil')";
	$addresult = mysql_query($addquery) or die ("Error in query.");

	$addquery = "INSERT INTO `itemstatus` (itemId,dateCreated) VALUES ('$itemId',CURRENT_DATE)";
	$addresult = mysql_query($addquery) or die ("Error in query.");

if($nextAction=='y') {
	$query = "INSERT INTO nextactions (projectId,nextaction) VALUES ('$projectId','$itemId')
		ON DUPLICATE KEY UPDATE nextaction='$itemId'";
	$result = mysql_query($query) or die ("Error in query.");
	}

echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=projectReport.php?projectId='.$projectId.'">';

mysql_close($connection);
include_once('footer.php');
?>
