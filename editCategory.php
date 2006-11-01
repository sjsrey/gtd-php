<?php
//INCLUDES
	include_once('header.php');

//RETRIEVE URL VARIABLES
	$values['categoryId'] =(int) $_GET["categoryId"];

//SQL CODE

//select all categories for dropdown list
$cshtml=categoryselectbox($config,$values,$options,$sort);

        //Select category to edit
        $result = query("selectcategory",$config,$values,$options,$sort);
        $row = $result[0];

//PAGE DISPLAY CODE
	echo "<h2>Edit Category</h2>\n";
	echo '<form action="updateCategory.php?categoryId='.$values['categoryId'].'" method="post">'."\n";
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
