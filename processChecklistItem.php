<?php
//INCLUDES
	include_once('header.php');
	include_once('config.php');

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
	mysql_select_db($db) or die ("Unable to select database!");

//RETRIEVE URL AND FORM VARIABLES
	$checklistId=(int) $_POST['checklistId'];
	$item=mysql_real_escape_string($_POST['item']);
	$notes=mysql_real_escape_string($_POST['notes']);

    echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=checklistReport.php?checklistId='.$checklistId.'"';
	echo '<p>New checklist item added at ';
	echo date('H:i, jS F');
	echo '</p>';

	# don't forge null
	$query = "INSERT into checklistItems  values (NULL, '$item', '$notes', '$checklistId', 'n')";
	$result = mysql_query($query) or die ("Error in query");

	//echo "New record inserted with ID ".mysql_insert_id();

	mysql_close($connection);
	include_once('footer.php');

?>
