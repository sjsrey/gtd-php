<?php
include_once('header.php');

if (!isset($_POST['submit'])) {
    //form not submitted
    ?>
    <h1>New Spatial Context Definition </h1>
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
            <div class='form'>
                    <div class='formrow'>
                            <label for='name' class='left first'>Context Name:</label>
                            <input type="text" name="name" id="name">
                    </div>

                    <div class='formrow'>
                            <label for='description' class='left first'>Description:</label>
                            <textarea rows="10" name="description" id="description" wrap="virtual"></textarea>
                    </div>
            </div>
            <div class='formbuttons'>
                    <input type="submit" value="Add Context" name="submit">
                    <input type="reset" value="Cancel">
            </div>
    </form>
    <?php
}

else {

    $connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
    mysql_select_db($config['db']) or die ("Unable to select database!");

    $values['name'] = ($_POST['name']=="") ? die('<META HTTP-EQUIV="Refresh" CONTENT="2; url=newCategory.php" /><p>Error: Enter a context name</p>') : mysql_real_escape_string($_POST['name']);
    $values['description'] = mysql_real_escape_string($_POST['description']);

   $result = query("newspacecontext",$config,$values);

    if ($GLOBALS['ecode']=="0") echo "Context ".$values['name']." inserted.";
    else echo "Context NOT inserted.";
    if (($config['debug']=="true" || $config['debug']=="developer") && $GLOBALS['ecode']!="0") echo "<p>Error Code: ".$GLOBALS['ecode']."=> ".$GLOBALS['etext']."</p>";

    echo '<META HTTP-EQUIV="Refresh" CONTENT="2; url=newContext.php" />';
    }

include_once('footer.php');
?>

