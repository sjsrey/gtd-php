<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM VARIABLES
$values = array();
$values['checklistItemId'] = (int) $_GET["checklistItemId"];

//SQL CODE
$result = query("selectchecklistitem",$config,$values,$options,$sort);
$currentrow = $result[0];
$cshtml = checklistselectbox($config,$values,$options,$sort);

//PAGE DISPLAY CODE
	echo "<h2>Edit Checklist Item</h2>\n";

	echo '<form action="updateChecklistItem.php?checklistItemId='.$currentrow['checklistItemId'].'" method="post">'."\n";
?>
	<div class='form'>
		<div class='formrow'>
			<label for='checklist' class='left first'>Checklist:</label>
			<select name='checklistId' id='checklist'>
                        <?php echo $cshtml;?>
			</select>
			<input type='checkbox' name='completed' id='completed' class='notfirst' value='y'<?php if ($currentrow['completed']=='y') echo ' CHECKED'; ?>>
			<label for='completed'>Item Completed</label>
		</div>

		<div class='formrow'>
			<label for='newitem' class='left first'>Item:</label>
			<textarea rows='2' name='newitem' wrap='virtual'><?php echo htmlspecialchars(stripslashes($currentrow['item'])); ?></textarea>
		</div>

		<div class='formrow'>
			<label for='notes' class='left first'>Notes:</label>
			<textarea rows="3" name="newnotes" id="notes" wrap="virtual"><?php echo htmlspecialchars(stripslashes($currentrow['notes'])); ?></textarea>
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
