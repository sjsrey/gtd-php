<?php
	include_once('header.php');

	$checklistItemId =$_GET["checklistItemId"];

$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

	$query = "SELECT checklistItemId, item, notes, checklistId, checked from checklistItems where checklistItemId = $checklistItemId";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	$currentrow = mysql_fetch_row($result);
	$checklistItemId = $currentrow[0];
	$item = $currentrow[1];
	$notes = $currentrow[2];
	$checklistId = $currentrow[3];
	$completed = $currentrow[4];

	echo "<h2>Edit Checklist Item</h2>\n";

//SELECT checklistId, title, categoryId, description from checklist ORDER BY title

	$query = "SELECT * from checklist ORDER BY title";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	echo '<form action="updateChecklistItem.php?checklistItemId='.$checklistItemId.'" method="post">'."\n";
?>
	<div class='form'>
		<div class='formrow'>
			<label for='checklist' class='left first'>Checklist:</label>
			<select name='checklistId' id='checklist'>
<?php
	while($row = mysql_fetch_row($result)){
		if($row[0]==$checklistId){
			echo "				<option selected value='" .$row[0] . "'>" . stripslashes($row[1]) . "</option>\n";
		}else{
			echo "				<option value='" .$row[0] . "'>" . stripslashes($row[1]) . "</option>\n";
		}
	}
//	mysql_free_result($result);
?>
			</select>
			<input type='checkbox' name='completed' id='completed' class='notfirst' value='y'<?php if ($completed=='y') echo ' CHECKED'; ?>>
			<label for='completed'>Item Completed</label>
		</div>

		<div class='formrow'>
			<label for='newitem' class='left first'>Item:</label>
			<textarea rows='2' name='newitem' wrap='virtual'><?php echo $item; ?></textarea>
		</div>

		<div class='formrow'>
			<label for='notes' class='left first'>Notes:</label>
			<textarea rows="3" name="newnotes" id="notes" wrap="virtual"><?php echo $notes; ?></textarea>
		</div>
	</div>
	<div class='formbuttons'>
		<input type='submit' value='Update Checklist Item' name='submit' />
		<input type="reset" value="Reset" />
		<input type='checkbox' name='delete' id='delete' value='delete' /><label for='delete'>Delete&nbsp;Checklist&nbsp;Item</label>
	</div>


<?php
	include_once('footer.php');
?>
