<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values['tcId'] =(int) $_GET["tcId"];

//SQL CODE
$tshtml=timecontextselectbox($config,$values,$options,$sort);

//Select timeframe to edit
$result = query("selecttimecontext",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
echo "<h2>Edit Timeframe</h2>\n";
echo '<form action="updateTimeContext.php?tcId='.$values['tcId'].'" method="post">'."\n";
echo '<table border="0">'."\n";
echo '	<tr><td colspan="2">Timeframe Name</td></tr>'."\n";
echo '	<tr><td colspan="2">';
echo '<input type="text" name="timeframe" size="50" value="';
echo stripslashes($result[0]['timeframe']);
echo '"></td></tr>'."\n";
echo '	<tr><td colspan="2">Description</td></tr>'."\n";
echo '	<tr><td colspan="2">';
echo '<textarea cols="80" rows="10" name="description" wrap=virtual">';
echo stripslashes($result[0]['description']);
echo "</textarea></td></tr>\n";
echo "	<tr>\n";
echo '		<td><input type="checkbox" name="delete" value="y">Delete Timeframe</td>'."\n";
echo "		<td>Reassign Items to timeframe:&nbsp;\n";
echo '			<select name="ntcId">'."\n";
echo $tshtml;
echo "			</select>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>\n";
echo "<br />\n";
echo '<input type="submit" class="button" value="Update Timeframe" name="submit">'."\n";
echo '<input type="reset" class="button" value="Reset">'."\n";
echo "</form>\n";

include_once('footer.php');
?>
