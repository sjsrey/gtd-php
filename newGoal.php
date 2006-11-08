<?php
//Page outdated, will be rewritten from scratch

include_once('header.php');

$query = "SELECT * from projects order by name";
$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());


if (mysql_num_rows($result) > 0){
    echo "<h2>New Goal</h2>\n";

    echo '<form action="processGoal.php" method="post">'."\n";

    echo "<table>\n";
    echo "	<tr>\n";
    echo "		<td>Project</td>\n";
    echo '		<td><select name="project">'."\n";

    while($row = mysql_fetch_row($result)){
            echo "			<option value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
    }
    echo "		</td>\n";

    mysql_free_result($result);

    echo "		<td>Type</td>\n";
    echo '		<td><select name="type">'."\n";
    echo '			<option value="weekly">weekly</option>'."\n";
    echo '			<option value="monthly">monthly</option>'."\n";
    echo '			<option value="quarterly">quarterly</option>'."\n";
    echo '			<option value="annual">annual</option>'."\n";
    echo "		</td>\n";
    echo "	</tr>\n";
    echo "</table>\n\n";

    echo "<table>\n";
    echo "	<tr>\n";
    echo "		<td>Deadline</td>\n";
    echo '		<td><input type="text" name="deadline" size="13" value="';
    echo "YYYY-MM-DD";
    echo '"></td>'."\n";
    echo "		<td>Date Added:</td>\n";
    echo '		<td><input type="text" name="date" size="13" value="';
    echo date('Y-m-d');
    echo '"></td>'."\n";
    echo "	</tr>\n";
    echo "</table>\n\n";


    echo "<table>\n";
    echo "	<tr>\n";
    echo "		<td>Title</td>\n";
    echo '		<td><textarea cols="60" rows="2" name="title" wrap="virtual"></textarea></td>'."\n";
    echo "	</tr>\n";
    echo "	<tr>\n";
    echo "		<td>Description</td>\n";
    echo '		<td><textarea cols="60" rows="12" name="description" wrap="virtual"></textarea></td>'."\n";
    echo "	</tr>\n";
    echo "</table>\n\n";

    echo '<input type="submit" class="button" value="Add Goal" name="submit">'."\n";
    echo '<input type="reset" class="button" value="Reset">'."\n";
}
else{
        echo "No rows found!\n";
}

include_once('footer.php');
?>
