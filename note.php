<?php
//INCLUDES
	include_once('gtdfuncs.php');
	include_once('header.php');
	include_once('config.php');

//RETRIEVE URL VARIABLES
	$noteId= (int) $_GET["noteId"];

//SQL CODE
	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect");
	mysql_select_db($db) or die ("Unable to select database!");

	//select note details
	if ($noteId>0) {
	$query = "SELECT ticklerId, title, note, date FROM tickler WHERE ticklerId='$noteId'";
	$result = mysql_query($query) or die ("Error in query");
	$currentrow = mysql_fetch_assoc($result);
	mysql_free_result($result);
	}

//PAGE DISPLAY CODE
	
	if ($noteId>0) {
		echo "<h2>Edit Note</h2>";
		echo '<form action="updateNote.php?noteId='.$noteId.'" method="post">';
		}

	else {
		echo "<h2>New Note</h2>";
		echo '<form action="processNote.php" method="post">';
		}

	echo'<table>';
	echo '<td>Date:&nbsp;';
        DateDropDown(365,"date",$currentrow['date']);
        echo '</td><td><input type="checkbox" name="delete" value="y" />Delete Note</td></tr>';
	echo '<tr><td colspan="2">Title</td></tr>';
	echo '<tr><td colspan="2"><input type="text" size="79" name="title" value="'.stripslashes($currentrow['title']).'"></td></tr>';
	echo '<tr><td colspan="2">Details</td></tr>';
	echo '<tr><td colspan="2"><textarea cols="77" rows="12" name="note" wrap=virtual">'.stripslashes($currentrow['note']).'</textarea></td></tr>';
	echo '</table>';

	echo '<br />';
	if ($noteId>0) echo '<input type="submit" class="button" value="Update Note" name="submit">';
	else echo '<input type="submit" class="button" value="Add Note" name="submit">';
	echo '<input type="reset" class="button" value="Reset">';
	include_once('footer.php');
?>
