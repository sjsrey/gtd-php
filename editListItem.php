<?php

//INCLUDES
	include_once('header.php');
	include_once('config.php');

//RETRIEVE URL VARIABLES
	$listItemId =(int) $_GET["listItemId"];

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");

//SQL CODE AREA
	$query = "SELECT listItemId, item, notes, listId, dateCompleted from listItems where listItemId = $listItemId";
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
	echo '<form action="updateListItem.php?listItemId='.$listItemId.'" method="post">'."\n";
	echo '<table border="0">'."\n";
	echo "	<tr>\n";
	echo "		<td>List</td>\n";
	echo '		<td><select name="list">'."\n";
	while($row = mysql_fetch_row($result)){
		if($row[0]==$listId){
			echo "			<option selected value='" .$row[0] . "'>".stripslashes($row[1])."</option>\n";
		}else{
			echo "			<option value='" .$row[0] . "'>" .stripslashes($row[1])."</option>\n";
		}
	}
	echo "		</td>\n";
	mysql_free_result($result);
	
	echo '		<td colspan=2">Date Completed:&nbsp;'."\n";
	echo '			<form action="#" method="get">'."\n";
	echo '			<input type="text" size="10" name="newdateCompleted" id="f_date_b" value="';
	echo $dateCompleted;
	echo '"/><button type="reset" id="f_trigger_b">...</button>'."\n";
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
		</td>
	</tr>

<?php

	echo "</table>\n\n";

	echo "<table>\n";
	echo "	<tr><td>Title</td></tr>\n";
	echo '	<tr><td><textarea cols="80" rows="2" name="newitem" wrap=virtual">';
	echo $item;
	echo "</textarea></td></tr>\n";
	echo "	<tr><td>Description</td></tr>\n";
	echo '	<tr><td><textarea cols="80" rows="4" name="newnotes" wrap=virtual">';
	echo $notes;
	echo "</textarea></td></tr>\n";
	echo '	<tr><td>Delete List Item&nbsp;<input type="checkbox" name="delete" value="y"></td></tr>'."\n";
	echo "</table>\n";
	echo "<br />\n";
	echo '<input type="submit" value="Update List Item" name="submit">'."\n";
	echo '<input type="reset" value="Reset">'."\n";
	
	include_once('footer.php');
?>
