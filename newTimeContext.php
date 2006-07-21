<?php
include_once('header.php');
include_once('config.php');
if (!isset($_POST['submit'])) {
	//form not submitted
	?>
	<h1>New Temporal Context Definition </h1>
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
		Context Name<br />
		<input type="text" name="name"><br />
		Description<br />
		<textarea cols="80" rows="10" name="description" wrap="virtual"></textarea><br />
		<input type="submit" value="Add Context" name="submit">
		<input type="reset" value="Cancel">
	</form>
	<?php
}else{
	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
	mysql_select_db($db) or die ("Unable to select database!");

	$name = empty($_POST['name']) ? die("Error: Enter a context name") : mysql_real_escape_string($_POST['name']);
	$description = empty($_POST['description']) ? die("Error: Enter a description") : mysql_real_escape_string($_POST['description']);
	$dateCreated = date('Y-m-d');
	# don't forget null
	$query = "INSERT into timeitems  values (NULL, '$name', '$description')";
	$result = mysql_query($query) or die ("Error in query: $query. ".mysql_error());


    echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=newTimeContext.php"';
	mysql_close($connection);
}
include_once('footer.php');
?>


