
<?php
	include_once('header.php');
	include_once('config.php');
	include_once('gtdfuncs.php');

	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");

//SECTION WILL BE COMPLETELY REWRITTEN

	$query = "SELECT * from goals order by type asc";
	$result = mysql_query($query) or die ("Error in query");


	if (mysql_num_rows($result) > 0){
		echo "<h2>Goals</h2>";
		echo "<table>";
		echo '<tr>';
		echo '<th>Id</th>';
		echo '<th>Goal</th>';
		echo '<th>Project</th>';
		echo '<th>Type</th>';
		echo '<th>Date Created</th>';
		echo '<th>Deadline</th>';
		echo '</tr>';
		
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			if($row['completed'] == NULL or $row['completed'] =="0000-00-00"){
				$naId = $row['id'];
				$pname = projectName($row['projectId']);

				echo "<tr>";
				echo '<td align = "left">';
				echo '<a href = "editGoal.php?goalId='.$naId.'">'.$row['id'].'</a></td>';
				echo '<td><a href = "editGoal.php?goalId='.$naId.'">'.stripslashes($row['goal']).'</td>';
				echo "<td>".stripslashes($pname)."</td>";
				echo "<td>".$row['type']."</td>";
				echo "<td>".$row['created']."</td>";
				echo "<td>".$row['deadline']."</td>";
				echo "</tr>";
			}
		}
		echo "</table>";
	}
	else{
		echo "<h4>Nothing was found</h4>";
	}

	# completed goals

	$query = 'SELECT * from goals where completed is not null 
	and completed != "0000-00-00" order by created desc, type desc';
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());


		echo "<h3>Accomplished Goals</h3>";
		
	if (mysql_num_rows($result) > 0){
	
		echo "<table>";
		echo '<tr>';
		echo '<th>ID</th>';
		echo '<th>Goal</th>';
		echo '<th>Project</th>';
		echo '<th>Type</th>';
		echo '<th>Date Created</th>';
		echo '<th>Deadline</th>';
		echo '<th>Completed</th>';
		echo '</tr>';
		
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				echo "<tr>";
				$naId = $row['id'];
				echo '<td align = "left">';
				echo '<a href = "editGoal.php?goalId='.$naId.'">'.$row['id'].'</a></td>';
				echo "<td>".stripslashes($row['goal'])."</td>";
				$pname = projectName($row['projectId']);
				echo "<td>".stripslashes($pname)."</td>";
				echo "<td>".$row['type']."</td>";
				echo "<td>".$row['created']."</td>";
				echo "<td>".$row['deadline']."</td>";
				echo "<td>".$row['completed']."</td>";
				echo "</tr>";
		}
		echo "</table>";
	}
	else{
		echo "<h4>Nothing was found</h4>";
	}


	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
