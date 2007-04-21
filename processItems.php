<?php
//INCLUDES
include_once('header.php');

// get core variables first
$values=array();  // ensures that this is a global variable
$values['itemId'] = (int) $_POST['itemId'];
$action = $_POST['action'];

$updateGlobals=array();
$updateGlobals['multi']    = (isset($_POST['multi']) && $_POST['multi']==='y');
$updateGlobals['parents']  = $_POST['parentId']; // TOFIX - what happens if this is empty?
$updateGlobals['referrer'] = $_POST['referrer'];

if (isset($_POST['wasNAonEntry'])) {  // toggling next action status on several items
	$updateGlobals['wasNAonEntry'] = explode(',',$_POST['wasNAonEntry']);
	$updateGlobals['isNA']=array();
	if (isset($_POST['isNAs'])) $updateGlobals['isNA']=$_POST['isNAs'];
}

if (isset($_POST['isMarked'])) { // doing a specific action on several items (currently, the only option is to complete them)
	$updateGlobals['isMarked']=array();
	$updateGlobals['isMarked']=array_unique($_POST['isMarked']); // remove duplicates
}


// some debugging - if debug is set to halt, dump all the variables we've got

if (($config['debug']==='freezedb') || ($config['debug']=='developer')) {
	// debugging text - simply dump the variables, and quit, without processing anything
	literaldump('$_GET');
	literaldump('$_POST');
	literaldump('$action');
	literaldump('$config');
	literaldump('$values');
	literaldump('$updateGlobals');
	if (isset($updateGlobals['isNA'])) {
		echo '<hr /><pre>array_diff(wasNAonEntry,isNA)';
		print_r(array_diff($updateGlobals['wasNAonEntry'],$updateGlobals['isNA']));
		echo '<br /><hr />array_diff(isNA,wasNAonEntry)';
		print_r(array_diff($updateGlobals['isNA'],$updateGlobals['wasNAonEntry']));
		echo '</pre>';
	}
} // END OF debugging text

if ($updateGlobals['multi']) {
	// recursively do actions, looping over items
	if (isset($updateGlobals['wasNAonEntry']) && isset($updateGlobals['isNA'])) {  // toggling next action status on several items
		foreach (array_diff($updateGlobals['wasNAonEntry'],$updateGlobals['isNA']) as $values['itemId']) if ($values['itemId']) doAction('removeNA');
		foreach (array_diff($updateGlobals['isNA'],$updateGlobals['wasNAonEntry']) as $values['itemId']) if ($values['itemId']) doAction('makeNA');
	}
	if (isset($updateGlobals['isMarked'])) { // doing a specific action on several items (currently, the only option is to complete them)
		foreach ($updateGlobals['isMarked'] as $nextItem) {
			$values=array('itemId'=>$nextItem); // reset the $values array each time, so that it only contains itemId
			doAction($action);
		}
	}
} else {
	if ($_POST['delete']==='y') $action='delete'; // override item-update if we are simply deleting
	doAction($action);
}

nextpage();
include_once('footer.php');
return;

/*========================================================================================
  main program finished - utility functions from here, below
========================================================================================*/

function doAction($localAction) { // do the current action on the current item; returns TRUE if succeeded, else returns FALSE
	global $config,$values,$updateGlobals;
	if (($config['debug']==='freezedb') || ($config['debug']==='developer')) {
		echo "<p><b>Action here is: '$localAction' on item {$values['itemId']}</b></p>";
		if ($config['debug']==='freezedb') return TRUE;
	}
	switch ($localAction) {
		case 'makeNA':
			makeNextAction();
			break;
			
		case 'removeNA':
			removeNextAction();
			break;
			
		case 'create':
			retrieveFormVars();
			createItem();
			break;
			
		case 'complete':
			completeItem();
			break;
		
		case 'fullUpdate':
			retrieveFormVars();
			updateItem();
			break;
			
		case 'delete':
			deleteItem();
			break;
		
		case 'createbasic': // not in use yet. added for future use, when only title and type are set.
			createItemQuickly();
			break;
			
		default: // failed to identify which action we should be taking, so quit
			return FALSE;
	}
	return TRUE; // we have successfully carried out some action
}

/* ===========================================================================================
	primary action functions
   ================================= */

function deleteItem() { // delete all references to a specific item
	global $config,$values;
	query("deleteitemstatus",$config,$values);
	query("deleteitemattributes",$config,$values);
	query("deleteitem",$config,$values);
	query("deletelookup",$config,$values);
	query("deletelookupparents",$config,$values);
	removeNextAction();
	query("deletenextactionparents",$config,$values);
}

function createItem() { // create an item and its parent-child relationships
	global $config,$values,$updateGlobals;
	//Insert new records
	$result = query("newitem",$config,$values);
	$values['newitemId'] = $GLOBALS['lastinsertid'];
	$result = query("newitemattributes",$config,$values);
	$result = query("newitemstatus",$config,$values);
	
	foreach ($updateGlobals['parents'] as $values['parentId']) if (((int) $values['parentId'])>0) {
		$result = query("newparent",$config,$values);
		if($values['nextAction']==='y') $result = query("updatenextaction",$config,$values);	
	}
}

function createItemQuickly() {// create an item when we only know its type and title - not yet in use - TOFIX still to check
	global $config,$values,$updateGlobals;
	//Insert new records
	$result = query("newitem",$config,$values);
	$values['newitemId'] = $GLOBALS['lastinsertid'];
	foreach ($updateGlobals['parents'] as $values['parentId']) if ($values['parentId']>0) {
		$result = query("newparent",$config,$values);
		if($values['nextAction']==='y') $result = query("updatenextaction",$config,$values);	
	}
}

function updateItem() { // update all the values for the current item
// TOFIX - what do we do if we change item type? Probably sever all parent-child links.
	global $config,$values,$updateGlobals;
	query("deletelookup",$config,$values);
	removeNextAction();
    query("updateitemattributes",$config,$values);
    query("updateitem",$config,$values);
	foreach ($updateGlobals['parents'] as $values['parentId']) if ($values['parentId']>0) {
		$result = query("updateparent",$config,$values);
		if (($values['nextAction']==='y') && $values['dateCompleted']==='NULL')
			// we have a next action, so make sure we tag it for each parent
			makeNextAction();
	}
	if ($values['dateCompleted']!=='NULL')
		completeItem();
	else
		query("updateitemstatus",$config,$values);
}

function completeItem() { // mark an item as completed, and recur if required
	global $config,$values;

	if (!isset($values['dateCompleted'])) $values['dateCompleted']="'".date('Y-m-d')."'";
	
	if (!isset($values['repeat'])) {
		$testrow = query("testitemrepeat",$config,$values);
		$values['repeat']=$testrow[0]['repeat'];
	}
	if ($values['repeat']) recurItem(); else makeComplete();
}

function makeNextAction() { // mark the current item as a next action
	global $config,$values;
	$thisquery='updatenextaction';
    $parentresult = query("lookupparent",$config,$values);
    if ($parentresult!="-1")
		foreach ($parentresult[0] as $values['parentId'])
			query($thisquery,$config,$values);
}

function removeNextAction() { // remove the next action reference for the current item
	global $config,$values;
	query("deletenextaction",$config,$values);
}

/* ===========================================================================================
	utility functions for the primary actions
   =========================================== */

function retrieveFormVars() { // extract the item values from the HTTP GET and POST variables - TOFIX still to check
	global $config,$values;

	// key variables
	if (isset($_POST['type']))           $values['type']           = $_POST['type'];
	if (isset($_POST['title']))          $values['title']          = mysql_real_escape_string($_POST['title']);
	if (isset($_POST['description']))    $values['description']    = mysql_real_escape_string($_POST['description']);
	if (isset($_POST['desiredOutcome'])) $values['desiredOutcome'] = mysql_real_escape_string($_POST['desiredOutcome']);

	// session variables
	$values['categoryId']  = getVarFromGetPost('categoryId',0);
	$values['contextId']   = getVarFromGetPost('contextId',0);

	// binary yes/no
	$values['nextAction'] = ($_POST['nextAction']==="y")?'y':'n';
	$values['isSomeday']  = ($_POST['isSomeday']==='y')?'y':'n';
	$values['suppress']   = ($_POST['suppress']==='y')?'y':'n';
	$values['delete']     = ($_POST['delete']==='y')?'y':NULL;

	// integers
	if (isset($_POST['suppressUntil'])) $values['suppressUntil']  = (int) $_POST['suppressUntil']; // ($values['suppress']==='n')?0:(int) $_POST['suppressUntil'];
	if (isset($_POST['repeat']))        $values['repeat']         = (int) $_POST['repeat'];
	if (isset($_POST['timeframeId']))   $values['timeframeId']    = $_POST['timeframeId'];

	// dates
	if (isset($_POST['dateCompleted'])) $values['dateCompleted'] = ($_POST['dateCompleted'] ==='')?"NULL":"'{$_POST['dateCompleted']}'";
	if (isset($_POST['deadline']))      $values['deadline']      = ($_POST['deadline']      ==='')?"NULL":"'{$_POST['deadline']}'";

	// crude error checking
	if (!isset($values['title'])) die ("No title. Item NOT added."); // TOFIX
	if($values['isSomeday']==='y') $values['type']='s';

	if (($config['debug']==='freezedb') || ($config['debug']=='developer')) {
		echo '<hr /><pre><b>retrieved form vars</b><br />';
		literaldump('$values');
		echo '</pre>';
	}
}

function getItemCopy() { // retrieve all the values for the current item, and store in the $values array
	// NB this doesn't get parents - we currently assume that they are available in $updateGlobals['parents'] . This is not a problem for now, but might be, later!
	global $config,$values;
	$copyresult = query("selectitem",$config,$values,$options,$sort);
	foreach ($copyresult[0] as $key=>$thisvalue) $values[$key]=$thisvalue;
	if (($config['debug']==='freezedb') || ($config['debug']=='developer')) {
		echo '<pre>Retrieved record for copying: </pre>';
		literaldump('$values');
	}
}

function recurItem() { // mark a recurring item completed, and set up the recurrence
	global $config,$values;
	// calculate date to recur to, based on: date completed + number of days between recurrences
	$dateArray=explode("-", str_replace("'",'',$values['dateCompleted']));
	$unixdateCompleted=mktime(12,0,0,$dateArray[1],$dateArray[2],$dateArray[0]);
	$nextdue=strtotime("+".$values['repeat']." day",$unixdateCompleted);

	if ($config['storeRecurrences']) {
		makeComplete();
		getItemCopy();
	}

	$values['dateCompleted']="NULL"; 
	$values['deadline']="'".gmdate("Y-m-d", $nextdue)."'";

	if ($config['storeRecurrences'])
		createItem();
	else {
		query("updatedeadline",$config,$values);
		query("updateitemstatus",$config,$values);
	}
}

function makeComplete() { // mark an action as completed, and removes next action marker for it
	global $config,$values;
	query("completeitem",$config,$values);
	removeNextAction();
}

/* ===========================================================================================
	general utility functions that don't modify the database
   ========================================================= */

function nextPage() { // set up the forwarding to the next page
	/*	APS note: I'm not really happy about doing it this way;
		the forwarding should be done in PHP, not via the user's browser.
		However, that requires a bigger rewrite: we need to split header.php into 2 separate files:
		1 for UI, 1 for backend. Then we'd only use the backend half here
	*/
	global $config,$values,$updateGlobals;
	if (isset($_POST['afterCreate' . $_POST['type']]))
		$_SESSION['afterCreate' . $_POST['type']]=$_POST['afterCreate' . $_POST['type']];
	if (isset($updateGlobals['referrer']) && ($updateGlobals['referrer'] !== ''))
		$nextURL=$updateGlobals['referrer'];
	else switch ($_SESSION['afterCreate' . $_POST['type']]) {
		case "parent"  : $nextURL=($updateGlobals['parents'][0]>0)?('itemReport.php?itemId='.$updateGlobals['parents'][0]):('orphans.php'); break;
		case "item"    : $nextURL='itemReport.php?itemId='.$values['newitemId']; break;
		case "another" : $nextURL='item.php?type='        .$values['type']; break;
		case "list"	   : $nextURL='listItems.php?type='   .$values['type']; break;
        default        : $nextURL='listItems.php?type='   .$values['type']."&amp;dbg=".$_SESSION['afterCreate' . $_POST['type']];
	}
	if ($config['debug']==='false') {
		echo '<META HTTP-EQUIV="Refresh" CONTENT="0; url=',$nextURL,'" />';
	} else {
		echo '<p>Next page is <a href="',$nextURL,'">&lt;',htmlspecialchars($nextURL),'&gt;</a> (would be auto-refresh in non-debug mode)</p>';
	}
	echo "\n";
}

function literaldump($varname) { // dump a variable name, and its contents
	echo "<pre><b>$varname</b>=";
	eval("print_r((isset($varname))?$varname:\$GLOBALS['".substr($varname,1)."']);"); // var_dump
	echo '</pre>';
}

?>
