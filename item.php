<?php
//INCLUDES
	include_once('header.php');

$nextactioncheck="n";

//RETRIEVE URL VARIABLES
	$values['projectId']= (int) $_GET["projectId"];
	$values['itemId']= (int) $_GET["itemId"];
	$values['type']=$_GET["type"]{0};
	if ($values['type']=="n") {
		$values['type']='a';
		$nextactioncheck='true';
	}

	$values['pType']=$_GET["pType"]{0};
	if ($pType=="s") {
		$values['isSomeday']="y";
		$pTypename="Someday/Maybe";
	}
	else {
		$values['isSomeday']="n";
		$pTypename="Project";
	}

	//select item details
	if ($values['itemId']>0) {
	   $result = query("selectitem",$config,$values,$options,$sort);
           if ($GLOBALS['ecode']==0) {
            $currentrow = $result[0];
            $values['type']=$currentrow['type'];

            //Test to see if nextaction
            $result = query("testnextaction",$config,$values,$options,$sort);
	    if ($result[0]['nextaction']==$values['itemId']) $nextactioncheck='true';
            }
        }

//create project, timecontext, and spacecontext selectboxes
$pshtml = projectselectbox($config,$values,$options,$sort);
$cshtml = contextselectbox($config,$values,$options,$sort);
$tshtml = timecontextselectbox($config,$values,$options,$sort);

//PAGE DISPLAY CODE
	//determine item label
	if ($values['type']=="a") $typename="Action";
	elseif ($values['type']=="r") $typename="Reference";
	elseif ($values['type']=="w") $typename="Waiting On";
 	else $typename="Item";

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
				<input type='text' name='title' id='title' value='<?php echo stripslashes($currentrow['title']); ?>'>
			</div>

			<div class='formrow'>
				<label for='project' class='left first'><?php echo $pTypename; ?>:</label>
				<select name="projectId"> <?php echo $pshtml; ?>
				</select>
				 <label for='context' class='left'>Context:</label>
				<select name='contextId' id='context'> <?php echo $cshtml; ?>
				</select>

				<label for='timeframe' class='left'>Time:</label>
				<select name='timeframeId' id='timeframe'> <?php echo $tshtml; ?>
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
				<textarea rows='12' name='description' id='description' wrap='virtual'><?php echo stripslashes($currentrow['description']); ?></textarea>
			</div>

			<div class='formrow'>
				<label class='left first'>Type:</label>
	  			<input type='radio' name='type' id='action' value='a' class="first" <?php if ($values['type']=='a') echo "CHECKED "; ?>/><label for='action' class='right'>Action</label>
	  			<input type='radio' name='type' id='reference' value='r' class="notfirst" <?php if ($values['type']=='r') echo "CHECKED "; ?>/><label for='reference' class='right'>Reference</label>
	  			<input type='radio' name='type' id='waiting' value='w' class="notfirst" <?php if ($values['type']=='w') echo "CHECKED "; ?>/><label for='waiting' class='right'>Waiting</label>
			</div>

			<div class='formrow'>
				<label for='repeat' class='left first'>Repeat every&nbsp;</label><input type='text' name='repeat' id='repeat' size='3' value='<?php echo $currentrow['repeat']; ?>'><label for='repeat'>&nbsp;days</label>
			</div>

			<div class='formrow'>
				<label for='suppress' class='left first'>Tickler:</label>
				<input type='checkbox' name='suppress' id='suppress' value='y' title='Hides this project from the active view' <?php if ($currentrow['suppress']=="y") echo " CHECKED"; ?>/>
				<label for='suppress'>Tickle&nbsp;</label>
				<input type='text' size='3' name='suppressUntil' id='suppressUntil' value='<?php echo $currentrow['suppressUntil'];?>'><label for='suppressUntil'>&nbsp;days before deadline</label>
			</div>

			<div class='formrow'>
				<label for='nextAction' class='left first'>Next Action:</label><input type="checkbox" name="nextAction" value="y" <?php if ($nextactioncheck=='true') echo 'CHECKED '; ?>/>
			</div>

		</div> <!-- form div -->
		<div class='formbuttons'>
<?php
	if ($values['itemId']>0) {
		echo "			<input type='submit' value='Update ".$typename."' name='submit'>\n";
	} else echo "			<input type='submit' value='Add ".$typename."' name='submit'>\n";
?>
			<input type='reset' value='Reset'>
			<input type='checkbox' name='delete' id='delete' value='y' /><label for='delete'>Delete&nbsp;Item</label>
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
