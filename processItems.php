<?php
//INCLUDES
include_once('headerDB.inc.php');

$updateGlobals=array();
$html=false; // indicates if we are outputting html

$updateGlobals['captureOutput']=(isset($_POST['output']) && $_POST['output']==='xml');
if ($updateGlobals['captureOutput']) {
    ob_start();
}

// get core variables first
$values=array();  // ensures that this is a global variable
$values['itemId'] = isset($_REQUEST['itemId'])?(int) $_REQUEST['itemId']:null;
$values['type'] = (isset($_REQUEST['type']))?$_REQUEST['type']:null;

$action = $_REQUEST['action'];
$updateGlobals['referrer'] = $_REQUEST['referrer'];

$updateGlobals['multi']    = (isset($_POST['multi']) && $_POST['multi']==='y');
$updateGlobals['parents'] = (isset($_POST['parentId']))?$_POST['parentId']:array();
if (!is_array($updateGlobals['parents'])) $updateGlobals['parents']=array($updateGlobals['parents']);

if (isset($_POST['wasNAonEntry'])) {  // toggling next action status on several items
	$updateGlobals['wasNAonEntry'] = explode(' ',$_POST['wasNAonEntry']);
	$updateGlobals['isNA']=array();
	if (isset($_POST['isNAs'])) $updateGlobals['isNA']=$_POST['isNAs'];
}

if (isset($_POST['isMarked'])) { // doing a specific action on several items (currently, the only option is to complete them)
	$updateGlobals['isMarked']=array();
	$updateGlobals['isMarked']=array_unique($_POST['isMarked']); // remove duplicates
}

// some debugging - if debug is set to halt, dump all the variables we've got

if ($config['debug'] & _GTD_DEBUG) {
    include 'headerHtml.inc.php';
    echo "</head><body><div id='container'>\n";
    $html=true;
	// debugging text - simply dump the variables, and quit, without processing anything
	literaldump('$_GET');
    literaldump('$_POST');
    literaldump('$_SESSION');
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

$title='';

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
	if (isset($_POST['delete']) && $_POST['delete']==='y') $action='delete'; // override item-update if we are simply deleting
	doAction($action);
}

nextPage();
if ($html)
    include_once('footer.php');
else
    echo '</head></html>';
return;

/*========================================================================================
  main program finished - utility functions from here, below
========================================================================================*/

function doAction($localAction) { // do the current action on the current item; returns TRUE if succeeded, else returns FALSE
	global $config,$values,$updateGlobals,$title;
	if ($values['itemId']) {
        $result=query('getitembrief',$config,$values);
    	$title=($result)?$result[0]['title']:'title unknown';
    } else
        $title=$_POST['title'];

    if ($title=='') $title='item '.$values['itemId'];

	if ($config['debug'] & _GTD_DEBUG) echo "<p><b>Action here is: $localAction item {$values['itemId']}</b></p>";
	if ($config['debug'] & _GTD_FREEZEDB) return TRUE;
	switch ($localAction) {
		case 'makeNA':
			makeNextAction();
			$msg="'$title' is now a next action";
			break;
			
		case 'removeNA':
			removeNextAction();
            $msg="'$title' is no longer a next action";
			break;
			
		case 'changeType':
			changeType();
			$newtype=getTypes($values['type']);
			$msg="$newtype is now the type for item: '$title'";
			break;

        case 'create':
			retrieveFormVars();
			createItem();
			$msg="Created item: '$title'";
			break;
			
		case 'complete':
			completeItem();
			$msg="Completed '$title'";
			break;
		
		case 'fullUpdate':
			retrieveFormVars();
			updateItem();
			$msg="Updated '$title'";
			break;
			
		case 'delete':
			deleteItem();
			$msg="Deleted '$title'";
			break;
		
		case 'createbasic': // not in use yet. added for future use, when only title and type are set.
			createItemQuickly();
			$msg="Created item: '$title'";
			break;
			
		default: // failed to identify which action we should be taking, so quit
			return FALSE;
	}
	$_SESSION['message'][] = $msg;
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
	global $config,$values,$updateGlobals,$title;
	//Insert new records
	$result = query("newitem",$config,$values);
	$values['newitemId'] = $GLOBALS['lastinsertid'];
	$result = query("newitemattributes",$config,$values);
	$result = query("newitemstatus",$config,$values);
	setParents('new');
	$title=$values['title'];
}

function createItemQuickly() {// create an item when we only know its type and title - not yet in use - TOFIX still to check
	global $config,$values,$updateGlobals,$title;
	//Insert new records
	$result = query("newitem",$config,$values);
	$values['newitemId'] = $GLOBALS['lastinsertid'];
	setParents('new');
	$title=$values['title'];
}

function updateItem() { // update all the values for the current item
	global $config,$values,$updateGlobals,$title;
	query("deletelookup",$config,$values);
	removeNextAction();
    query("updateitemattributes",$config,$values);
    query("updateitem",$config,$values);
    if ($values['type'] === $values['oldtype'])
    	setParents('update');
    else
        // changing item type - sever child links
    	query("deletelookupparents",$config,$values);

	if ($values['dateCompleted']==='NULL')
		query('completeitem',$config,$values);
	else
		completeItem();
	$title=$values['title'];
}

function completeItem() { // mark an item as completed, and recur if required
	global $config,$values;

	if (!isset($values['dateCompleted'])) $values['dateCompleted']="'".date('Y-m-d')."'";
	
	if (!isset($values['repeat']) || !isset($values['old']['dateCompleted'])) {
		$testrow = query("testitemrepeat",$config,$values);
		if (!isset($values['repeat'])) $values['repeat']=$testrow[0]['repeat'];
        if (!isset($values['old'])) $values['old']=array();
		$values['old']['dateCompleted']=$testrow[0]['dateCompleted'];
	}
	if ($values['repeat'] && empty($values['old']['dateCompleted'])) recurItem(); else makeComplete();
}

function makeNextAction() { // mark the current item as a next action
	global $config,$values;
	$thisquery='updatenextaction';
    $parentresult = query("lookupparent",$config,$values);
    if ($parentresult) {
        foreach ($parentresult as $parent) {
    		$values['parentId']=$parent['parentId'];
    		query($thisquery,$config,$values);
        }
    } else {
        $values['parentId']=0;
		query($thisquery,$config,$values);
	}
	query("touchitem",$config,$values);
}

function removeNextAction() { // remove the next action reference for the current item
	global $config,$values;
	query("deletenextaction",$config,$values);
	query("touchitem",$config,$values); // TOFIX - doing this too often - probably move all occurrences into function doAction
}

function changeType() {
	global $config,$values;
    $values['isSomeday']=isset($_REQUEST['isSomeday'])?$_REQUEST['isSomeday']:'n';
    query("updateitemtype",$config,$values);
    if ($_REQUEST['safe']!=='y') {
    	query("deletelookup",$config,$values);
    	query("deletelookupparents",$config,$values);
    	removeNextAction();
    	query("deletenextactionparents",$config,$values);
    }
}
/* ===========================================================================================
	utility functions for the primary actions
   =========================================== */

function retrieveFormVars() {
	global $config,$values;

    // TOFIX - what we should really do here is retrieve the item, and then over-write with $_POST variables if and only if they are available
    // although we'd need to check that unticked checkboxes came through ok - that could be tricky
	// key variables
	if (!empty($_POST['oldtype'])) $values['oldtype'] = $_POST['oldtype'];

	foreach (
        array('type'=>'i','title'=>'untitled','description'=>''
            ,'desiredOutcome'=>'','categoryId'=>0,'contextId'=>0
            ,'timeframeId'=>0)
        as $field=>$default)
            $values[$field] = (empty($_POST[$field])) ? $default : $_POST[$field];

	// binary yes/no
	$values['nextAction'] = (isset($_POST['nextAction']) && $_POST['nextAction']==="y")?'y':'n';
	$values['isSomeday']  = (isset($_POST['isSomeday']) && $_POST['isSomeday']==='y')?'y':'n';
	$values['suppress']   = (isset($_POST['suppress']) && $_POST['suppress']==='y')?'y':'n';
	$values['delete']     = (isset($_POST['delete']) && $_POST['delete']==='y')?'y':NULL;

	// integers
	$values['suppressUntil']  = empty($_POST['suppressUntil'])?0:(int) $_POST['suppressUntil'];
	$values['repeat']         = empty($_POST['repeat'])?0:(int) $_POST['repeat'];

	// dates
	$values['dateCompleted'] = (empty($_POST['dateCompleted'])) ? "NULL" : "'{$_POST['dateCompleted']}'";
	$values['deadline']      = (empty($_POST['deadline']))      ? "NULL" : "'{$_POST['deadline']}'";

	if ($config['debug'] & _GTD_DEBUG) {
		echo '<hr /><pre><b>retrieved form vars</b><br />';
		literaldump('$values');
		echo '</pre>';
	}
}

function getItemCopy() { // retrieve all the values for the current item, and store in the $values array
	global $config,$values,$updateGlobals;
	$copyresult = query("selectitem",$config,$values,array());
	foreach ($copyresult[0] as $key=>$thisvalue) $values[$key]=$thisvalue;
	// now get parents
	$result=query("lookupparent",$config,$values,array());
	$updateGlobals['parents']=array();
	if (is_array($result))
		foreach ($result as $parent)
			$updateGlobals['parents'][]=$parent['parentId'];
	if ($config['debug'] & _GTD_DEBUG) {
		echo '<pre>Retrieved record for copying: </pre>';
		literaldump('$values');
		echo '<pre>Parents:',print_r($updateGlobals['parents'],true),'</pre>';
	}
}

function setParents($new) {
    global $config,$values,$updateGlobals;
	if($config['debug'] & _GTD_DEBUG) echo '<pre>',print_r($updateGlobals['parents'],true),'</pre>';
    $markedna=false;
    foreach ($updateGlobals['parents'] as $values['parentId']) if ($values['parentId']) {
    	$result = query($new."parent",$config,$values);
    	if(!empty($values['nextAction']) && $values['nextAction']==='y') {
            $result = query($new."nextaction",$config,$values);
            $markedna=true;
        }
   	}
    if (!$markedna && $values['nextAction']==='y') {
        $values['parentId']=0;
        $result = query($new."nextaction",$config,$values);
    }
}

function recurItem() { // mark a recurring item completed, and set up the recurrence
	global $config,$values,$updateGlobals;
	// calculate date to recur to, based on: date completed + number of days between recurrences
	$dateArray=explode("-", str_replace("'",'',$values['dateCompleted']));
	$unixdateCompleted=mktime(12,0,0,$dateArray[1],$dateArray[2],$dateArray[0]);
	$nextdue=strtotime("+".$values['repeat']." day",$unixdateCompleted);

	if ($config['storeRecurrences']) {
		makeComplete();
		getItemCopy();
		if (isset($updateGlobals['isNA']) && in_array($values['itemId'],$updateGlobals['isNA']))
			$values['nextAction']='y';
	}

	$values['dateCompleted']="NULL"; 
	$values['deadline']="'".gmdate("Y-m-d", $nextdue)."'";

	if ($config['storeRecurrences'])
		createItem();
	else {
		query("updatedeadline",$config,$values);
		query("completeitem",$config,$values); // reset completed date to null, and touch the last modified date
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
	global $config,$values,$updateGlobals,$action;
	$t = (isset($values['oldtype']))?$values['oldtype']:((isset($values['type']))?$values['type']:null);
	$key='afterCreate'.$t;
    $id=(empty($values['newitemId']))?$values['itemId']:$values['newitemId'];
    $nextURL='';
    if (isset($_POST['afterCreate'])) {
        $tst=$_POST['afterCreate'];
        $_SESSION[$key]=$_POST['afterCreate'];
    }elseif (isset($updateGlobals['referrer']) && ($updateGlobals['referrer'] !== ''))
		$tst=$updateGlobals['referrer'];
    else
        $tst=$_SESSION[$key];
        
    if ($action=='delete' && $tst=='item') $tst='list';

	switch ($tst) {
		case "another" :
            $nextURL="item.php?type=$t";
            if (!empty($updateGlobals['parents'])) {
                $parentlist= (is_array($updateGlobals['parents']))
                            ?implode(',',$updateGlobals['parents'])
                            :$updateGlobals['parents'];
                if ($parentlist!='') $nextURL.="&amp;parentId=$parentlist";
            }
            foreach ( array(
              'categoryId'=>'categoryId','contextId'=>'contextId',
              'timeframeId'=>'timeframeId','nextAction'=>'nextonly',
              'suppress'=>'suppress','deadline'=>'deadline',
              'isSomeday'=>'isSomeday','suppressUntil'=>'suppressUntil'
              ) as $key=>$cat )
                  if (!empty($values[$key]) && $values[$key]!='NULL') $nextURL.="&amp;$cat=".str_replace("'","",$values[$key]);
            break;
		case 'child'   :
            $child=getChildType($values['type']);
            $nextURL="item.php?parentId=$id&amp;type={$child[0]}";
            if ($child[0]==='a') $nextURL.='&amp;nextonly=true';
            foreach (array('categoryId','contextId','timeframeId') as $field)
                if ($values[$field]) $nextURL.="&amp;$field=".$values[$field];
            break;
        case "item"    :
            $nextURL="itemReport.php?itemId=$id";
            break;
		case "list"	   :
            $nextURL="listItems.php?type=$t";
            if ($values['isSomeday']==='y') {
                $nextURL.='&someday=true';
            } else if ($values['suppress']==='y' and
                time() < getTickleDate(str_replace("'",'',$values['deadline']),$values['suppressUntil'])
                ) {
                $nextURL.='&tickler=true';
            }
            break;
		case "parent"  :
            $nextURL=(count($updateGlobals['parents']))
                        ?('itemReport.php?itemId='.$updateGlobals['parents'][0])
                        :'orphans.php';
            break;
		case "referrer":
            $nextURL=$_SESSION["lastfilter$t"];
            break;
        default        :
            $nextURL=$tst;
            break;
	}
	if ($config['debug'] & _GTD_DEBUG) {
        echo '<pre>$referrer=',print_r($updateGlobals['referrer'],true),'<br />'
            ,"type={$values['type']}<br />"
            ,'session=',print_r($_SESSION,true),'<br />'
            ,'</pre>';
    }
    if ($nextURL=='') $nextURL="listItems.php?type=$t";
    $_SESSION[$key]=$tst;
    $nextURL=html_entity_decode($nextURL);
	
	if ($updateGlobals['captureOutput']) {
        $logtext=ob_get_contents();
        ob_end_clean();
        echo '<?xml version="1.0" ?',"><gtdphp><result>\n";
        $outtext=$_SESSION['message'];
        foreach ($outtext as $line)
            echo "<line><![CDATA[$line]]></line>\n";
        echo "</result><nextURL><![CDATA[$nextURL]]></nextURL>\n";
        if (!empty($outtext)) echo "<log><![CDATA[$logtext]]></log>\n";
        echo "</gtdphp>";
        $_SESSION['message']=array();
        exit;
    } else nextScreen($nextURL);
}

function literaldump($varname) { // dump a variable name, and its contents
	echo "<pre><b>$varname</b>=";
	$tst="print_r((isset($varname))?($varname):(\$GLOBALS['".substr($varname,1)."']));return 1;";
	if (eval($tst))
		echo '</pre>';
	else
		echo "<br />Failed to display variable value: $tst <br />";
}

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
