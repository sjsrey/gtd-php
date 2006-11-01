<?php
	include_once('header.php');

//RETRIEVE URL VARIABLES
	$goalId =(int) $_GET["goalId"];

//NEW GOALS SECTION- TOTALREWRITE REQUIRED (Vs new pages Goals-new for transition)

	$query = "SELECT * from goals where id = $goalId";
	$result = mysql_query($query) or die ("Error in query");
	$currentrow = mysql_fetch_assoc($result);
	$projectId = $currentrow['projectId'];
	$goal = stripslashes($currentrow['goal']);
	$description = stripslashes($currentrow['description']);
	$created = $currentrow['created'];
	$deadline = $currentrow['deadline'];
	$completed = $currentrow['completed'];
	$type = $currentrow['type'];

	echo "type: $type";
	echo "<h1>Edit Goal</h1>";
	$query = "SELECT * from projects order by name";
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());
	echo '<form action="updateGoal.php?goalId='.$goalId.'" method="post">';
	echo '<table border="0">';
	echo '<tr><td>Project</td>';
	echo '<td><select name="project">';
	while($row = mysql_fetch_row($result)){
		if($row[0]==$projectId){
			echo "<option selected value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
		}else{
			echo $row[0];
			echo $projectId;
			echo "<option value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
		}
	}
	echo $row[0];
	echo $projectId;
	echo '</td>';
	mysql_free_result($result);
	echo '<td>Type</td>';
	echo '<td><select name="type">';
	if($type=='weekly'){
		echo '<option selected value="weekly">weekly</option>';
		echo '<option value="quarterly">quarterly</option>';
	}else{
		echo '<option value="weekly">weekly</option>';
		echo '<option selected value="quarterly">quarterly</option>';
	}
	echo '</td>';
	echo '<td>Date Added:</td>';
	echo '<td><input type="text" name="date" size="13" value="';
	echo $created;
	echo '"></td>';
	echo '<td>Deadline</td>';
	echo '<td><input type="text" name="deadline" size="13" value="';
	echo $deadline;
	echo '"></td>';
	echo '<td>Completed</td>';
	echo '<td><input type="text" name="completed" size="13" value="';
	echo $completed;
	echo '"></td>';

	echo '</tr>';
	echo "</table>";
	echo "<table>";
	echo '<tr><td>Goal</td>';
	echo '<td><textarea cols="80" rows="3" name="goal" wrap=virtual">';
	echo $goal;
	echo '</textarea></td>';
	echo '<tr><td>Description</td>';
	echo '<td><textarea cols="80" rows="20" name="newdescription" wrap=virtual">';
	echo $description;
	echo ' </textarea></td>';
	echo '</tr></table>';
	echo '<br>';
	echo '<input type="submit" value="Update Goal" name="submit">';
	echo '<input type="reset" value="Reset">';
	include_once('footer.php');
?>
