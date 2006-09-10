<?php

//INCLUDES
	include_once('header.php');
	include_once('config.php');

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
	mysql_select_db($db) or die ("unable to select database!");

	echo "<h2>Lists</h2>\n";

//SJK  Allows viewing of checklists by category
	echo '<form action="listList.php" method="post">'."\n";
	echo '<p>Category:&nbsp;';
	$categoryId=(int)$_POST['categoryId'];

//SELECT categoryId, category.name FROM categories ORDER BY category.name ASC

	$query = "select * from categories";
	$result = mysql_query($query) or die("Error in query");
	echo '<select name="categoryId">'."\n";
	echo '	<option value="0">All</option>'."\n";
	while($row = mysql_fetch_row($result)){
	if($row[0]==$categoryId){
			echo "	<option selected value='" .$row[0] . "'>".stripslashes($row[1])."</option>\n";
	} else {
			echo "	<option value='" .$row[0] . "'>" .stripslashes($row[1]). "</option>\n";
			}
	}
	echo "</select>\n";
	mysql_free_result($result);
	echo '<input type="submit" align="right" class="button" value="Update" name="submit">'."\n";
	echo "</p>\n";  // $


	if ($categoryId==NULL) $categoryId='0';
	if ($categoryId=='0') {
	   $query = "SELECT list.listId, list.title, list.description,
				list.categoryId, categories.category 
				FROM list, categories 
				WHERE list.categoryId=categories.categoryId 
				ORDER BY categories.category ASC";
		$result = mysql_query($query) or die ("Error in query");
	} else {
	   $query = "SELECT list.listId, list.title, list.description,
				list.categoryId, categories.category 
				FROM list, categories 
				WHERE list.categoryId=categories.categoryId AND list.categoryId='$categoryId' 
				ORDER BY categories.category ASC";
		$result = mysql_query($query) or die ("Error in query");
	}


	if (mysql_num_rows($result) > 0){
		echo "<p>Select list for report.</p>\n";
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Category</td>\n";
		echo "		<td>Title</td>\n";
		echo "		<td>Description</td>\n";
		echo "	</thead>\n";
		while($row = mysql_fetch_row($result)){
			echo "	<tr>\n";
			echo "		<td>".stripslashes($row[4])."</td>\n";
			echo '		<td><a href="listReport.php?listId='.$row[0].'&listTitle='.urlencode($row[1]).'">'.stripslashes($row[1])."</a></td>\n";
			echo "		<td>".stripslashes($row[2])."</td>\n";
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
