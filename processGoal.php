<?php
//Will be rewritten

	include_once('header.php');

	echo '<p>New Next action added at ';
	echo date('H:i, jS F');
	echo '</p>';

	//set up shortname for variables
	$projectId = (int) $_POST['project'];
	$goal = mysql_real_escape_string($_POST['title']);
	$description = mysql_real_escape_string($_POST['description']);
	$date = $_POST['date'];
	$deadline = $_POST['deadline'];
	$type = $_POST['type']{0};


	# don't forge null
	$query = "INSERT into goals  values (NULL, '$goal',
	'$description','$date', '$deadline',  NULL, '$type', '$projectId')";
	$result = mysql_query($query) or die ("Error in query");

	echo "New goal inserted with ID ".mysql_insert_id();

	include_once('footer.php');

?>
