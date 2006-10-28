<?php
//INCLUDES
	include_once('header.php');

//RETRIEVE URL VARIABLES
	$contextId =(int) $_GET["contextId"];

//SQL CODE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

        //select all contexts for selectbox (would make good function!)
        $query = "SELECT contextId, name FROM context ORDER BY name ASC";
        $result = mysql_query($query) or die("Error in query");
        $cshtml="";
        while($row = mysql_fetch_assoc($result)){
                if($row['contextId']==$currentrow['contextId']){
                        $cshtml .= "<option selected value='" .$row['contextId'] . "'>" . stripslashes($row['name']) . "</option>\n";
                } else {
                        $cshtml .= "<option value='" .$row['contextId'] . "'>" . stripslashes($row['name']) . "</option>\n";
                }
        }
        mysql_free_result($result);

	//Select context to edit
	$query = "SELECT contextId, name, description FROM context WHERE contextId = '$contextId'";
	$result = mysql_query($query) or die ("Error in query");
	$row = mysql_fetch_assoc($result);

//PAGE DISPLAY CODE
	echo "<h2>Edit Context</h2>\n";
	echo '<form action="updateContext.php?contextId='.$contextId.'" method="post">';
	echo '<table border="0">';
	echo '<tr><td colspan="2">Context Name</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<input type="text" name="name" size="50" value="';
	echo stripslashes($row['name']);
	echo '"></td></tr>';
	echo '<tr><td colspan="2">Description</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<textarea cols="80" rows="10" name="description" wrap=virtual">';
	echo stripslashes($row['description']);
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
