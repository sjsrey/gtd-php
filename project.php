<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values['projectId'] =(int) $_GET["projectId"];
$values['type'] = $_GET['type']{0};

//Get project details
if ($values['projectId']>0) {
        $result = query("selectproject",$config,$values,$options,$sort);
	$row = $result[0];
	if ($values['type']=$row['isSomeday']=="y") $values['type']='s';
	else $values['type']='p';
	}

//select all categories for dropdown list
$values['categoryId'] = $row['categoryId'];
$cashtml=categoryselectbox($config,$values,$options,$sort);

//PAGE DISPLAY CODE
//determine project labels
if ($values['type']=="s") $typename="Someday/Maybe";
else $typename="Project";

if ($values['projectId']>0) {
	echo "	<h2>Edit&nbsp;".$typename."</h2>\n";
	echo '	<form action="updateProject.php?projectId='.$values['projectId'].'" method="post">'."\n";
}

else {
	echo "	<h2>New&nbsp;".$typename."</h2>\n";
	echo '	<form action="processProject.php" method="post">'."\n";
}

?>
		<div class='form'>
			<div class='formrow'>
				<label for='name' class='left first'>Project Name:</label><input type='text' name='name' id='name' value='<?php echo stripslashes($row['name']);?>'>
			</div>
			<div class='formrow'>
				<label for='category' class='left first'>Category:</label>
				<select name='categoryId' id='category'>
                                <?php echo $cashtml; ?>
				</select>
				<label for='deadline' class='left'>Deadline:</label>
<?php
if ($row['deadline']=="0000-00-00" || $row['deadline']==NULL) {
    echo "				<input type='text' size='10' name='deadline' id='deadline' value=''/>\n";
} else {
    echo "				<input type='text' size='10' name='deadline' id='deadline' value='".$row['deadline']."'/>\n";
}
?>
				<button type='reset' id='f_trigger_b'>...</button>
					<script type='text/javascript'>
						Calendar.setup({
							inputField     :    'deadline',      // id of the input field
							ifFormat       :    '%Y-%m-%d',       // format of the input field
							showsTime      :    false,            // will display a time selector
							button         :    'f_trigger_b',   // trigger for the calendar (button ID)
							singleClick    :    true,           // single-click mode
							step           :    1                // show all years in drop-down boxes (instead of every other year as default)
						});
					</script>
				<label for='dateCompleted' class='left'>Completed:</label>
<?php
if ($row['dateCompleted']=="0000-00-00" || $row['dateCompleted']==NULL) {
	echo "				<input type='text' size='10' name='dateCompleted' id='dateCompleted' value=''/>\n";
} else {
	echo "				<input type='text' size='10' name='dateCompleted' id='dateCompleted' value='".$row['dateCompleted']."'/>\n";
}

?>
				<button type='reset' id='f_trigger_c'>...</button>
					<script type='text/javascript'>
						Calendar.setup({
							inputField     :    'dateCompleted',      // id of the input field
							ifFormat       :    '%Y-%m-%d',       // format of the input field
							showsTime      :    false,            // will display a time selector
							button         :    'f_trigger_c',   // trigger for the calendar (button ID)
							singleClick    :    true,           // single-click mode
							step           :    1                // show all years in drop-down boxes (instead of every other year as default)
						});
					</script>
			</div>
			<div class='formrow'>
				<label for='description' class='left first'>Description:</label>
				<textarea rows='8' name='description' id='description' class='big' wrap='virtual'><?php echo stripslashes($row['description']) ?></textarea>
			</div>
			<div class='formrow'>
				<label for='outcome' class='left first'>Desired Outcome:</label>
				<textarea rows='4' name='outcome' id='outcome' class='big' wrap='virtual'><?php echo stripslashes($row['desiredOutcome']) ?></textarea>
			</div>
			<div class='formrow'>
				<label for='repeat' class='left first'>Repeat every</label><input type='text' name='repeat' id='repeat' size='3' value='<?php echo $row['repeat'] ?>'><label for='repeat'>&nbsp;days</label>
			</div>
			<div class='formrow'>
				<label for='suppress' class='left first'>Tickler:</label><input type='checkbox' name='suppress' id='suppress' value='y' <?php if ($row['suppress']=="y") echo "CHECKED "; ?>title='Puts this project in the tickler file, hiding from active view'><label for='suppress'>Tickle&nbsp;</label>
				<input type='text' size='3' name='suppressUntil' id='suppressUntil' value='<?php echo $row['suppressUntil']; ?>'><label for='suppressUntil'>&nbsp;days before deadline</label>
			</div>
			<div class='formrow'>
				<label for='someday' class='left first'>Someday:</label><input type='checkbox' name='isSomeday' id='someday' value='y' title='Places project in Someday file'<?php if ($values['type']=='s') echo ' CHECKED';?>>
			</div>

		</div> <!-- form -->
		<div class='formbuttons'>
			<input type="hidden" name="type" value="<?php echo $values['type']; ?>" />
<?php
if ($values['projectId']>0) {
	echo '			<input type="submit" class="button" value="Update '.$typename.'" name="submit">'."\n";
	echo "			<input type='checkbox' name='delete' id='delete' value='y' title='Deletes project and ALL associated items'><label for='delete'>&nbsp;Delete&nbsp;Project</label>\n";
} else echo '			<input type="submit" class="button" value="Add '.$typename.'" name="submit">'."\n";
?>
		</div>
<?php if ($values['projectId']>0) { ?>
		<div class='project2'>
			<span class='detail'>Date Added: <?php echo $row['dateCreated']; ?></span>
			<span class='detail'>Last Modified: <?php echo $row['lastModified']; ?></span>
		</div>
<?php } ?>
	</form>


<?php
include_once('footer.php');
?>
2