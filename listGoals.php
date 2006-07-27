
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
		echo "<h2>Goals</h2>\n";
		echo "<table>\n";
		echo "	<tr>\n";
		echo "		<th>Id</th>\n";
		echo "		<th>Goal</th>\n";
		echo "		<th>Project</th>\n";
		echo "		<th>Type</th>\n";
		echo "		<th>Date Created</th>\n";
		echo "		<th>Deadline</th>\n";
		echo "	</tr>\n";
		
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
		echo "<h4>Nothing was found</h4>\n";
	}

	# completed goals

	$query = 'SELECT * from goals where completed is not null 
	and completed != "0000-00-00" order by created desc, type desc';
	$result = mysql_query($query) or die ("Error in query: $query.  ".mysql_error());


		echo "<h3>Accomplished Goals</h3>\n";
		
	if (mysql_num_rows($result) > 0){
	
		echo "<table>\n";
		echo "	<tr>\n";
		echo "		<th>ID</th>\n";
		echo "		<th>Goal</th>\n";
		echo "		<th>Project</th>\n";
		echo "		<th>Type</th>\n";
		echo "		<th>Date Created</th>\n";
		echo "		<th>Deadline</th>\n";
		echo "		<th>Completed</th>\n";
		echo "	</tr>\n";
		
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
		echo "<h4>Nothing was found</h4>\n";
	}


	mysql_free_result($result);
	mysql_close($connection);
	include_once('footer.php');
?>
