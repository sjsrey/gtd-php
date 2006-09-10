<?php
	include_once('header.php');
	include_once('config.php');

	$listId =(int) $_GET["listId"];
	$listTitle =(string) $_GET['listTitle'];

	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");
	
	$query = "SELECT title, description, categoryId FROM list WHERE listId = '$listId'";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	$row = mysql_fetch_array($result);

	echo "<h2>Edit List: $listTitle</h2>\n";	
	echo '<form action="updateList.php?listId='.$listId.'" method="POST">'."\n";
?>

	<div class='form'>		<div class='formrow'>
			<label for='title' class='left first'>List Title:</label>
			<input type='text' name='newlistTitle' id='title' value='<?php echo $row[0]; ?>'>
		</div>

		<div class='formrow'>			<label for='category' class='left first'>Category:</label>			<select name='newcategoryId' id='category'>
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
		<label for='delete'>Delete&nbsp;List</label>
	</div>

<?php
	include_once('footer.php');
?>
