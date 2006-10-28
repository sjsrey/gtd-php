<?php
	include_once('header.php');

	$values['listId'] =(int) $_GET["listId"];

$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

	$query = "SELECT title, description, categoryId FROM list WHERE listId = '{$values['listId']}'";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	$row = mysql_fetch_array($result);

	echo "<h2>Edit List: {$values['listTitle']}</h2>\n";
	echo '<form action="updateList.php?listId='.$values['listId'].'" method="POST">'."\n";
?>

	<div class='form'>
		<div class='formrow'>
			<label for='title' class='left first'>List Title:</label>
			<input type='text' name='newlistTitle' id='title' value='<?php echo $row[0]; ?>'>
		</div>

		<div class='formrow'>
			<label for='category' class='left first'>Category:</label>
			<select name='newcategoryId' id='category'>
<?php
//SELECT categoryId, category, description from categories
		$catquery = "select * from categories";
		$catresult = mysql_query($catquery) or die("Error in query");
		while($catrow = mysql_fetch_row($catresult)){
			if ($catrow[0]==$row[2]) echo "				<option value='" .$catrow[0] . "' SELECTED>".stripslashes($catrow[1])."</option>\n";
			else echo "				<option value='".$catrow[0]."'>".stripslashes($catrow[1])."</option>\n";
			}
?>
			</select>
		</div>

		<div class='formrow'>
			<label for='description' class='left first'>Description:</label>
			<textarea rows="10" name="newdescription" id="description" wrap="virtual"><?php echo $row[1]; ?></textarea>
		</div>
	</div>
	<div class='formbuttons'>
		<input type="submit" value="Update List" name="submit">
		<input type="reset" class="button" value="Reset">
		<input type="checkbox" name="delete" id='delete' class='notfirst' title="ALL items will be deleted!" value="y" />
                <input type="hidden" name="listId" value="<?php echo $values['listId'] ?>" />
		<label for='delete'>Delete&nbsp;List</label>
	</div>

<?php
	include_once('footer.php');
?>
