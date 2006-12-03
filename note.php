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
    $result = query("selectnote",$config,$values,$sort,$options);
    if ($result!=-1) $currentrow = $result[0];
    else echo "Nothing found.";
    }

//PAGE DISPLAY CODE
if ($values['noteId']>0) {
        echo "<h2>Edit Note</h2>\n";
        echo "     <div class='form'>\n";
        echo '        <form action="updateNote.php?noteId='.$values['noteId'].'" method="post">';
        }

else {
        echo "<h2>New Note</h2>\n";
        echo "     <div class='form'>\n";
        echo '       <form action="processNote.php" method="post">';
        }

?>
        <div class="formrow">
            <label for="date" id="date" class="left first">Date</label>
                            <form action="#" method="get">
                            <input type="text" size="10" name="date" id="f_date_b" value="<?php echo $currentrow['date'] ?>"/><button type="reset" id="f_trigger_b">...</button>


            <script type="text/javascript">
                Calendar.setup({
                    inputField     :    "f_date_b",      // id of the input field
                    ifFormat       :    "%Y-%m-%d",       // format of the input field
                    showsTime      :    false,            // will display a time selector
                    button         :    "f_trigger_b",   // trigger for the calendar (button ID)
                    singleClick    :    true,           // single-click mode
                    step           :    1                // show all years in drop-down boxes (instead of every other year as default)
                });
            </script>

            <label for='suppress' class="left">Tickle</label>
            <input type='text' size='3' name='suppressUntil' id='suppressUntil' value='<?php echo $currentrow['suppressUntil'];?>'><label class="notfirst">days before date</label>

            <label for='repeat' class="left">Repeat every&nbsp;</label><input type='text' name='repeat' id='repeat' size='3' value='<?php echo $currentrow['repeat']; ?>'><label for='repeat'>&nbsp;days</label>
        </div>

        <div class="formrow">
            <label for="text" id="text" class="left first">Title</label>
            <input type="text" size="68" name="title"  id="title" value="<?php echo htmlspecialchars(stripslashes($currentrow['title'])) ?>">
        </div>
        
        <div class="formrow">
            <label for="note" id="note" class="left first">Details</label>
            <textarea rows="12" class="big" name="note" id="description" wrap="virtual"><?php echo htmlspecialchars(stripslashes($currentrow['note'])) ?></textarea>
        </div>

    </div>
    
        <div class="formbuttons">

            <?php if ($values['noteId']>0) echo '<input type="submit" class="button" value="Update Note" name="submit">';
            else echo '<input type="submit" class="button" value="Add Note" name="submit">';
            ?>

            <input type="reset" class="button" value="Reset">
            <input type="checkbox" name="acknowledge" value="y" title="Updates note, incrementing to next display date" /><label for="acknowledge" id="acknowledge" class="notfirst">Acknowledge Note</label>
            <input type="checkbox" name="delete" value="y" title="Deletes note; will not repeat again"/><label for="delete" id="delete" class="notfirst">Delete Note</label>
            <input type="hidden" name="referrer" value="<?php echo $referrer ?>" />
            <input type="hidden" name="type" value="<?php echo $type ?>" />
        </div>
    </form>
</div>
<?php
include_once('footer.php');
?>
