<?php
//INCLUDES
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
	mysql_select_db($db) or die ("Unable to select database!");


if (!isset($_POST['submit'])) {
	//form not submitted
	?>
<h1>New List</h1>

<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
<table border=0">
<?php

//SELECT categories.categoryId, categories.name FROM categories ORDER BY categories.name ASC

	$query = "select * from categories";
	$result = mysql_query($query) or die("Error in query");
?>
	<tr>
		<td>Category</td>
		<td><select name="categoryId">
<?php
	while($row = mysql_fetch_row($result)){
			echo "			<option value='" .$row[0] . "'>" . stripslashes($row[1]) . "</option>\n";
	}
?>
		</select></td>
	</tr>
</table>
<br /><br />
Title<br />
<input type="text" name="title" size="50">

<br /><br />Description<br />
<textarea name="description" cols="80" rows="8"></textarea><br />
<input type="submit" value="Add List" name="submit">
<input type="reset" value="Cancel">
</form>

<?php
}else {

	$title = empty($_POST['title']) ? die("Error: Enter a list title") : mysql_real_escape_string($_POST['title']);		
	$description = empty($_POST['description']) ? die("Error: Enter a list description") : mysql_real_escape_string($_POST['description']);		
	$categoryId = (int) $_POST['categoryId'];
	$dateCreated = date('Y-m-d');

	# don't forget null
	$query = "INSERT into list values (NULL, '$title', '$categoryId', '$description')";
	$result = mysql_query($query) or die ("Error in query");

    echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listReport.php?listID='.mysql_insert_id().'&listTitle='.$title.'">';
	mysql_close($connection);
}
include_once('footer.php');
?>

