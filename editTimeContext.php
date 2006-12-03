<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values = array();
$values['tcId'] =(int) $_GET["tcId"];

//SQL CODE
$tshtml=timecontextselectbox($config,$values,$options,$sort);

//Select timeframe to edit
$result = query("selecttimecontext",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
?>
<h2>Edit Timeframe</h2>

<form action="updateTimeContext.php?tcId=<?php echo $values['tcId']?>" method="post">

<div class="form">
    <div class="formrow">
    <label for="timeframe" class="left first">Name:</label>
    <input type="text" name="timeframe" id="title" value="<?php echo htmlspecialchars(stripslashes($result[0]['timeframe'])); ?> " />
    </div>

    <div class="formrow">
        <label for="description" class="left first">Description:</label>
        <textarea  rows='4' class='big' id='description' name="description" wrap='virtual' ><?php echo htmlspecialchars(stripslashes($result[0]['description'])) ?></textarea>
    </div>

    <div class='formrow'>
                            <label for='type' class ='left first'>Type:</label>
<!--                            <input type='radio' name="type" id="vision" value="v" class="first"/><label for="vision" class="right">Vision</label>
                            <input type='radio' name="type" id="role" value="o" class="notfirst" /><label for="role" class="right">Role</label>  //-->
                            <input type='radio' name="type" id="goal" value="g" class="notfirst" <?php if ($result[0]['type']=='g') echo 'CHECKED' ?>/><label for="goal" class="right">Goal</label>
<!--                            <input type='radio' name="type" id="project" value="p" class="notfirst" /><label for="project" class="right">Project</label>  //-->
                            <input type='radio' name="type" id="action" value="a" class="notfirst" <?php if ($result[0]['type']=='a') echo 'CHECKED' ?>/><label for="action" class="right">Action</label>
    </div>

    <div class="formrow">
        <label for="delete" id="delete" class="left first">Delete</label><input type="checkbox" name="delete" value="y" />
        <label for="reassign" id="reassign" class="notfirst">Reassign Items to timeframe:</label>
        <select name="ntcId"><?php echo $tshtml; ?></select>
    </div>
</div>
    <div class="formbuttons">
        <input type="submit" class="button" value="Update Timeframe" name="submit" />
        <input type="reset" class="button" value="Reset" />
    </div>
</form>

<?php
include_once('footer.php');
?>
