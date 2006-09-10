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
<?php

//SELECT categories.categoryId, categories.name FROM categories ORDER BY categories.name ASC

	$query = "select * from categories";
	$result = mysql_query($query) or die("Error in query");
?>
	<div class='form'>		<div class='formrow'>
			<label for='title' class='left first'>Title:</label>
			<input type="text" name="title" id="title">
		</div>

		<div class='formrow'>
			<label for='category' class='left first'>Category:</label>
			<select name='categoryId' id='category'>
<?php
	while($row = mysql_fetch_row($result)){
			echo "			<option value='" .$row[0] . "'>" . stripslashes($row[1]) . "</option>\n";
	}
?>
			</select>
		</div>
		
		<div class='formrow'>
			<label for='description' class='left first'>Description:</label>
			<textarea rows="10" name="description" id="description" wrap="virtual"></textarea>
		</div>
	</div>
	<div class='formbuttons'>
		<input type="submit" value="Add List" name="submit">
	</div>
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

