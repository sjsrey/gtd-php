<?php

//INCLUDES
	include_once('header.php');
	include_once('config.php');

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");

//RETRIEVE URL VARIABLES
	$checklistId = (int) $_GET['checklistId'];

//SQL CODE AREA
	$query = "SELECT checklistId, title from checklist order by title";
	$result = mysql_query($query) or die ("Error in query");

	if (mysql_num_rows($result) > 0){
?>
<h2>New Checklist Item</h2>
<form action="processChecklistItem.php" method="POST">
	<div class='form'>		<div class='formrow'>
			<label for='checklist' class='left first'>Checklist:</label>
			<select name='checklistId' id='checklist'>
<?php
		while($row = mysql_fetch_row($result)){
			if($row[0]==$checklistId){
				echo "				<option selected value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}else{
				echo "				<option value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}
		}
?>

			</select>
		</div>

		<div class='formrow'>
			<label for='item' class='left first'>Item:</label>
			<input type='text' name='item' id='item'>
		</div>

		<div class='formrow'>
			<label for='notes' class='left first'>Notes:</label>
			<textarea rows="3" name="notes" id="notes" wrap="virtual"></textarea>
		</div>
	</div>
	<div class='formbuttons'>
		<input type='submit' value='Add List Item' name='submit'>
	</div>
	
<?php
	}
	else{
		echo "No rows found!\n";
	}
	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
