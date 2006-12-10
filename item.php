<?php
//INCLUDES
include_once('header.php');

$nextactioncheck="n";

//RETRIEVE URL VARIABLES
$values = array();
$values['parentId']=array();

$values['itemId']= (int) $_GET["itemId"];
if ($_GET['parentId']>0) $values['parentId'][0] = (int) $_GET["parentId"];
$values['type']=$_GET["type"]{0};

if ($values['type']=="n") {
        $values['type']='a';
        $nextactioncheck='true';
}

if ($values['type']=="s") {
        $values['type']='p';
        $values['isSomeday']="y";
}

//SQL CODE
if ($values['itemId']>0) {
    $result = query("selectitem",$config,$values,$options,$sort);
    if ($GLOBALS['ecode']==0) {
    $currentrow = $result[0];
    $values['itemId']=$currentrow['itemId'];
    $values['type']=$currentrow['type'];
    $values['categoryId']=$currentrow['categoryId'];
    $values['contextId']=$currentrow['contextId'];
    $values['timeframeId']=$currentrow['timeframeId'];
    $values['isSomeday']=$currentrow['isSomeday'];

    //Test to see if nextaction
    $result = query("testnextaction",$config,$values,$options,$sort);
    if ($result!="-1") {
        if ($result[0]['nextaction']==$values['itemId']) $nextactioncheck='true';
        }
    }
}

//determine item and parent labels
    switch ($values['type']) {
        case "m" : $typename="Value"; $parentname=""; $values['ptype']=""; break;
        case "v" : $typename="Vision"; $parentname="Value"; $values['ptype']="m"; break;
        case "g" : $typename="Goal"; $parentname="Vision"; $values['ptype']="v"; break;
        case "o" : $typename="Role"; $parentname="Goals"; $values['ptype']="g"; break;
        case "p" : $typename="Project"; $parentname="Role"; $values['ptype']="o"; break;
        case "a" : $typename="Action"; $parentname="Project"; $values['ptype']="p"; break;
        case "w" : $typename="Waiting On"; $parentname="Project"; $values['ptype']="p"; break;
        case "r" : $typename="Reference"; $parentname="Project"; $values['ptype']="p"; break;
        case "i" : $typename="Inbox Item"; $parentname="Project"; $values['ptype']="p"; break; //default to project as parent
        default  : $typename="Item"; $parentname="Project"; $values['ptype']="p"; //default to project as parent
        }

$parents = query("lookupparent",$config,$values);

    $i=1;
    if ($parents!="-1") {
        foreach ($parents as $row) {
            $values['parentId'][$i]=$row['parentId'];
            $i++;
            }
        }

//create filters for selectboxes
if ($values['type']=="g") $values['timefilterquery'] = " WHERE ".sqlparts("timegoals",$config,$values);
else $values['timefilterquery'] = " WHERE ".sqlparts("timeitems",$config,$values);

//create item, timecontext, and spacecontext selectboxes
$pshtml = parentselectbox($config,$values,$options,$sort);
$cashtml = categoryselectbox($config,$values,$options,$sort);
$cshtml = contextselectbox($config,$values,$options,$sort);
$tshtml = timecontextselectbox($config,$values,$options,$sort);


//PAGE DISPLAY CODE
if ($values['itemId']>0) {
        echo "<h2>Edit ".$typename."</h2>";
        echo '	<form action="updateItem.php?itemId='.$values['itemId'].'" method="post">';
}
else {
        echo "<h2>New ".$typename."</h2>\n";
        echo '	<form action="processItem.php" method="post">'."\n";
}
?>
        <div class='form'>

                <div class='formrow'>
                        <label for='title' class='left first'>Title:</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars(stripslashes($currentrow['title'])); ?>">
                </div>

                <?php if ($values['ptype']!="") { ?>
                <div class='formrow'>
                        <label for='parent' class='left first'>
                        <?php echo $parentname; ?>:</label>
                        <select name="parentId[]" id='parent' multiple size=6>
                        <?php echo $pshtml; ?>
                        </select>
                </div>
                <?php } ?>

                <div class='formrow'>
                        <label for='category' class='left first'>Category:</label>
                        <select name='categoryId' id='category'>
                        <?php echo $cashtml; ?>
                        </select>

                        <label for='context' class='left'>Context:</label>
                        <select name='contextId' id='context'>
                        <?php echo $cshtml; ?>
                        </select>

                        <label for='timeframe' class='left'>Time:</label>
                        <select name='timeframeId' id='timeframe'>
                        <?php echo $tshtml; ?>
                        </select>
                </div>

                <div class='formrow'>
                        <label for='deadline' class='left first'>Deadline:</label>
                        <input type='text' size='10' name='deadline' id='deadline' value='<?php echo $currentrow['deadline']; ?>'/>
                        <button type='reset' id='deadline_trigger'>...</button>
                                <script type='text/javascript'>
                                        Calendar.setup({
                                                inputField	 :	'deadline',	  // id of the input field
                                                ifFormat	   :	'%Y-%m-%d',	   // format of the input field
                                                showsTime	  :	false,			// will display a time selector
                                                button		 :	'deadline_trigger',   // trigger for the calendar (button ID)
                                                singleClick	:	true,		   // single-click mode
                                                step		   :	1				// show all years in drop-down boxes (instead of every other year as default)
                                        });
                                </script>
                        <label for='dateCompleted' class='left'>Completed:</label><input type='text' size='10' name='dateCompleted' id='dateCompleted' value='<?php echo $currentrow['dateCompleted'] ?>'/>
                        <button type='reset' id='dateCompleted_trigger'>...</button>
                                <script type='text/javascript'>
                                        Calendar.setup({
                                                inputField	 :	'dateCompleted',	  // id of the input field
                                                ifFormat	   :	'%Y-%m-%d',	   // format of the input field
                                                showsTime	  :	false,			// will display a time selector
                                                button		 :	'dateCompleted_trigger',   // trigger for the calendar (button ID)
                                                singleClick	:	true,		   // single-click mode
                                                step		   :	1				// show all years in drop-down boxes (instead of every other year as default)
                                        });
                                </script>
                </div>
                <div class='formrow'>
                        <label for='description' class='left first'>Description:</label>
                        <textarea rows='12' name='description' id='description' wrap='virtual'><?php echo htmlspecialchars(stripslashes($currentrow['description'])); ?></textarea>
                </div>
                <div class='formrow'>
                        <label for='outcome' class='left first'>Desired Outcome:</label>
                        <textarea rows='4' name='desiredOutcome' id='outcome' class='big' wrap='virtual'><?php echo htmlspecialchars(stripslashes($currentrow['desiredOutcome'])) ?></textarea>
                </div>
                <div class='formrow'>
                        <label class='left first'>Type:</label>
                        <input type='radio' name='type' id='value' value='m' class="first" <?php if ($values['type']=='m') echo "CHECKED "; ?>/><label for='value' class='right'>Value</label>
                        <input type='radio' name='type' id='vision' value='v' class="notfirst" <?php if ($values['type']=='v') echo "CHECKED "; ?>/><label for='vision' class='right'>Vision</label>
                        <input type='radio' name='type' id='goal' value='g' class="notfirst" <?php if ($values['type']=='g') echo "CHECKED "; ?>/><label for='goal' class='right'>Goal</label>
                        <input type='radio' name='type' id='role' value='o' class="notfirst" <?php if ($values['type']=='o') echo "CHECKED "; ?>/><label for='role' class='right'>Role</label>
                        <input type='radio' name='type' id='project' value='p' class="notfirst" <?php if ($values['type']=='p') echo "CHECKED "; ?>/><label for='project' class='right'>Project</label>
                </div>
                <div class='formrow'>
                        <label class='left first'></label>
                        <input type='radio' name='type' id='action' value='a' class="first" <?php if ($values['type']=='a') echo "CHECKED "; ?>/><label for='action' class='right'>Action</label>
                        <input type='radio' name='type' id='reference' value='r' class="notfirst" <?php if ($values['type']=='r') echo "CHECKED "; ?>/><label for='reference' class='right'>Reference</label>
                        <input type='radio' name='type' id='waiting' value='w' class="notfirst" <?php if ($values['type']=='w') echo "CHECKED "; ?>/><label for='waiting' class='right'>Waiting</label>
                </div>
                <div class='formrow'>
                        <label class='left first'></label>
                        <input type='radio' name='type' id='inbox' value='i' class="first" <?php if ($values['type']=='i') echo "CHECKED "; ?>/><label for='inbox' class='right'>Inbox</label>
                </div>

                <div class='formrow'>
                        <label for='repeat' class='left first'>Repeat every&nbsp;</label><input type='text' name='repeat' id='repeat' size='3' value='<?php echo $currentrow['repeat']; ?>'><label for='repeat'>&nbsp;days</label>
                </div>

                <div class='formrow'>
                        <label for='suppress' class='left first'>Tickler:</label>
                        <input type='checkbox' name='suppress' id='suppress' value='y' title='Temporarily puts this into the tickler file, hiding it from the active view' <?php if ($currentrow['suppress']=="y") echo " CHECKED"; ?>/>
                        <label for='suppress'>Tickle&nbsp;</label>
                        <input type='text' size='3' name='suppressUntil' id='suppressUntil' value='<?php echo $currentrow['suppressUntil'];?>'><label for='suppressUntil'>&nbsp;days before deadline</label>
                </div>

                <div class='formrow'>
                        <label for='nextAction' class='left first'>Next Action:</label><input type="checkbox" name="nextAction" value="y" <?php if ($nextactioncheck=='true') echo 'CHECKED '; ?>/>

                        <label for='someday' class='left first'>Someday:</label><input type='checkbox' name='isSomeday' id='someday' value='y' title='Places item in Someday file'<?php if ($values['isSomeday']=='y' || $values['type']=='s') echo ' CHECKED';?>>
                </div>

        </div> <!-- form div -->
        <div class='formbuttons'>
<?php
        echo "                  <input type='hidden' name='referrer' value='".$values['type']."'>\n";
if ($values['itemId']>0) {
        echo "			<input type='submit' value='Update ".$typename."' name='submit'>\n";
        echo "                  <input type='reset' value='Reset'>\n";
        echo "                  <input type='checkbox' name='delete' id='delete' value='y' title='Deletes item. Child items are orphaned, NOT deleted.'/><label for='delete'>Delete&nbsp;".$typename."</label>\n";
    }
else echo "			<input type='submit' value='Add ".$typename."' name='submit'>\n";
?>
        </div>
</form>
<?php
if ($values['itemId']>0) {
        echo "	<div class='details'>\n";
        echo "		<span class='detail'>Date Added: ".$currentrow['dateCreated']."</span>\n";
        echo "		<span class='detail'>Last Modified: ".$currentrow['lastModified']."</span>\n";
        echo "	</div>\n";
}
echo "</div><!-- main -->\n";
include_once('footer.php');
?>
