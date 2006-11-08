<?php
//INCLUDES
	include_once('header.php');

//RETRIEVE URL VARIABLES
	$values['noteId']= (int) $_GET["noteId"];

	//select note details
	if ($values['noteId']>0) {
            $result = query("selectnote",$config,$values,$sort,$options);
	    if ($result['status']!=-1) $currentrow = $result[0];
            else echo "Nothing found.";
            }

//PAGE DISPLAY CODE

	if ($values['noteId']>0) {
		echo "<h2>Edit Note</h2>";
		echo '<form action="updateNote.php?noteId='.$values['noteId'].'" method="post">';
		}

	else {
		echo "<h2>New Note</h2>";
		echo '<form action="processNote.php" method="post">';
		}

	echo'<table>';

echo '		<td>Date:&nbsp;'."\n";
	echo '			<form action="#" method="get">'."\n";
	echo '			<input type="text" size="10" name="date" id="f_date_b" value="'.$currentrow['date'].'"/><button type="reset" id="f_trigger_b">...</button>'."\n";
?>

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
<?php
        echo '</td><td align="right"><input type="checkbox" name="delete" value="y" />Delete Note</td></tr>';
	echo '<tr><td colspan="2">Title</td></tr>';
	echo '<tr><td colspan="2"><input type="text" size="79" name="title" value="'.stripslashes($currentrow['title']).'"></td></tr>';
	echo '<tr><td colspan="2">Details</td></tr>';
	echo '<tr><td colspan="2"><textarea cols="77" rows="12" name="note" wrap=virtual">'.stripslashes($currentrow['note']).'</textarea></td></tr>';
	echo '</table>';

	echo '<br />';
	if ($values['noteId']>0) echo '<input type="submit" class="button" value="Update Note" name="submit">';
	else echo '<input type="submit" class="button" value="Add Note" name="submit">';
	echo '<input type="reset" class="button" value="Reset">';


	include_once('footer.php');
?>
