<?php
//INCLUDES
include_once('gtdfuncs.php');
include_once('header.php');
include_once('config.php');

//RETRIEVE URL VARIABLES
$projectId =(int) $_GET["projectId"];
$type = $_GET['type']{0};

//SQL CODE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect");
mysql_select_db($db) or die ("unable to select database!");

//Get project details
if ($projectId>0) {
	$query= "SELECT projects.projectId, projects.name, projects.description, projects.desiredOutcome, 
		projectstatus.dateCreated, projectstatus.dateCompleted, projectattributes.categoryId, projectattributes.deadline,
		projectattributes.repeat, projectattributes.suppress, projectattributes.suppressUntil, projectattributes.isSomeday 
		FROM projects, projectattributes, projectstatus 
		WHERE projectstatus.projectId=projects.projectId and projectattributes.projectId=projects.projectId and 
		projects.projectId = '$projectId'";
	$result = mysql_query($query) or die ("Error in query");
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	if ($type=$row['isSomeday']=="y") $type='s';
	else $type='p';
	}

//select all categories for dropdown list
$query = "SELECT categories.categoryId, categories.category from categories ORDER BY categories.category ASC";
$result = mysql_query($query) or die("Error in query");
$cshtml="";
while($catrow = mysql_fetch_assoc($result)) {
	if($catrow['categoryId']==$row['categoryId']){
		$cshtml .= "					<option selected value='" .$catrow['categoryId'] . "'>" . stripslashes($catrow['category']) . "</option>\n";
	}
	else {
		$cshtml .= "					<option value='" .$catrow['categoryId'] . "'>" . stripslashes($catrow['category']) . "</option>\n";
	}
}
mysql_free_result($result);

//PAGE DISPLAY CODE
//determine project labels
if ($type=="s") $typename="Someday/Maybe";
else $typename="Project";

if ($projectId>0) {
	echo "	<h2>Edit&nbsp;".$typename."</h2>\n";	
	echo '	<form action="updateProject.php?projectId='.$projectId.'" method="post">'."\n";
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
<?php
echo $cshtml;
?>
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
	echo "				<input type='text' size='10' name='dateCompleted' id='dateCompleted' value='".$currentrow['dateCompleted']."'/>\n";
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
				<label for='suppress' class='left first'>Tickler:</label><input type='checkbox' name='suppress' id='suppress' value='y' <?php if ($row['suppress']=="y") echo "CHECKED "; ?>title='Hides this project from the active view'><label for='suppress'>Tickle&nbsp;</label>
				<input type='text' size='3' name='suppressUntil' id='suppressUntil' value='<?php echo $row['suppressUntil']; ?>'><label for='suppressUntil'>&nbsp;days before deadline</label>
			</div>
			<div class='formrow'>
				<label for='someday' class='left first'>Someday:</label><input type='checkbox' name='isSomeday' id='someday' value='y' title='Places project in Someday file'<?php if ($type=='s') echo ' CHECKED';?>>
			</div>

		</div> <!-- form -->
		<div class='formbuttons'>	
			<input type="hidden" name="type" value="<?php echo $type; ?>" />
<?php
if ($projectId>0) {
	echo '			<input type="submit" class="button" value="Update '.$typename.'" name="submit">'."\n";
	echo "			<input type='checkbox' name='delete' id='delete' value='y' title='Deletes project and ALL associated items'><label for='delete'>&nbsp;Delete&nbsp;Project</label>\n";
} else echo '			<input type="submit" class="button" value="Add '.$typename.'" name="submit">'."\n";
?>
		</div>
<?php if ($projectId>0) { ?>
		<div class='project2'>
			<span class='detail'>Date Added: <?php echo $row['dateCreated']; ?></span>
			<span class='detail'>Last Modified: <?php echo $row['lastModified']; ?></span>
		</div>
<?php } ?>
	</form>


<?php
include_once('footer.php');
?>
