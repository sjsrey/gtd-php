<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL AND FORM DATA
$values['categoryId']=(int)$_POST['categoryId'];

//SQL Code
$cashtml=categoryselectbox($config,$values,$options,$sort);

$values['filterquery']="";
if ($values['categoryId']!=0) $values['filterquery']=sqlparts("getlists",$config,$values);
$result = query("getlists",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
echo '<h2><a href="newList.php" title="Add new list" >Lists</a></h2>'."\n";

//category selection form
echo '<div id="filter">'."\n";
echo '<form action="listList.php" method="post">'."\n";
echo "<p>Category:&nbsp;\n";
echo '<select name="categoryId" title="Filter lists by category">'."\n";
echo '  <option value="0">All</option>'."\n";
echo $cashtml."</select>\n";
echo '<input type="submit" class="button" value="Filter" name="submit" title="Filter list by category" />'."\n";
echo "</p>\n";
echo "</form>\n";
echo "</div>\n";

if ($result!="-1") {
        echo "<p>Select list for report.</p>\n";
        echo "<table class='datatable'>\n";
        echo "	<thead>\n";
        echo "		<td>Category</td>\n";
        echo "		<td>Title</td>\n";
        echo "		<td>Description</td>\n";
        echo "	</thead>\n";
        foreach ($result as $row) {
                echo "	<tr>\n";
                echo "		<td>".stripslashes($row['category'])."</td>\n";
                echo '		<td><a href="listReport.php?listId='.$row['listId'].'&listTitle='.urlencode($row['title']).'">'.stripslashes($row['title'])."</a></td>\n";
                echo "		<td>".nl2br(substr(stripslashes($row['description']),0,72))."</td>\n";
                echo "	</tr>\n";
        }
        echo "</table>\n";
}

else {
    $message="You have not defined any lists yet.";
    $prompt="Would you like to create a new list?";
    $yeslink="newList.php";
    nothingFound($message,$prompt,$yeslink);
    }

include_once('footer.php');
?>
