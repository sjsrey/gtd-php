<?php

//INCLUDES
	include_once('header.php');

//RETRIEVE URL AND FORM DATA
$values['categoryId']=(int)$_POST['categoryId'];


//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");


//select all categories for dropdown list
$cshtml=categoryselectbox($config,$values,$options,$sort);


//PAGE DISPLAY CODE
	echo "<h2>Lists</h2>\n";

        //category selection form
        echo '<div id="filter">'."\n";
        echo '<form action="listList.php" method="post">'."\n";
        echo "<p>Category:&nbsp;\n";
        echo '<select name="categoryId">'."\n";
        echo '  <option value="0">All</option>'."\n";
        echo $cshtml."</select>\n";
        echo '<input type="submit" class="button" value="Filter" name="submit" title="Filter list by category" />'."\n";
        echo "</p>\n";
        echo "</form>\n";
        echo "</div>\n";


	if ($values['categoryId']==NULL) $values['categoryId']='0';
	if ($values['categoryId']=='0') {
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
				WHERE list.categoryId=categories.categoryId AND list.categoryId='{$values['categoryId']}'
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
