<?php

//INCLUDES
include_once('header.php');

//RETRIEVE URL VARIABLES
$values['listItemId'] =(int) $_GET["listItemId"];

//SQL CODE
$result = query("selectlistitem",$config,$values,$options,$sort);
$values['listId']=$result[0]['listId'];
$lshtml = listselectbox($config,$values,$options,$sort);

//PAGE DISPLAY CODE
echo "<h1>Edit List Item</h1>\n";
echo '<form action="updateListItem.php?listItemId='.$values['listItemId'].'" method="post">'."\n";
?>

<div class='form'>
        <div class='formrow'>

                <label for='newitem' class='left first'>Title:</label>

                <textarea rows="2" name="newitem" wrap=virtual"><?php echo $result[0]['item']; ?></textarea>
        </div>

        <div class='formrow'>
                <label for='list' class='left first'>List:</label>
                <select name='listId' id='list'>
                <?php echo $lshtml; ?>
                </select>
                <label for='newdateCompleted' class='left notfirst'>Date Completed:</label>
                <input type='text' name='newdateCompleted' id='f_date_b' value=' <?php echo $result[0]['dateCompleted']; ?>'>
                <button type='reset' id='f_trigger_b'>...</button>
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
        </div>

        <div class='formrow'>
                <label for='newnotes' class='left first'>Description:</label>
                <textarea rows='10' name='newnotes' id='newnotes' wrap='virtual'><?php echo $result[0]['notes'];?></textarea>
        </div>
</div>
<div class='formbuttons'>
        <input type='submit' value='Update List Item' name='submit' />
        <input type='reset' value='Reset' />
        <input type='checkbox' name='delete' id='delete' class='notfirst' value='y' />
        <label for='delete'>Delete&nbsp;List&nbsp;Item</label>
</div>

<?php include_once('footer.php'); ?>