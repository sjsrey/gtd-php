<?php
//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values=array();
$values['noteId']= (int) $_GET["noteId"];
$referrer = $_GET["referrer"];
$type = $_GET["type"];

//select note details
if ($values['noteId']>0) {
    $result = query("selectnote",$config,$values,$sort);
    if ($result) $currentrow = $result[0];
    else echo "Nothing found.";
    }

//PAGE DISPLAY CODE
if ($values['noteId']) {
    $pagetitle='Edit Note';
    $pageaction='updateNote.php?noteId='.$values['noteId'];
    $pagesubmit='Update Note';
}else{
    $pagetitle='New Note';
    $pageaction='processNote.php';
    $pagesubmit='Add Note';
}
?>
<h2><?php echo $pagetitle; ?></h2>
<p class='warning'>Deprecated feature: notes will be withdrawn before the 1.0 release of gtd-php.
They have been superseded by tickler actions.
Normal actions can be put into a tickler file, to be suppressed until a specified number of days before their deadlines</p>
<div class='form'>
    <form action="<?php echo $pageaction; ?>" method="post" onsubmit="return validate(this);">
        <div class="formrow">
            <span class="error" id="errorMessage"></span>
            <label for="date" id="date" class="left first">Date</label>
            <input type="text" size="10" name="date" id="f_date_b" value="<?php echo $currentrow['date'] ?>"/><button type="reset" id="f_trigger_b">...</button>
            <script type="text/javascript">
                Calendar.setup({
					firstDay       :    <?php echo (int) $config['firstDayOfWeek']; ?>, 
					inputField     :    "f_date_b",      // id of the input field
                    ifFormat       :    "%Y-%m-%d",       // format of the input field
                    showsTime      :    false,            // will display a time selector
                    button         :    "f_trigger_b",   // trigger for the calendar (button ID)
                    singleClick    :    true,           // single-click mode
                    step           :    1                // show all years in drop-down boxes (instead of every other year as default)
                });
            </script>
            <label for='suppressUntil' class="left">Tickle</label>
            <input type='text' size='3' name='suppressUntil' id='suppressUntil' value='<?php echo $currentrow['suppressUntil'];?>' /><label class="notfirst">days before date</label>
            <label for='repeat' class="left">Repeat every&nbsp;</label><input type='text' name='repeat' id='repeat' size='3' value='<?php echo $currentrow['repeat']; ?>' /><label for='repeat'>&nbsp;days</label>
        </div>
        <div class="formrow">
            <label for="text" id="text" class="left first">Title</label>
            <input type="text" size="68" name="title" id="title" value="<?php echo makeclean($currentrow['title']); ?>" />
        </div>
        <div class="formrow">
            <label for="note" id="note" class="left first">Details</label>
            <textarea rows="12" class="big" name="note" id="description" cols="80"><?php echo makeclean($currentrow['note']); ?></textarea>
        </div>
        <div class="formbuttons">
            <input type="submit" class="button" value="<?php echo $pagesubmit; ?>" name="submit" />
            <input type="reset" class="button" value="Reset" />
            <input type="checkbox" name="acknowledge" value="y" title="Updates note, incrementing to next display date" /><label for="acknowledge" id="acknowledge" class="notfirst">Acknowledge Note</label>
            <input type="checkbox" name="delete" value="y" title="Deletes note; will not repeat again"/><label for="delete" id="delete" class="notfirst">Delete Note</label>
            <input type="hidden" name="referrer" value="<?php echo $referrer ?>" />
            <input type="hidden" name="type" value="<?php echo $type ?>" />
     		<input type='hidden' name='dateformat' value='ccyy-mm-dd' />
    		<input type='hidden' name='required'
    		       value='title:notnull:Title can not be blank.,date:notnull:You must provide a date,date:date:Date must be valid ' />
        </div>
    </form>
</div>
<?php
include_once('footer.php');
?>
