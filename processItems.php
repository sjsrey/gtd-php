<?php
//INCLUDES
require_once('headerDB.inc.php');

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
$updateGlobals['referrer'] = (isset($_REQUEST['referrer'])) ?$_REQUEST['referrer']:null;

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
    echo "<html><head><title>Process Item</title></head><body>\n";
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
	if (isset($updateGlobals['isMarked'])) { // doing a specific action on several items
		foreach ($updateGlobals['isMarked'] as $nextItem) {
			$values=array('itemId'=>$nextItem); // reset the $values array each time, so that it only contains itemId
			doAction($action);
		}
	}
} else {
	if (isset($_POST['doDelete']) && $_POST['doDelete']==='y') $action='delete'; // override item-update if we are simply deleting
	doAction($action);
}

nextPage();
if ($html)
    echo "</body></html>";
else
    echo '</head></html>';
return;

/*========================================================================================
  main program finished - utility functions from here, below
========================================================================================*/

function doAction($localAction) { // do the current action on the current item; returns TRUE if succeeded, else returns FALSE
	global $config,$values,$updateGlobals,$title;
	if ($values['itemId']) {
        $result=query('getitembrief',$config,$values); // TOFIX - should really only do this query at the end, after processing, if necessary then
        if ($result) {
            $briefitem=$result[0];
    	    $title=($result)?$briefitem['title']:'title unknown';
        } else $briefitem=null;
    } else
        $title=(empty($_POST['title']))?'':$_POST['title'];

	if ($config['debug'] & _GTD_DEBUG) echo "<p><b>Action here is: $localAction item {$values['itemId']} - $title</b></p>";
    if ($title=='') $title='item '.$values['itemId'];
	if ($config['debug'] & _GTD_FREEZEDB) return TRUE;

	switch ($localAction) {
        //-----------------------------------------------------------------------------------
        case 'category':
            $values['categoryId']=$_POST['categoryId'];
            query('updateitemcategory',$config,$values);
            query("touchitem",$config,$values);
            $msg="Set category for '$title'";
            break;
        //-----------------------------------------------------------------------------------
		case 'changeType':
			changeType();
			$newtype=getTypes($values['type']);
			$msg="$newtype is now the type for item: '$title'";
			$updateGlobals['referrer']="item.php?itemId={$values['itemId']}&amp;referrer={$updateGlobals['referrer']}";
			break;
        //-----------------------------------------------------------------------------------
        case 'checkcomplete':
            $msg=doChecklist();
            break;
        //-----------------------------------------------------------------------------------
        case 'clearCheckmark':
            $values['dateCompleted']='NULL';
            query("completeitem",$config,$values);
            $msg="Checkmark cleared from '$title'";
            break;
        //-----------------------------------------------------------------------------------
		case 'complete':
			completeItem();
			$msg="Completed '$title'";
			break;
        //-----------------------------------------------------------------------------------
        case 'context':
            $values['contextId']=$_POST['contextId'];
            query('updateitemcontext',$config,$values);
            query("touchitem",$config,$values);
            $msg="Set space context for '$title'";
            break;
        //-----------------------------------------------------------------------------------
		case 'createbasic': // deliberately flows through to case create
        case 'create':
			retrieveFormVars();
			createItem();
			$msg="Created item: '$title'";
			if (isset($_REQUEST['addAsParentTo'])) {
			    addAsParent();
                $msg.=" and added it as a parent";
            }
			break;
        //-----------------------------------------------------------------------------------
		case 'delete':
			deleteItem();
			$msg="Deleted '$title'";
			break;
        //-----------------------------------------------------------------------------------
        case 'fullUpdate':
			retrieveFormVars();
			updateItem();
			$msg="Updated '$title'";
			break;
        //-----------------------------------------------------------------------------------
		case 'makeNA':
			makeNextAction();
			$msg="'$title' is now a next action";
			break;
        //-----------------------------------------------------------------------------------
		case 'removeNA':
			removeNextAction();
            $msg="'$title' is no longer a next action";
			break;
        //-----------------------------------------------------------------------------------
        case 'tag':
            $values['tagname']=$_POST['tag'];
            query('newtagmap',$config,$values);
            query("touchitem",$config,$values);
            $msg="Tagged '$title' with '{$values['tagname']}'";
            break;
        //-----------------------------------------------------------------------------------
        case 'timecontext':
            $values['timeframeId']=$_POST['timeframeId'];
            query('updateitemtimecontext',$config,$values);
            query("touchitem",$config,$values);
            $msg="Set time context for '$title'";
            break;
        //-----------------------------------------------------------------------------------
		case 'updateText':
            // overlay any values from $_POST, defaulting to current values
            foreach (array('title','description','desiredOutcome') as $field)
                $values[$field] = (isset($_POST[$field]))
                    ? iconv('UTF-8',$config['charset'].'//IGNORE',$_POST[$field])
                    : $briefitem[$field];
            $result=query('updateitemtext',$config,$values);
            query("touchitem",$config,$values);
            $msg="Updated '$title'";
            break;
        //-----------------------------------------------------------------------------------
		default: // failed to identify which action we should be taking, so quit
			return FALSE;
	}
	$_SESSION['message'][] = $msg;
	return TRUE; // we have successfully carried out some action
}

/* ===========================================================================================
	primary action functions
   ================================= */
function doChecklist() {
	global $config,$values,$updateGlobals,$title;
	if (empty($_REQUEST['clearchecklist']))
        $todo=$updateGlobals['isMarked'];
    else
        $todo=array();
    $values['parentId']=$updateGlobals['parents'][0];
    if (!isset($values['dateCompleted']))
        $values['dateCompleted']="'".date('Y-m-d')."'";
    $sep='';
    $ids='';
    foreach ($todo as $id) {
        $ids.=$sep.(int) $id;
        $sep="','";
    }
    $values['itemfilterquery']="$ids";
    query("updatechecklist",$config,$values);
    $msg  = ($cnt=count($todo))." checklist item"
            .( ($cnt===1) ? '' : 's' )
            .' marked complete';
    return $msg;
}
//===========================================================================
function deleteItem() { // delete all references to a specific item
	global $config,$values;
	query("deleteitemstatus",$config,$values);
	query("deleteitemattributes",$config,$values);
	query("deleteitem",$config,$values);
	query("deletelookup",$config,$values);
	query("deletelookupparents",$config,$values);
}
//===========================================================================
function createItem() { // create an item and its parent-child relationships
	global $config,$values,$updateGlobals,$title;
	//Insert new records
	$result = query("newitem",$config,$values);
	$values['newitemId'] = $GLOBALS['lastinsertid'];
	$result = query("newitemstatus",$config,$values);
	if ($values['type']!=='L' && $values['type']!=='C' && $values['type']!=='T')
	   $result = query("newitemattributes",$config,$values);
	setParents('new');
	$title=$values['title'];
	$values['itemId']=$values['newitemId'];
	updateTags();
}
//===========================================================================
function updateItem() { // update all the values for the current item
	global $config,$values,$updateGlobals,$title;
	query("deletelookup",$config,$values);
    if ($values['type']!=='L' && $values['type']!=='C' && $values['type']!=='T')
        query("updateitemattributes",$config,$values);
    query("updateitem",$config,$values);
    query("updateitemstatus",$config,$values);
    updateTags();
    if ($values['type'] === $values['oldtype']) {
    	setParents('update');
    } else {
        // changing item type - sever child links
    	query("deletelookupparents",$config,$values);
    }
	if ($values['dateCompleted']==='NULL')
		query('completeitem',$config,$values);
	else
		completeItem();
	$title=$values['title'];
}
//===========================================================================
function completeItem() { // mark an item as completed, and recur if required
	global $config,$values;
	if (!isset($values['dateCompleted'])) $values['dateCompleted']="'".date('Y-m-d')."'";
	if (!isset($values['recur'])) {
		$testrow = query("testitemrepeat",$config,$values);
		if ($testrow) {
            $values['deadline']  =$testrow[0]['deadline'];
            $values['recur']     =$testrow[0]['recur'];
            $values['tickledate']=$testrow[0]['tickledate'];
        }
	}
	if (empty($values['recur'])) makeComplete(); else recurItem();
}
//===========================================================================
function makeNextAction() { // mark the current item as a next action
	global $config,$values;
	$values['nextaction']='y';
    query('updatenextaction',$config,$values);
}
//===========================================================================
function removeNextAction() { // remove the next action reference for the current item
	global $config,$values;
	$values['nextaction']='n';
    query('updatenextaction',$config,$values);
}
//===========================================================================
function changeType() {
	global $config,$values;
    $values['isSomeday']=isset($_REQUEST['isSomeday'])?$_REQUEST['isSomeday']:'n';
    query("updateitemtype",$config,$values);
    if (empty($_REQUEST['safe'])) {
    	query("deletelookup",$config,$values);
    	query("deletelookupparents",$config,$values);
    	removeNextAction();
    }
}
/* ===========================================================================================
	utility functions for the primary actions
   =========================================== */

function updateTags() {
    global $config,$values;
    query('removeitemtags',$config,$values);
    if (!empty($values['alltags']))
        foreach ($values['alltags'] as $tag)
            if (!empty($tag)) {
                $values['tagname']=trim($tag);
                query('newtagmap',$config,$values);
            }
}
//===========================================================================
function addAsParent() {
    global $config,$values;
    // we need to make the item we've just created, a parent of the item with id addAsParentTo
    $tempvalues=array('parentId'=>$values['newitemId'],'newitemId'=>$_REQUEST['addAsParentTo']);
    $result = query("newparent",$config,$tempvalues);
}
//===========================================================================
function retrieveFormVars() {
	global $config,$updateGlobals,$values;

    // TOFIX - what we should really do here is retrieve the item, and then over-write with $_POST variables if and only if they are available
    // although we'd need to check that unticked checkboxes came through ok - that could be tricky
	// key variables
	$values['oldtype'] = (empty($_POST['oldtype'])) ? $values['type'] : $_POST['oldtype'];

	foreach ( array('type'=>'i','title'=>'untitled','description'=>''
            ,'desiredOutcome'=>'','categoryId'=>0,'contextId'=>0
            ,'timeframeId'=>0) as $field=>$default) {
        if (empty($_POST[$field]))
            $values[$field] = $default;
        elseif (empty($_POST['fromjavascript']))
            $values[$field] = $_POST[$field];
        else {
            $values[$field] = iconv('UTF-8',$config['charset'].'//IGNORE',$_POST[$field]);
        }
    }
    $tags=(isset($_POST['tags']))?strtolower($_POST['tags']):'';
    $tags=array_unique(explode(',',$tags));
    if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>tags=",print_r($tags,true),"</p>\n";
    $values['alltags']=$tags;
    
	// binary yes/no
	foreach (array('nextaction','isSomeday') as $field)
        $values[$field] = (isset($_POST[$field]) && $_POST[$field]==="y")?'y':'n';

	// dates
	foreach ( array('tickledate','dateCompleted','deadline') as $field)
	   $values[$field]  = (empty($_POST[$field])) ? "NULL" : "'{$_POST[$field]}'";

    if (    empty($_POST['FREQtype']) 
        || $_POST['FREQtype']==='NORECUR' 
        || ($_POST['FREQtype']==='TEXT' && empty($_POST['icstext']))) {
        $values['recur']=null;
        $values['recurdesc']=null;
    } else {
        processRecurrence();
    }

	if ($config['debug'] & _GTD_DEBUG) {
		echo '<hr /><pre><b>retrieved form vars</b><br />';
		literaldump('$values');
		echo '</pre>';
	}
}
//===========================================================================
function processRecurrence() {
    global $config,$values;
    $rrule=array();
    require_once 'iCalcreator.class.inc.php';
    $vevent = new vevent();

    $rrule=array();
    $rrule['INTERVAL']= (empty($_POST['INTERVAL'])) ? 1 : $_POST['INTERVAL'];
    if (!empty($_POST['UNTIL'])) $rrule['UNTIL']=$_POST['UNTIL'];
    switch ($_POST['FREQtype']) {
        case ('TEXT') :
            $vevent->parse(array('RRULE:'.$_POST['icstext']));
            $rrule=array();
            break;
        case ('DAILY'):   // Deliberately flows through to next case
        case ('WEEKLY'):  // Deliberately flows through to next case
        case ('MONTHLY'): // Deliberately flows through to next case
        case ('YEARLY'):
            $rrule['FREQ']=$_POST['FREQtype'];
            break;
        // end of simple cases - now the trickier stuff
        case ('WEEKLYBYDAY'):
            $rrule['FREQ']='WEEKLY';
            if (is_array($_POST['WEEKLYday'])) {
                $out=array();
                foreach ($_POST['WEEKLYday'] as $val)
                    array_push($out,array('DAY'=>$val));
                $rrule['BYDAY']=$out;
            }
            break;
        case ('MONTHLYBYDAY'):
            $rrule['FREQ']='MONTHLY';
            $rrule['BYMONTHDAY']=array($_POST['MONTHLYdate']);
            break;
        case ('MONTHLYBYWEEK'):
            $rrule['FREQ']='MONTHLY';
            $rrule['BYDAY']=array( (int) $_POST['MONTHLYweek'] ,
                'DAY'=> $_POST['MONTHLYweekday']  );
            break;
        case ('YEARLYBYDATE'):
            $rrule['FREQ']='YEARLY';
            $rrule['BYMONTHDAY']=array($_POST['YEARLYdate']);
            $rrule['BYMONTH']=array($_POST['YEARLYmonth']);
            break;
        case ('YEARLYBYWEEK'):
            $rrule['FREQ']='YEARLY';
            $rrule['BYMONTH']=array($_POST['YEARLYweekmonth']);
            $rrule['BYDAY']=array( (int) $_POST['YEARLYweeknum'] ,
                                'DAY'=> $_POST['YEARLYweekday']  );
            break;
        default:
            $values['recurdesc']='';
            $values['recur']='';
            return false;
    }
    /*  got all the data from the form
        --------------------------------------------------------------------
    */
    if ($_POST['FREQtype']!=='TEXT')
        $vevent->setProperty( "rrule",$rrule);
    if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>RRULE: ",print_r($rrule,true),"</p>";
    
    $rrule=$vevent->getProperty('rrule');
    $rruletext=$vevent->_format_recur('',array(array('value'=>$rrule)));
    if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>RRULEtext: $rruletext =",print_r($rrule,true),"</p>";
    // now we've done the round trip, we can be confident that it's a valid recurrence string, so store it
    $values['recur']=$rruletext;
    if (  !empty($rruletext)
        && ( empty($values['deadline'])   || $values['deadline']==='NULL'   )
        && ( empty($values['tickledate']) || $values['tickledate']==='NULL' ) ) {
        // haven't got a startdate, so use what the next recurrence date would be
        $nextdue=getNextRecurrence();
        if ($nextdue) $values['deadline']="'$nextdue'";
        if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>Forcing deadline where none given - $nextdue</p>";
    }
    if (empty($_POST['recurdesc'])) {
        // set desc based on intelligent description
        $values['recurdesc']="+{$rrule['INTERVAL']}".substr($rrule['FREQ'],0,1);
    } else
        $values['recurdesc']=$_POST['recurdesc'] ;
}
//===========================================================================
function recurItem() {
	global $config,$values,$updateGlobals;
	require_once 'iCalcreator.class.inc.php';

    $nextdue=getNextRecurrence();

    // before processing the next due date, do some house-cleaning and preparation
    $values['oldDateCompleted']=$values['dateCompleted'];
	if ($config['storeRecurrences']) {
		$copy=getItemCopy();
		makeComplete();
		$values=array_merge($values,$copy);
		$updateGlobals['parents']=$copy['parents'];
		if (isset($updateGlobals['isNA']) && in_array($values['itemId'],$updateGlobals['isNA']))
			$values['nextaction']='y';
	}
	$values['dateCompleted']="NULL";
	if (empty($values['tickledate'])) $values['tickledate']='NULL';

    // now process the next due date
    if (empty($nextdue)) {
        $msg="There are no further occurrences of item {$values['itemId']} - {$values['title']}";
        if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>$msg</p>";
        $_SESSION['message'][] = $msg;
    } else {
        // now need to set tickle date (either to NULL, or to date in quotes)
    	if (empty($values['deadline']) || $values['deadline']==='NULL') {
            $values['tickledate']="'$nextdue'";
            $values['deadline']='NULL';
    	} else {
            if ($values['tickledate']!=='NULL')
                $values['tickledate']= date( "'Y-m-d'" ,
                     strtotime(str_replace("'",'',$values['tickledate']))
                   + (   strtotime($nextdue)
                       - strtotime(str_replace("'",'',$values['deadline']))
                     )
                );
            $values['deadline']="'$nextdue'";
    	}
        if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>new deadline={$values['deadline']}, new tickler={$values['tickledate']}</p>";
    	if ($config['storeRecurrences']) createItem();
    } // end of processing next due date
    
	if (!$config['storeRecurrences']) {
		query("updatedeadline",$config,$values);
		query("completeitem",$config,$values); // reset completed date to null, and touch the last modified date
	}
}
//===========================================================================
function getItemCopy() { // retrieve values for the current item, and store in the $values array
	global $config,$values,$updateGlobals;
	$result = query("selectitem",$config,$values,array());
	$copy=($result) ? $result[0] : array();
	// now get parents
	$result=query("selectparents",$config,$values,array());
	$copy['parents']=array();
	if ($result) {
        foreach ($result as $parent)
            $copy['parents'][]=$parent['parentId'];
    }
	if ($config['debug'] & _GTD_DEBUG) {
		echo '<pre>Retrieved record for copying: </pre>';
		literaldump('$values');
		echo '<pre>Parents:',print_r($copy['parents'],true),'</pre>';
	}
	return $copy;
}
//===========================================================================
function setParents($new) {
    global $config,$values,$updateGlobals;
	if($config['debug'] & _GTD_DEBUG) echo '<pre>',print_r($updateGlobals['parents'],true),'</pre>';
    foreach ($updateGlobals['parents'] as $values['parentId'])
        if ($values['parentId'])
    	   $result = query($new."parent",$config,$values);
}
//===========================================================================
function makeComplete() { // mark an action as completed
	global $config,$values;
	query("completeitem",$config,$values);
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
    $tst=false;
    if (!empty($_POST['afterCreate'])) {
        $_SESSION[$key]=$tst=$_POST['afterCreate'];
    } else {
        $submitbuttons=array('parent','item','list','another','child','referrer');
        foreach ($submitbuttons as $testbutton) if (isset($_POST["{$testbutton}Next"])) {
            $_SESSION[$key]=$tst=$testbutton;
            break;
        }
    }
    if (!$tst) {
        if (isset($updateGlobals['referrer']) && ($updateGlobals['referrer'] !== ''))
		  $tst=$updateGlobals['referrer'];
        elseif(!empty($_SESSION[$key]))
            $tst=$_SESSION[$key];
    }
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
              'timeframeId'=>'timeframeId',
              'suppress'=>'suppress','deadline'=>'deadline',
              'isSomeday'=>'isSomeday','tickledate'=>'tickledate'
              ) as $key=>$cat )
                  if (!empty($values[$key]) && $values[$key]!='NULL') $nextURL.="&amp;$cat=".str_replace("'","",$values[$key]);
            if (!empty($values['nextaction']) && $values['nextaction']==='y') $nextURL.="&amp;nextonly=true";
            break;
		case 'child'   :
            $child=getChildType($values['type']);
            $nextURL="item.php?parentId=$id&amp;type={$child[0]}";
            if ($child[0]==='a') $nextURL.='&amp;nextonly=true';
            foreach (array('categoryId','contextId','timeframeId') as $field)
                if (!empty($values[$field])) $nextURL.="&amp;$field=".$values[$field];
            break;
        case "item"    :
            $nextURL="itemReport.php?itemId=$id";
            break;
		case "list"	   :
            $nextURL="listItems.php?type=$t";
            if (!empty($values['isSomeday']) && $values['isSomeday']==='y') {
                $nextURL.='&someday=true';
            } elseif (!empty($values['tickledate']) && time() < strtotime($values['tickledate']) ) {
                $nextURL.='&tickler=true';
            }
            break;
		case "parent"  :
            $nextURL=(count($updateGlobals['parents']))
                        ?('itemReport.php?itemId='.$updateGlobals['parents'][0])
                        :'orphans.php';
            break;
		case "referrer":
            $nextURL=(empty($updateGlobals['referrer']) )
                        ? (empty($_SESSION["lastfilter$t"])?'':$_SESSION["lastfilter$t"])
                        : $updateGlobals['referrer'];
            break;
        default        :
            $nextURL=$tst;
            break;
	}
	if ($config['debug'] & _GTD_DEBUG) {
        echo '<pre>$referrer=',print_r($updateGlobals['referrer'],true),'<br />'
            ,((empty($values['type']))?'':"type={$values['type']}<br />")
            ,'session=',print_r($_SESSION,true),'<br />'
            ,'</pre>';
    }
    if ($nextURL=='')
        $nextURL="listItems.php?type=$t";
    else if (strpos($nextURL,'nextId=0')!==false) {
        $nextURL=str_replace('nextId=0','nextId='.$values['newitemId'],$nextURL);
        $_SESSION[$key]=$tst;
        $_SESSION['message'][]='Creation of this '.getTypes($values['type']).' has been suspended while parent is created';
    }
    $nextURL=html_entity_decode($nextURL);
	
	if ($updateGlobals['captureOutput']) {
        $logtext=ob_get_contents();
        ob_end_clean();
        $outtext=$_SESSION['message'];
        $_SESSION['message']=array();
        if (!headers_sent()) {
            $header="Content-Type: text/xml; charset=".$config['charset'];
            header($header);
        }
        echo '<?xml version="1.0" ?',">\n<gtdphp>\n"; // encoding="{$config['charset']}"
        echo "<values>\n";
        foreach ($values as $key=>$val) {
            echo "<$key>";
            switch ($key) {
                case 'description':     
                case 'desiredOutcome':  // deliberately flows through
                    $val=nl2br($val);
                case 'title':           // deliberately flows through
                    echo "<![CDATA[$val]]>";
                    break;
                case 'deadline':        // deliberately flows through
                case 'tickledate':
                    echo str_replace("'",'',$val);
                    break;
                default:
                    echo '<![CDATA[',makeclean($val),']]>';
                    break;
            }
            echo "</$key>\n";
        }
        echo '</values>';

        echo '<result>';
        if (!empty($outtext)) foreach ($outtext as $line) echo "<line><![CDATA[$line]]></line>";
        echo '</result>';

        echo "<nextURL><![CDATA[$nextURL]]></nextURL>";
        echo "<log><![CDATA[$logtext]]></log>";
        echo "</gtdphp>";
        exit;
    } else nextScreen($nextURL);
}
//===========================================================================
function literaldump($varname) { // dump a variable name, and its contents
	echo "<pre><b>$varname</b>=";
	$tst="print_r((isset($varname))?($varname):(\$GLOBALS['".substr($varname,1)."']));return 1;";
	if (eval($tst))
		echo '</pre>';
	else
		echo "<br />Failed to display variable value: $tst <br />";
}
//===========================================================================

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
