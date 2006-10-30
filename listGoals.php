<?php
	include_once('header.php');

$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//SECTION WILL BE COMPLETELY REWRITTEN

	$query = "SELECT * from goals order by type asc";
	$result = mysql_query($query) or die ("Error in query");


	if (mysql_num_rows($result) > 0){
		echo "<h2>Goals</h2>\n";
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Id</td>\n";
		echo "		<td>Goal</td>\n";
		echo "		<td>Project</td>\n";
		echo "		<td>Type</td>\n";
		echo "		<td>Date Created</td>\n";
		echo "		<td>Deadline</td>\n";
		echo "	</thead>\n";

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			if($row['completed'] == NULL or $row['completed'] =="0000-00-00"){
				$naId = $row['id'];
				$pname = projectName($row['projectId']);

				echo "	<tr>\n";
				echo '		<td align = "left">';
				echo '<a href = "editGoal.php?goalId='.$naId.'">'.$row['id']."</a></td>\n";
				echo '		<td><a href = "editGoal.php?goalId='.$naId.'">'.stripslashes($row['goal'])."</td>\n";
				echo "		<td>".stripslashes($pname)."</td>\n";
				echo "		<td>".$row['type']."</td>\n";
				echo "		<td>".$row['created']."</td>\n";
				echo "		<td>".$row['deadline']."</td>\n";
				echo "	</tr>\n";
			}
		}
		echo "</table>\n";
	}
	else{
		echo "<h4>No goals have been set.</h4>\n";
		echo 'Would you like to create a goal? <a href="newGoal.php">Yes </a>';
		echo '<a href="index.php">No</a>';
	}

	# completed goals

	$query = 'SELECT * from goals where completed is not null
	and completed != "0000-00-00" order by created desc, type desc';
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());


		echo "<h3>Accomplished Goals</h3>\n";

	if (mysql_num_rows($result) > 0){

		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>ID</td>\n";
		echo "		<td>Goal</td>\n";
		echo "		<td>Project</td>\n";
		echo "		<td>Type</td>\n";
		echo "		<td>Date Created</td>\n";
		echo "		<td>Deadline</td>\n";
		echo "		<td>Completed</td>\n";
		echo "	</tead>\n";

		while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				echo "	<tr>\n";
				$naId = $row['id'];
				echo '		<td align = "left">';
				echo '<a href = "editGoal.php?goalId='.$naId.'">'.$row['id']."</a></td>\n";
				echo "		<td>".stripslashes($row['goal'])."</td>\n";
				$pname = projectName($row['projectId']);
				echo "		<td>".stripslashes($pname)."</td>\n";
				echo "		<td>".$row['type']."</td>\n";
				echo "		<td>".$row['created']."</td>\n";
				echo "		<td>".$row['deadline']."</td>\n";
				echo "		<td>".$row['completed']."</td>\n";
				echo "	</tr>\n";
		}
		echo "</table>\n";
	}
	else{
		echo "<h4>No completed goals have been found.</h4>\n";
	}


	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
