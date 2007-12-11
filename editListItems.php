<?php
include_once('header.php');
include_once('lists.inc.php');

if ($values['itemId']) {
    $row = query("select{$check}listitem",$config,$values,$sort);
    if (!$row) {
        echo "<p class='error'>That {$check}list item does not exist</p>\n";
        include_once('footer.php');
        exit();
    }
    foreach (array('id','item','notes') as $field)
            $values[$field]=$row[0][$field];
    if ($isChecklist)
        $values['checked']=$row[0]['checked'];
    else
        $values['dateCompleted']=$row[0]['dateCompleted'];
    $action='itemedit';
} else {
    $values['id']=(int) $_REQUEST['id'];
    $values['item']='';
    $values['notes']='';
    $values['dateCompleted']=null;
    $values['checked']=null;
    $action='itemcreate';
}
$lshtml = listselectbox($config,$values,$sort,$check);
?>
<h1><?php echo ($values['itemId'])?'Edit ':'Create ',$check; ?>list item</h1>
<form action="processLists.php" method="post" onsubmit="return validate(this);">
    <div class='form'>
        <div class='formrow'><span class="error" id='errorMessage'></span></div>
        <div class='formrow'>
            <label class='left first' for='title'>Title:</label>
            <input type='text' name='title' id='title' value='<?php echo makeclean($values['item']); ?>' size='80' />
        </div>
        <div class='formrow'>
            <label for='notes' class='left first'>Description:</label>
            <textarea rows='10' name='notes' id='notes' cols='80'><?php echo makeclean($values['notes']);?></textarea>
        </div>
        <div class='formrow'>
            <label class='left first'><?php echo $check; ?>list:</label>
            <?php if ($values['id'] && !$values['itemId']) { ?>
                <a href='reportLists.php?id=<?php
                    echo "{$values['id']}&amp;{$urlSuffix}'>{$values['listTitle']}";
                    ?></a>
            <?php } else { ?>
                <select name='id' id='id'>
                    <?php echo $lshtml; ?>
                </select>
            <?php }
            if ($values['itemId']) {
                if ($isChecklist) { ?>
                    <label class='left notfirst'>Item completed</label>
                    <input type='checkbox' name='checked' <?php echo ($values['checked']==='y')?" checked='checked' ":'' ?> />
                	<input type='hidden' name='required' value='title:notnull:Title cannot be blank' />
                <?php } else { ?>
                    <label class='left notfirst'>Date Completed:</label>
                    <input type='text' name='dateCompleted' id='dateCompleted' value='<?php echo $values['dateCompleted']; ?>' />
                    <button type='reset' id='f_trigger_b'>...</button>
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
                    <button type='button' id='dateCompleted_today' onclick="javascript:completeToday('dateCompleted');">Today</button>
                	<input type='hidden' name='required'
        	           value='title:notnull:Title cannot be blank,dateCompleted:date:Completion date is not valid' />
                <?php }
            } else { ?>
        	   <input type='hidden' name='required' value='title:notnull:Title cannot be blank' />
            <?php } ?>
        </div>
    </div>
    <div class='formbuttons'>
        <?php if ($values['itemId']) { ?>
            <input type='submit' value='Update item' name='submit' />
        <?php } else { ?>
            <input type='submit' value='Create, then add another item' name='again' />
            <input type='submit' value='Create and return to list' name='submit' />
        <?php } ?>
        <input type='reset' value='Reset' />
        <?php if ($values['itemId']) { ?>
            <input type='checkbox' name='delete' id='delete' class='notfirst' value='y' />
            <label for='delete'>Delete&nbsp;Item</label>
        <?php } ?>
        <input type='hidden' name='type'       value='<?php echo $type;             ?>' />
        <input type='hidden' name='itemId'     value='<?php echo $values['itemId']; ?>' />
        <input type='hidden' name='id'         value='<?php echo $values['id'];     ?>' />
        <input type='hidden' name='action'     value='<?php echo $action;           ?>' />
    	<input type='hidden' name='dateformat' value='ccyy-mm-dd'                       />
    </div>
</form>
<?php include_once('footer.php'); ?>
