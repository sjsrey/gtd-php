<?php
//INCLUDES
	include_once('header.php');
	include_once('config.php');

//RETRIEVE URL VARIABLES
	$categoryId =(int) $_GET["categoryId"];

//SQL CODE
	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect");
	mysql_select_db($db) or die ("Unable to select database!");

        //select all categories for selectbox (would make good function!)
        $query = "SELECT categoryId, category, description FROM categories ORDER BY category ASC";
        $result = mysql_query($query) or die("Error in query");
        $cshtml="";
        while($row = mysql_fetch_assoc($result)) {
	        $cshtml .= '			<option value="'.$row['categoryId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
       		if($row['categoryId']==$categoryId) $cshtml .= ' SELECTED';
        	$cshtml .= '>'.stripslashes($row['category'])."</option>\n";
	        }
        mysql_free_result($result);

	//Select category to edit
	$query = "SELECT categoryId, category, description FROM categories WHERE categoryId = '$categoryId'";
	$result = mysql_query($query) or die ("Error in query");
	$row = mysql_fetch_assoc($result);

//PAGE DISPLAY CODE
	echo "<h2>Edit Category</h2>\n";
	echo '<form action="updateCategory.php?categoryId='.$categoryId.'" method="post">'."\n";
	echo '<table border="0">'."\n";
	echo '	<tr><td colspan="2">Category Name</td></tr>'."\n";
	echo '	<tr><td colspan="2">';
	echo '<input type="text" name="category" size="50" value="';
	echo stripslashes($row['category']);
	echo '"></td></tr>'."\n";
	echo '	<tr><td colspan="2">Description</td></tr>'."\n";
	echo '	<tr><td colspan="2">';
	echo '<textarea cols="80" rows="10" name="description" wrap=virtual">';  
	echo stripslashes($row['description']);
	echo "</textarea></td></tr>\n";
	echo "	<tr>\n";
	echo '		<td><input type="checkbox" name="delete" value="y"> Delete category</td>'."\n";
	echo "		<td>Reassign all items to category:&nbsp;\n";
	echo '			<select name="newCategoryId">';
	echo $cshtml;
	echo "			</select>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />\n";
	echo '<input type="submit" class="button" value="Update category" name="submit">'."\n";
	echo '<input type="reset" class="button" value="Reset">'."\n";
	echo "</form>\n";

	include_once('footer.php');
?>
