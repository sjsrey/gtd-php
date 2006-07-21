
<?php
//INCLUDES
	include_once('header.php');
	include_once('config.php');

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
	mysql_select_db($db) or die ("Unable to select database!");

	echo '<p>Goal updated at ';
	echo date('H:i, jS F');
	echo '</p>';

//RETRIEVE URL AND FORM VARIABLES
	$projectId = (int) $_POST['project'];
	$goal = mysql_real_escape_string($_POST['goal']);
	$description = mysql_real_escape_string($_POST['newdescription']);
	$created = $_POST['date'];
	$deadline = $_POST['deadline'];
	$completed = $_POST['completed'];
	$type = $_POST['type']{0};
	$id = (int) $_GET['goalId'];
	$gid = (int) $_GET['goalId'];

//PAGE DISPLAY AREA
	echo "type: $type";
	#echo '<td><select name="project">';
	#echo '<td><select name="type">';
	#echo '<td><select name="time">';
	#echo '<td><input type="text" name="date" size="13" value="';
	#echo '<td><input type="text" name="deadline" size="13" value="';
	#echo '<td><input type="text" name="completed" size="13" value="';
	#echo '<td><textarea cols="80" rows="3" name="goal" wrap=virtual">';
	#echo '<td><textarea cols="80" rows="20" name="newdescription" wrap=virtual">';
	#echo '<input type="submit" value="Update Goal" name="submit">';

	echo $type;

//SQL CODE AREA
	$query = "update goals
		set goal = '$goal', 
		description = '$description',
		created = '$created',
		deadline = '$deadline',
		completed = '$completed',
		type='$type',
		projectId = '$projectId'
		where id = '$gid'";

	$result = mysql_query($query) or die ("Error in query");

	echo "Number of rows updated: ";
	echo mysql_affected_rows();

	mysql_close($connection);


	include_once('footer.php');

?>
