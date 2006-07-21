<?php
include_once('header.php');
include_once('config.php');
if (!isset($_POST['submit'])) {
	//form not submitted
	?>
	<h1>New Category Definition </h1>
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
		Category Name<br />
		<input type="text" name="name"><br />
		Description<br />
		<textarea cols="80" rows="10" name="description" wrap="virtual"></textarea><br />
		<input type="submit" value="Add Category" name="submit">
		<input type="reset" value="Cancel">
		</form>
	<?php
}else{

	$name = empty($_POST['category']) ? die("Error: Enter a category name") : mysql_escape_string($_POST['category']);
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

