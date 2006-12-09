<?php
include_once('header.php');

if (!isset($_POST['submit'])) {
    //form not submitted
    ?>
    <h1>New Time Context Definition </h1>
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

                    <div class='formrow'>
                            <label for='type' class ='left first'>Type:</label>
<!--                            <input type='radio' name="type" id="vision" value="v" class="first"/><label for="vision" class="right">Vision</label>
                            <input type='radio' name="type" id="role" value="o" class="notfirst" /><label for="role" class="right">Role</label>  //-->
                            <input type='radio' name="type" id="goal" value="g" class="notfirst" /><label for="goal" class="right">Goal</label>
<!--                            <input type='radio' name="type" id="project" value="p" class="notfirst" /><label for="project" class="right">Project</label>  //-->
                            <input type='radio' name="type" id="action" value="a" CHECKED class="notfirst" /><label for="action" class="right">Action</label>
                    </div> 
            </div>
            <div class='formbuttons'>
                    <input type="submit" value="Add Time Context" name="submit">
                    <input type="reset" value="Cancel">
            </div>
    </form>
    <?php
}
else {
$values = array();
$values['name'] = ($_POST['name']=="") ? die('<META HTTP-EQUIV="Refresh" CONTENT="2; url=newCategory.php" /><p>Error: Enter a context name</p>') : mysql_real_escape_string($_POST['name']);
$values['description'] = mysql_real_escape_string($_POST['description']);
$values['type'] = mysql_real_escape_string($_POST['type']);

$result = query("newtimecontext",$config,$values);

if ($GLOBALS['ecode']=="0") echo "Time context ".$values['name']." inserted.";
else echo "Time  context NOT inserted.";
if (($config['debug']=="true" || $config['debug']=="developer") && $GLOBALS['ecode']!="0") echo "<p>Error Code: ".$GLOBALS['ecode']."=> ".$GLOBALS['etext']."</p>";

echo '<META HTTP-EQUIV="Refresh" CONTENT="2; url=newTimeContext.php" />';
    }

include_once('footer.php');
?>


