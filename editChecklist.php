<?php
	include_once('header.php');

	$checklistId =(int) $_GET["checklistId"];
	$checklistTitle =(string) $_GET['checklistTitle'];

$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

	$query = "SELECT title, description, categoryId FROM checklist WHERE checklistId = '$checklistId'";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	$row = mysql_fetch_array($result);

	echo "<h2>Edit Checklist: $checklistTitle</h2>";
	echo '<form action="updateChecklist.php?checklistId='.$checklistId.'" method="post">';
?>

	<div class='form'>
		<div class='formrow'>
			<label for='title' class='left first'>Checklist Title:</label>
			<input type='text' name='newchecklistTitle' id='title' value='<?php echo $row[0]; ?>'>
		</div>

		<div class='formrow'>
			<label for='category' class='left first'>Category:</label>
			<select name='newcategoryId' id='category'>

<?php
		$catquery = "select * from categories";
		$catresult = mysql_query($catquery) or die("error in query: $catquery.  ".mysql_error());

		while($catrow = mysql_fetch_row($catresult)){
			if ($catrow[0]==$row[2]) echo "				<option value='" .$catrow[0] . "' SELECTED>".$catrow[1]."</option>\n";
			else echo "				<option value='".$catrow[0]."'>".$catrow[1]."</option>\n";
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
		<input type="submit" value="Update Checklist" name="submit">
		<input type="reset" value="Reset">
		<input type='checkbox' name='delete' id='delete' value='y' title='ALL items will be deleted!' /><label for='delete'>Delete&nbsp;Checklist</label>
		<input type='checkbox' name='clear' id='clear' value='clear' class='notfirst'/><label for='clear'>Clear&nbsp;Checklist</label>
	</div>

<?php
	include_once('footer.php');
?>
