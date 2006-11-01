<?php
	include_once('header.php');

    switch ($config['dbtype']) {
        case "frontbase":require("frontbaseparts.inc.php");
        break;
        case "msql":require("msqlparts.inc.php");
        break;
        case "mysql":require("mysqlparts.inc.php");
        break;
        case "mssql":require("mssqlparts.inc.php");
        break;
        case "postgres":require("postgresparts.inc.php");
        break;
        case "sqlite":require("sqliteparts.inc.php");
        break;
        }


$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

        $values['listId'] = (int) $_GET['listId'];

    $result = query("selectlist",$config,$values,$options,$sort);

    if ($result!="-1") {
        $row=$result[0];

	echo "<h1>List Report: ".$row['title']."</h1>\n";
	echo '<form action="processListUpdate.php?listId='.$row['listId'].'" method="POST">'."\n";

	echo '[ <a href="editList.php?listId='.$row['listId'].'">Edit List</a> ]'."\n";
	echo "<br />\n";

	echo '<h2><a href = "newListItem.php?listId='.$row['listId'].'" style="text-decoration:none">List Items</a></h2>'."\n";

        $values['filterquery']=$sqlparts['activelistitems'];
        $result=query("getlistitems",$config,$values,$options,$sort);

	if ($result!="-1") {
		$counter=0;

		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Item</td>\n";
		echo "		<td>Description</td>\n";
		echo "		<td>Completed</td>\n";
		echo "	</thead>\n";

		foreach($result as $row) {
                echo '	<tr>'."\n";
                echo '		<td><a href = "editListItem.php?listItemId='.$row['listItemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['item'])).'">'.stripslashes($row['item']).'</td>'."\n";
                echo '		<td>'.stripslashes($row['notes']).'</td>'."\n";
                echo '		<td align="center"><input type="checkbox" align="center" name="completedLis[]" title="Complete '.htmlspecialchars(stripslashes($row['item'])).'" value="'.$row['listItemId'].'"></td>'."\n";
                echo '	</tr>'."\n";
                $counter = $counter+1;
		}
		echo '</table>'."\n\n";
	    echo '<input type="submit" align="right" class="button" value="Update List Items" name="submit">'."\n";
		if($counter==0){
			echo "No list items";
		}
	}
	else{
		echo "None";
	}

        $values['filterquery']=$sqlparts['completedlistitems'];
	$result = query("getlistitems",$config,$values,$options,$sort);

	echo "<h2>Completed List Items</h2>\n";
	if ($result!="-1"){
		echo "<table class='datatable'>\n";
		echo "	<thead>\n";
		echo "		<td>Item</td>\n";
		echo "		<td>Notes</td>\n";
//		echo '<td>Completed</td>';
		echo "	</thead>\n";
		foreach($result as $row) {
			echo "	<tr>\n";
                   echo '               <td><a href = "editListItem.php?listItemId='.$row['listItemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['item'])).'">'.stripslashes($row['item']).'</td>'."\n";
                echo '          <td>'.stripslashes($row['notes']).'</td>'."\n";
                echo "	</tr>\n";
		}
		echo "</table>\n";
	}
	else{
		echo "None";
	}
    }
    else echo "Invalid List ID.";

	mysql_close($connection);
	include_once('footer.php');
?>
