<?php

//INCLUDES
	include_once('header.php');

//RETRIEVE URL VARIABLES
	$values['listItemId'] =(int) $_GET["listItemId"];

//SQL CODE AREA
	$query = "SELECT listItemId, item, notes, listId, dateCompleted from listItems where listItemId = {$values['listItemId']}";
	$result = mysql_query($query) or die ("Error in query");
	$currentrow = mysql_fetch_row($result);
	$listItemId = $currentrow[0];
	$item = stripslashes($currentrow[1]);
	$notes = stripslashes($currentrow[2]);
	$listId = $currentrow[3];
	$dateCompleted = $currentrow[4];

	echo "<h1>Edit List Item</h1>\n";

//SELECT listId, title, categoryId, description from list ORDER BY title

	$query = "SELECT * from list ORDER BY title";
	$result = mysql_query($query) or die ("Error in query");
	echo '<form action="updateListItem.php?listItemId='.$values['listItemId'].'" method="post">'."\n";
?>

	<div class='form'>
		<div class='formrow'>

			<label for='newitem' class='left first'>Title:</label>

			<textarea rows="2" name="newitem" wrap=virtual"><?php echo $item; ?></textarea>
		</div>

		<div class='formrow'>
			<label for='list' class='left first'>List:</label>
			<select name='listId' id='list'>

<?php
	while($row = mysql_fetch_row($result)){
		if($row[0]==$listId){
			echo "				<option selected value='" .$row[0] . "'>".stripslashes($row[1])."</option>\n";
		}else{
			echo "				<option value='" .$row[0] . "'>" .stripslashes($row[1])."</option>\n";
		}
	}
?>
			</select>
			<label for='newdateCompleted' class='left notfirst'>Date Completed:</label>
			<input type='text' name='newdateCompleted' id='f_date_b' value=' <?php echo $dateCompleted; ?>'>
                        <button type='reset' id='f_trigger_b'>...</button>
		        <script type="text/javascript">
			    Calendar.setup({
			        inputField     :    "f_date_b",      // id of the input field
			        ifFormat       :    "%Y-%m-%d",       // format of the input field
			        showsTime      :    false,            // will display a time selector
			        button         :    "f_trigger_b",   // trigger for the calendar (button ID)
			        singleClick    :    true,           // single-click mode
			        step           :    1                // show all years in drop-down boxes (instead of every other year as default)
			    });
			</script>
		</div>

		<div class='formrow'>

			<label for='newnotes' class='left first'>Description:</label>

			<textarea rows='10' name='newnotes' id='newnotes' wrap='virtual'><?php echo $notes;?></textarea>
		</div>
	</div>
	<div class='formbuttons'>
		<input type='submit' value='Update List Item' name='submit' />
		<input type='reset' value='Reset' />
		<input type='checkbox' name='delete' id='delete' class='notfirst' value='y' />
		<label for='delete'>Delete&nbsp;List&nbsp;Item</label>
	</div>





<?php
	include_once('footer.php');
?>
