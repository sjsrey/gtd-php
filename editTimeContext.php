<?php
//INCLUDES
	include_once('header.php');
	include_once('config.php');

//RETRIEVE URL VARIABLES
	$tcId =(int) $_GET["tcId"];

//SQL CODE
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("Unable to select database!");

        //select all timeframes for selectbox (would make good function!)
        $query = "SELECT timeframeId, timeframe FROM timeitems ORDER BY timeframe ASC";
        $result = mysql_query($query) or die("Error in query");
        $cshtml="";
        while($row = mysql_fetch_assoc($result)){
                if($row['timeframeId']==$currentrow['timeframeId']){
                        $cshtml .= "<option selected value='" .$row['timeframeId'] . "' title='".htmlspecialchars(stripslashes($row['description']))."'>" . stripslashes($row['timeframe']) . "</option>\n";
                } else {
                        $cshtml .= "<option value='" .$row['timeframeId'] . "' title='".htmlspecialchars(stripslashes($row['description']))."'>" . stripslashes($row['timeframe']) . "</option>\n";
                }
        }
        mysql_free_result($result);

	//Select timeframe to edit
	$query = "SELECT timeframeId, timeframe, description FROM timeitems WHERE timeframeId = '$tcId'";
	$result = mysql_query($query) or die ("Error in query");
	$row = mysql_fetch_assoc($result);

//PAGE DISPLAY CODE
	echo "<h2>Edit Timeframe</h2>\n";	
	echo '<form action="updateTimeContext.php?tcId='.$tcId.'" method="post">';
	echo '<table border="0">';
	echo '<tr><td colspan="2">Timeframe Name</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<input type="text" name="timeframe" size="50" value="';
	echo stripslashes($row['timeframe']);
	echo '"></td></tr>';
	echo '<tr><td colspan="2">Description</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<textarea cols="80" rows="10" name="description" wrap=virtual">';  
	echo stripslashes($row['description']);
	echo '</textarea></td></tr>';
	echo '<tr><td><input type="checkbox" name="delete" value="y"> Delete Timeframe</td>';
	echo '<td>Reassign Items to timeframe:';
	echo '&nbsp;<select name="ntcId">'.$cshtml.'</select>';
	echo '</td></tr>';
	echo '</table>';
	echo '<br>';
	echo '<input type="submit" class="button" value="Update Timeframe" name="submit">';
	echo '<input type="reset" class="button" value="Reset"></form>';

	include_once('footer.php');
?>
