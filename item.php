<?php
//INCLUDES
	include_once('gtdfuncs.php');
	include_once('header.php');
	include_once('config.php');

//RETRIEVE URL VARIABLES
	$projectId= (int) $_GET["projectId"];
	$itemId= (int) $_GET["itemId"];
	$type=$_GET["type"]{0};
	if ($type=="n") {
		$type='a';
		$nextactioncheck='true';
	}

	$pType=$_GET["pType"]{0};
	if ($pType=="s") {
		$isSomeday="y";
		$pTypename="Someday/Maybe";
	}
	else { 
		$isSomeday="n";
		$pTypename="Project";
	}

//SQL CODE
	$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect");
	mysql_select_db($db) or die ("Unable to select database!");

	//select item details
	if ($itemId>0) {
	$query= "SELECT items.itemId, itemattributes.projectId, itemattributes.contextId, itemattributes.type, itemattributes.timeframeId, items.title, 
			items.description, itemstatus.dateCreated, itemattributes.deadline, itemstatus.dateCompleted, itemstatus.lastModified, 
			itemattributes.repeat, itemattributes.suppress, itemattributes.suppressUntil FROM items, itemattributes, itemstatus 
			WHERE itemstatus.itemId=items.itemId and itemattributes.itemId=items.itemId and items.itemId = '$itemId'";
		$result = mysql_query($query) or die ("Error in query");
		$currentrow = mysql_fetch_assoc($result);
		mysql_free_result($result);
		$type=$currentrow['type'];
	}

	//Test to see if nextaction
	$query = "SELECT projectId, nextaction FROM nextactions where nextaction='$itemId'";
	$result = mysql_query($query) or die ("Error in query");
	while ($nextactiontest = mysql_fetch_assoc($result)) {
		if ($nextactiontest['nextaction']==$itemId) $nextactioncheck='true';
	}
	mysql_free_result($result);


	//select active or someday projects for selectbox (would make good function!)
	$query="SELECT projects.projectId, projects.name, projects.description
		FROM projects, projectattributes, projectstatus
		WHERE projectattributes.projectId = projects.projectId 
		AND projectstatus.projectId=projects.projectId 
		AND (projectstatus.dateCompleted IS NULL OR projectstatus.dateCompleted = '0000-00-00') 
		AND projectattributes.isSomeday ='".$isSomeday."' ORDER BY projects.name";
	$result = mysql_query($query) or die ("Error in query");
	$pshtml="";
	while($row = mysql_fetch_assoc($result)){
		$pshtml .= '			<option value="'.$row['projectId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
		if($row['projectId']==$currentrow['projectId'] || $row['projectId']==$projectId) $pshtml .= ' SELECTED';
		$pshtml .= '>'.stripslashes($row['name'])."</option>\n";
	}
	mysql_free_result($result);
	
	//select all contexts for selectbox (would make good function!)
	$query = "SELECT contextId, name, description FROM context ORDER BY name ASC";
	$result = mysql_query($query) or die("error in query: $query.  ".mysql_error());
	$cshtml="";

	while($row = mysql_fetch_assoc($result)) {
		$cshtml .= '			<option value="'.$row['contextId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
		if($row['contextId']==$currentrow['contextId']) $cshtml .= ' SELECTED';
		$cshtml .= '>'.stripslashes($row['name'])."</option>\n";
	}
	mysql_free_result($result);

	//select all itemtimeframes for selectbox (function candidate?)
	$query = "SELECT timeframeId, timeframe, description FROM timeitems ORDER BY timeframe DESC";
	$result = mysql_query($query) or die("error in query: $query.  ".mysql_error());
	$tshtml="";
	while($row = mysql_fetch_assoc($result)){
		$tshtml .= '			<option value="'.$row['timeframeId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
		if($row['timeframeId']==$currentrow['timeframeId']) $tshtml .= ' SELECTED';
		$tshtml .= '>'.stripslashes($row['timeframe'])."</option>\n";
	}
	mysql_free_result($result);

//PAGE DISPLAY CODE
	
	//determine item label
	if ($type=="a") $typename="Action";
	elseif ($type=="r") $typename="Reference";
	elseif ($type=="w") $typename="Waiting On";
 	else $typename="Item";

	if ($itemId>0) {
		echo "<h2>Edit ".$typename."</h2>";
		echo '	<form action="updateItem.php?itemId='.$itemId.'" method="post">';
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
	  			<input type='radio' name='type' id='action' value='a' class="first" <?php if ($type=='a') echo "CHECKED "; ?>/><label for='action' class='right'>Action</label>
	  			<input type='radio' name='type' id='reference' value='r' class="notfirst" <?php if ($type=='r') echo "CHECKED "; ?>/><label for='reference' class='right'>Reference</label>
	  			<input type='radio' name='type' id='waiting' value='w' class="notfirst" <?php if ($type=='w') echo "CHECKED "; ?>/><label for='waiting' class='right'>Waiting</label>
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
	if ($itemId>0) {
		echo "			<input type='submit' value='Update ".$typename."' name='submit'>\n";
	} else echo "			<input type='submit' value='Add ".$typename."' name='submit'>\n";
?>		
			<input type='reset' value='Reset'>
			<input type='checkbox' name='delete' id='delete' value='y' /><label for='delete'>Delete&nbsp;Item</label>
		</div>
	</form>
<?php
	if ($itemId>0) {
		echo "	<div class='details'>\n";
		echo "		<span class='detail'>Date Added: ".$currentrow['dateCreated']."</span>\n";
		echo "		<span class='detail'>Last Modified: ".$currentrow['lastModified']."</span>\n";
		echo "	</div>\n";
	}
	echo "</div><!-- main -->\n";
	include_once('footer.php');
?>
