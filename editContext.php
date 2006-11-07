<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values['contextId'] =(int) $_GET["contextId"];

//SQL CODE
$cshtml = contextselectbox($config,$values,$options,$sort);

$row=query("selectcontext",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
	echo "<h2>Edit Context</h2>\n";
	echo '<form action="updateContext.php?contextId='.$values['contextId'].'" method="post">';
	echo '<table border="0">';
	echo '<tr><td colspan="2">Context Name</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<input type="text" name="name" size="50" value="';
	echo stripslashes($row[0]['name']);
	echo '"></td></tr>';
	echo '<tr><td colspan="2">Description</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<textarea cols="80" rows="10" name="description" wrap=virtual">';
	echo stripslashes($row[0]['description']);
	echo '</textarea></td></tr>';
	echo '<tr><td><input type="checkbox" name="delete" value="y"> Delete Context</td>';
	echo '<td>Reassign all items to context:';
	echo '&nbsp;<select name="newContextId">'.$cshtml.'</select>';
	echo '</td></tr>';
	echo '</table>';
	echo '<br>';
	echo '<input type="submit" class="button" value="Update Context" name="submit">';
	echo '<input type="reset" class="button" value="Reset"></form>';

	include_once('footer.php');
?>
