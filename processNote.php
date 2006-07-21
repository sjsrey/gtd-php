<?php
//INCLUDES
include_once('header.php');
include_once('config.php');

//Connect to database	
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//RETRIVE FORM VARIABLES
$date = $_POST['date'];
$title = mysql_real_escape_string($_POST['title']);
$note = mysql_real_escape_string($_POST['note']);

//CRUDE error checking
if (!isset($date)) die ("No date choosen. Note NOT added.");
if (!isset($title)) die ("No title. Note NOT added.");

//Insert new record
//don't forge null

$addquery = "INSERT INTO `tickler` (date,title,note) VALUES ('$date','$title','$note')";
	$addresult = mysql_query($addquery) or die ("Error in query");
	//Retrieve autoincrement value for noteId
	$noteId = mysql_insert_id();

mysql_close($connection);

echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=tickler.php">';

include_once('footer.php');
?>
