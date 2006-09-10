<?php
include_once('header.php');
include_once('config.php');
if (!isset($_POST['submit'])) {
	//form not submitted
	?>
	<h1>New Category Definition </h1>
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
		<div class='form'>
			<div class='formrow'>
				<label for='name' class='left first'>Category Name:</label>
				<input type="text" name="name" id="name">
			</div>
			
			<div class='formrow'>
				<label for='description' class='left first'>Description:</label>
				<textarea rows="10" name="description" id="description" wrap="virtual"></textarea>
			</div>
		</div>
		<div class='formbuttons'>
			<input type="submit" value="Add Category" name="submit">
			<input type="reset" value="Cancel">
		</div>
	</form>
	<?php
}else{

	$name = empty($_POST['name']) ? die("Error: Enter a category name") : mysql_escape_string($_POST['name']);
	$description = empty($_POST['description']) ? die("Error: Enter a description") : mysql_escape_string($_POST['description']);

	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");

	mysql_select_db($db) or die ("Unable to select database!");
	$dateCreated = date('Y-m-d');
	# don't forget null
	$query = "INSERT into categories values (NULL, '$name', '$description')";
	$result = mysql_query($query) or die ("Error in query");

	//echo "New category inserted with ID ".mysql_insert_id();

    //brainstorming mode would have user want to add new categories in
    //bunches?
    echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=newCategory.php"';
	mysql_close($connection);
}
include_once('footer.php');
?>

