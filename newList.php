<?php
//INCLUDES
include_once('header.php');

if (!isset($_POST['submit'])) {
	//form not submitted
?>
<h1>New List</h1>

<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
<?php
$cashtml=categoryselectbox($config,$values,$options,$sort);
?>
	<div class='form'>
		<div class='formrow'>
			<label for='title' class='left first'>Title:</label>
			<input type="text" name="title" id="title">
		</div>

		<div class='formrow'>
			<label for='category' class='left first'>Category:</label>
			<select name='categoryId' id='category'>
                        <?php echo $cashtml ?>
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

    $values['title'] = empty($_POST['title']) ? die("Error: Enter a list title") : mysql_real_escape_string($_POST['title']);
    $values['description'] = mysql_real_escape_string($_POST['description']);
    $values['categoryId'] = (int) $_POST['categoryId'];
//    $values['dateCreated'] = date('Y-m-d');

    $result= query("newlist",$config,$values,$options,$sort);

    if ($GLOBALS['ecode']=="0") echo "List: ".$values['title']." inserted.";
    else echo "List NOT inserted.";
    if (($config['debug']=="true" || $config['debug']=="developer") && $GLOBALS['ecode']!="0") echo "<p>Error Code: ".$GLOBALS['ecode']."=> ".$GLOBALS['etext']."</p>";

    echo '<META HTTP-EQUIV="Refresh" CONTENT="2; url=listReport.php?listId='.mysql_insert_id().'&listTitle='.urlencode($values['title']).'">';
    }

include_once('footer.php');
?>

