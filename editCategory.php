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
	        $cshtml .= '<option value="'.$row['categoryId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
       		if($row['categoryId']==$categoryId) $cshtml .= ' SELECTED';
        	$cshtml .= '>'.stripslashes($row['category']).'</option>\n';
	        }
        mysql_free_result($result);

	//Select category to edit
	$query = "SELECT categoryId, category, description FROM categories WHERE categoryId = '$categoryId'";
	$result = mysql_query($query) or die ("Error in query");
	$row = mysql_fetch_assoc($result);

//PAGE DISPLAY CODE
	echo "<h2>Edit Category</h2>\n";	
	echo '<form action="updateCategory.php?categoryId='.$categoryId.'" method="post">';
	echo '<table border="0">';
	echo '<tr><td colspan="2">Category Name</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<input type="text" name="category" size="50" value="';
	echo stripslashes($row['category']);
	echo '"></td></tr>';
	echo '<tr><td colspan="2">Description</td></tr>';
	echo '<tr><td colspan="2">';
	echo '<textarea cols="80" rows="10" name="description" wrap=virtual">';  
	echo stripslashes($row['description']);
	echo '</textarea></td></tr>';
	echo '<tr><td><input type="checkbox" name="delete" value="y"> Delete category</td>';
	echo '<td>Reassign all items to category:';
	echo '&nbsp;<select name="newCategoryId">'.$cshtml.'</select>';
	echo '</td></tr>';
	echo '</table>';
	echo '<br>';
	echo '<input type="submit" class="button" value="Update category" name="submit">';
	echo '<input type="reset" class="button" value="Reset"></form>';

	include_once('footer.php');
?>
