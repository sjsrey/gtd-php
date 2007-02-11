<?php

//INCLUDES
include_once('header.php');

//page display options array--- can put defaults in preferences table/config/session and load into $show array as defaults...
$show=array();

//GET URL VARIABLES
$values = array();
$filter = array();


if (isset($_GET['type'])) $values['type']=$_GET["type"]{0};
else $values['type']="a";

if ($_GET['categoryId']>0)  $values['categoryId']=(int) $_GET['categoryId'];
    else $values['categoryId']=(int) $_POST['categoryId'];

if (isset($_GET['notcategory'])) $filter['notcategory']= $_GET['notcategory'];
    else $filter['notcategory']=$_POST['notcategory'];

if ($_GET['contextId']>0) $values['contextId']=(int) $_GET['contextId'];
    else $values['contextId']=(int) $_POST['contextId'];

if (isset($_GET['notspacecontext'])) $filter['notspacecontext']= $_GET['notspacecontext'];
    else $filter['notspacecontext']=$_POST['notspacecontext'];

if ($_GET['timeId']>0) $values['timeframeId']=(int) $_GET['timeId'];
    else $values['timeframeId']=(int) $_POST['timeId'];

if (isset($_GET['nottimecontext'])) $filter['nottimecontext']= $_GET['nottimecontext'];
    else $filter['nottimecontext']=$_POST['nottimecontext'];

//suppressed (tickler file): true/false
if (isset($_GET['tickler'])) $filter['tickler']=$_GET['tickler'];
else $filter['tickler']=$_POST['tickler'];

//someday/maybe:true/empty
if (isset($_GET['someday'])) $filter['someday']=$_GET['someday'];
else $filter['someday']=$_POST['someday'];

//next actions only: true/empty 
if (isset($_GET['nextonly'])) $filter['nextonly']=$_GET['nextonly'];
else $filter['nextonly']=$_POST['nextonly'];

//status:pending/completed (empty)
if (isset($_GET['completed'])) $filter['completed']=$_GET['completed'];
else $filter['completed']=$_POST['completed'];

//has due date:true/empty
if (isset($_GET['dueonly'])) $filter['dueonly']=$_GET['dueonly'];
else $filter['dueonly']=$_POST['dueonly'];

//is repeating:true/empty
if (isset($_GET['repeatingonly'])) $filter['repeatingonly']=$_GET['repeatingonly'];
else $filter['repeatingonly']=$_POST['repeatingonly'];


//Check Session Variables
//If we have contextId from a new filter, change Session value
$contextId=$values['contextId'];
if ($contextId>=0) $_SESSION['contextId']=$contextId;
else $values['contextId']=$_SESSION['contextId'];

//If we have categoryId from a new filter, change Session value
$categoryId=$values['categoryId'];
if ($categoryId>=0) $_SESSION['categoryId']=$categoryId;
else $values['categoryId']=$_SESSION['categoryId'];

//SQL CODE

//create filters for selectboxes
if ($values['type']=="g") $values['timefilterquery'] = " WHERE ".sqlparts("timegoals",$config,$values);
else $values['timefilterquery'] = " WHERE ".sqlparts("timeitems",$config,$values);

//create filter selectboxes
$cashtml=categoryselectbox($config,$values,$options,$sort);
$cshtml=contextselectbox($config,$values,$options,$sort);
$tshtml=timecontextselectbox($config,$values,$options,$sort);

//select all nextactions for test
$result = query("getnextactions",$config,$values,$options,$sort);

$nextactions = array();
if ($result!="-1") {
    $i=0;
    foreach ($result as $row) {
        $nextactions[$i] = $row['nextaction'];
        $i++;
        }
    }

//Select notes
if ($filter['tickler']=="true") {
    $values['filterquery'] = "";
    $reminderresult = query("getnotes",$config,$values,$options,$sort);
    }

//Select items

//set default table column display options (kludge-- needs to be divided into multidimensional array for each table type and added to preferences table
$show['parent']=FALSE;
$show['title']=TRUE;
$show['description']=TRUE;
$show['desiredOutcome']=FALSE;
$show['isSomeday']=FALSE;
$show['suppress']=FALSE;
$show['suppressUntil']=FALSE;
$show['dateCreated']=FALSE;
$show['lastModified']=FALSE;
$show['category']=TRUE;
$show['context']=TRUE;
$show['timeframe']=TRUE;
$show['deadline']=TRUE;
$show['repeat']=TRUE;
$show['dateCompleted']=FALSE;
$show['checkbox']=TRUE;

//determine item and parent labels, set a few defaults
    switch ($values['type']) {
        case "m" : $typename="Values"; $parentname=""; $values['ptype']=""; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "v" : $typename="Visions"; $parentname="Value"; $values['ptype']="m"; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "o" : $typename="Roles"; $parentname="Vision"; $values['ptype']="v"; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "g" : $typename="Goals"; $parentname="Role"; $values['ptype']="o"; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $checkchildren=TRUE; break;
        case "p" : $typename="Projects"; $parentname="Goal"; $values['ptype']="g"; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "a" : $typename="Actions"; $parentname="Project"; $values['ptype']="p"; $show['parent']=TRUE; $show['category']=FALSE; $checkchildren=FALSE; break;
        case "w" : $typename="Waiting On"; $parentname="Project"; $values['ptype']="p"; $show['parent']=TRUE; $checkchildren=FALSE; break;
        case "r" : $typename="References"; $parentname="Project"; $values['ptype']="p"; $show['parent']=TRUE; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; $checkchildren=FALSE; break;
        case "i" : $typename="Inbox Items"; $parentname=""; $values['ptype']=""; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['deadline']=FALSE; $show['dateCreated']=TRUE; $show['repeat']=FALSE; $checkchildren=FALSE; break;
        default  : $typename="Items"; $parentname=""; $values['ptype']=""; $checkchildren=FALSE; 
        }

$show['flags']=($checkchildren || (($values['type'])=="a")); // temporary measure; to be made user-configurable later

if ($filter['someday']=="true") {
    $show['dateCreated']=TRUE;
    $show['context']=FALSE;
    $show['repeat']=FALSE;
    $show['deadline']=FALSE;
    $show['timeframe']=FALSE;
	$checkchildren=FALSE; 
    }

if ($filter['tickler']=="true") $show['suppressUntil']=TRUE;

if ($filter['dueonly']=="true") $show['deadline']=TRUE;

if ($filter['repeatingonly']=="true") {
    $show['deadline']=TRUE;
    $show['repeat']=TRUE;
    }

if ($filter['completed']=="completed") {
    $show['suppress']=FALSE;
    $show['suppressUntil']=FALSE;
    $show['dateCreated']=TRUE;
    $show['deadline']=FALSE;
    $show['repeat']=FALSE;
    $show['dateCompleted']=TRUE;
    $show['checkbox']=FALSE;
	$checkchildren=FALSE; 
}

//set query fragments based on filters
$values['childfilterquery'] = "";
$values['parentfilterquery'] = "";
$values['filterquery'] = "";

//type filter
$values['childfilterquery'] = " WHERE ".sqlparts("typefilter",$config,$values);

//filter box filters
if ($values['categoryId'] != NULL && $filter['notcategory']!="true") $values['childfilterquery'] .= " AND ".sqlparts("categoryfilter",$config,$values);
if ($values['categoryId'] != NULL && $filter['notcategory']=="true") $values['childfilterquery'] .= " AND ".sqlparts("notcategoryfilter",$config,$values);

if ($values['contextId'] != NULL && $filter['notspacecontext']!="true") $values['childfilterquery'] .= " AND ".sqlparts("contextfilter",$config,$values);
if ($values['contextId'] != NULL && $filter['notspacecontext']=="true") $values['childfilterquery'] .= " AND ".sqlparts("notcontextfilter",$config,$values);

if ($values['timeframeId'] != NULL && $filter['nottimecontext']!="true") $values['childfilterquery'] .= " AND ".sqlparts("timeframefilter",$config,$values);
if ($values['timeframeId'] != NULL && $filter['nottimecontext']=="true") $values['childfilterquery'] .= " AND ".sqlparts("nottimeframefilter",$config,$values);

if ($filter['completed']=="completed") $values['childfilterquery'] .= " AND ".sqlparts("completeditems",$config,$values);
else $values['childfilterquery'] .= " AND " .sqlparts("pendingitems",$config,$values);

//problem with project somedays vs actions...want an OR, but across subqueries;
if ($filter['someday']=="true") {
    $values['isSomeday']="y";
    $values['childfilterquery'] .= " AND " .sqlparts("issomeday",$config,$values);
    }

else {
    $values['isSomeday']="n";
    $values['childfilterquery'] .= " AND ".sqlparts("issomeday",$config,$values);
//    $values['filterquery'] .= " WHERE " .sqlparts("issomeday-parent",$config,$values);
    }

//problem: need to get all items with suppressed parents(even if child is not marked suppressed), as well as all suppressed items
if ($filter['tickler']=="true") $values['childfilterquery'] .= " AND ".sqlparts("suppresseditems",$config,$values);

else {
    $values['childfilterquery'] .= " AND ".sqlparts("activeitems",$config,$values);
    $values['filterquery'] .= " AND ".sqlparts("activeparents",$config,$values);
    }

if ($filter['repeatingonly']=="true") $values['childfilterquery'] .= " AND " .sqlparts("repeating",$config,$values);

if ($filter['dueonly']=="true") $values['childfilterquery'] .= " AND " .sqlparts("due",$config,$values);

/*
$filter['nextonly']
*/

//Get items for display
$result = query("getitemsandparent",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
?>

<div id="filter">
    <form action="listItems.php?type=<?php echo $values['type']?>" method="post">
        <div class="formrow">
            <label for='categoryId' class='left'>Category:</label>
            <select name="categoryId" title="Filter items by parent category">
            <?php echo $cashtml ?>
            </select>
            <input type="checkbox" name="notcategory" title="Exclude category from list" value="true" <?php if ($filter['notcategory']=="true") echo 'CHECKED'?> />
            <label for='notcategory' class='notfirst'>NOT</label>
            <label for='contextId' class='left'>Context:</label>
            <select name="contextId" title="Filter items by context">
            <?php echo $cshtml ?>
            </select>
            <input type="checkbox" name="notspacecontext" title="Exclude spatial context from list" value="true" <?php if ($filter['notspacecontext']=="true") echo 'CHECKED'?> />
            <label for='notspacecontext' class='notfirst'>NOT</label>
            <label for='timeId' class='left'>Time:</label>
            <select name="timeId" title="Filter items by time context">
            <?php echo $tshtml ?>
            </select>
            <input type="checkbox" name="nottimecontext" title="Exclude time context from list" value="true" <?php if ($filter['nottimecontext']=="true") echo 'CHECKED'?> />
            <label for='nottimecontext' class='notfirst'>NOT</label>
        </div>
        <div class="formrow">
            <label class='left'>Status:</label>
            <input type='radio' name='completed' id='pending' value='pending' class="first" <?php if ($filter['completed']=="pending") echo 'CHECKED'?> title="Show incomplete <?php echo $typename ?>" /><label for='pending' class='right' >Pending</label>
            <input type='radio' name='completed' id='completed' value='completed' class="notfirst" <?php if ($filter['completed']=="completed") echo 'CHECKED'?> title="Show achivements" /><label for='completed' class='right'>Completed</label>
            <label class='left'>Tickler:</label>
            <input type='radio' name='tickler' id='notsuppressed' value='false' class="notfirst" <?php if ($filter['tickler']=="false") echo 'CHECKED'?> title="Show active <?php echo $typename ?>" /><label for='notsuppressed' class='right'>Active</label>
            <input type='radio' name='tickler' id='suppressed' value='true' class="notfirst" <?php if ($filter['tickler']=="true") echo 'CHECKED'?> title="Show tickler <?php echo $typename ?>" /><label for='suppressed' class='right'>Tickler</label>
            <label class='left'>Someday/Maybe:</label>
            <input type='radio' name='someday' id='notsomeday' value='false' class="notfirst" <?php if ($filter['someday']=="false") echo 'CHECKED'?> title="Show active <?php echo $typename ?>" /><label for='notsuppressed' class='right'>Active</label>
            <input type='radio' name='someday' id='someday' value='true' class="notfirst" <?php if ($filter['someday']=="true") echo 'CHECKED'?> title="Show someday/maybe <?php echo $typename ?>" /><label for='suppressed' class='right'>Someday</label>
        </div>
        <div class="formrow">
            <input type="checkbox" name="nextonly" id="nextonly" class="first" value="true" <?php if ($filter['nextonly']=="true") echo 'CHECKED'?> title="Show only Next Actions" /><label for='nextonly' class='right'>Next Actions</label>
            <input type="checkbox" name="dueonly" id="dueonly" class="notfirst" value="true" <?php if ($filter['dueonly']=="true") echo 'CHECKED'?> title="Show only <?php echo $typename ?> with a due date" /><label for='dueonly' class='right'>Due</label>
            <input type="checkbox" name="repeatingonly" id="repeatingonly" class="notfirst" value="true" <?php if ($filter['repeatingonly']=="true") echo 'CHECKED'?> title="Show only repeating <?php echo $typename ?>" /><label for='repeatingonly' class='right'>Repeating</label>
            </div>
            <div class="formbuttons">
            <input type="submit" class="button" value="Filter" name="submit" title="Filter <?php echo $typename ?> by selected criteria" />
        </div>
    </form>
</div>

<?php

//Tickler file header and notes section
if ($filter['tickler']=="true") {
    if ($reminderresult!="-1") {
            echo "<div class='notes'>\n";
            echo '<h2><a href="note.php?&type='.$values['type'].'&referrer=t" Title="Add new reminder">Reminder Notes</a></h2>';
            $tablehtml="";
            foreach ($reminderresult as $row) {
                    $tablehtml .= " <tr>\n";
                    $tablehtml .= "         <td>".$row['date']."</td>\n";
                    $tablehtml .= '         <td><a href = "note.php?noteId='.$row['ticklerId'].'&type='.$values['type'].'&referrer=t" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.htmlspecialchars(stripslashes($row['title']))."</a></td>\n";
                    $tablehtml .= '         <td>'.nl2br(htmlspecialchars(stripslashes($row['note'])))."</td>\n";
                    $tablehtml .= " </tr>\n";
            }

            echo '<table class="datatable sortable" summary="table of reminders" id="remindertable">'."\n";
            echo "  <thead><tr>\n";
            echo "          <td>Reminder</td>\n";
            echo "          <td>Title</td>\n";
            echo "          <td>Note</td>\n";
            echo "  </tr></thead>\n";
            echo $tablehtml;
            echo "</table>\n";
            echo "</div>\n";
        }
   }

        echo '<h2>';
        if ($filter['completed']=="completed") echo 'Completed&nbsp;';
        else echo '<a href="item.php?type='.$values['type'].'" title="Add new '.str_replace("s","",$typename).'">';
            if ($filter['repeatingonly']=="true") echo "Repeating&nbsp;";
            if ($filter['dueonly']=="true") echo "Due&nbsp;";
            if ($filter['someday']=="true") echo "Someday/Maybe&nbsp;";
            if ($filter['nextonly']=="true") echo "Next&nbsp;";
            echo $typename;
            if ($filter['tickler']=="true") echo ' in Tickler File';
            if ($filter['completed']!="true") echo "</a>";
            echo "</h2>\n";



	if ($result!="-1") {
		$tablehtml="";
		$nonext=FALSE;
		$nochildren=FALSE;
		foreach ($result as $row) if (($filter['nextonly']!="true")  || ($key = array_search($row['itemId'],$nextactions))) {
			//filter out all but nextactions if $filter['nextonly']==true
		
			if ($checkchildren) { 
				$values['parentId']=$row['itemId'];
				$nochildren=(query("countchildren",$config,$values)=="-1");
				if ($values['type']=="p") $nonext=(query("selectnextaction",$config,$values)=="-1");
			}
			
			$isNextAction = ($key=array_search($row['itemId'],$nextactions));
			
			$tablehtml .= "	<tr".(($nonext || $nochildren)?' class="noNextAction"':'').">\n";

			//parent title
			if ($show['parent']!=FALSE) 
				$tablehtml .= '		<td><a href = "itemReport.php?itemId='.$row['parentId']
					.'" title="Go to '.htmlspecialchars(stripslashes($row['ptitle']))
					.' '.$parentname.' report">'
					.htmlspecialchars(stripslashes($row['ptitle']))."</a></td>\n";

			if ($show['flags']!=FALSE) {
				// add markers to indicate if this is a next action, or a project with no next actions, or an item with no childern
				$tablehtml .= '<td';
				if ($nonext || $nochildren) {
					$tablehtml .= '><a class="noNextAction" title='
								 .(($nochildren)?'"No children - click to create one"':'"No next action - click to assign one"')
								 .(($nochildren && ($values['type']=="p"))?(' href="item.php?type=a&amp;parentId='):('href="itemReport.php?itemId='))
								 .($values['parentId'].'">!')
								 .(($nochildren)?'!':'')
								 .'</a>';
				} else if ($isNextAction) {
					//if nextaction, add link class and icon in front of action (* for now)
					$tablehtml .= ' class="nextactionlink" title="Next Action">*';
				} else $tablehtml .= '>&nbsp;';
				$tablehtml .= "</td>\n";
			} // end of showing flags

			//item title
			if ($show['title']!=FALSE) {
				$cleaned=htmlspecialchars(stripslashes($row['title']));
				$tablehtml .= '         <td><a href = "itemReport.php?itemId='
								.$row['itemId'].'"><img src="themes/'.$config['theme']
								.'/report.gif" alt="Go to '.$cleaned
								.' report" /></a><a href = "item.php?itemId='.$row['itemId']
								.'"><img src="themes/'.$config['theme']
								.'/edit.gif" alt="Edit '.$cleaned.'" /></a>'
								.'<a '.(($isNextAction)?' class="nextactionlink" ':'').' href = "item'
								.(($row['type']=="a" || $row['type']=="r" || $row['type']=="w" || $row['type']=="i")?'.php':'Report.php')
								.'?itemId='.$row['itemId']
								.'" title="Edit '.$cleaned.'">'.$cleaned."</a></td>\n";
			} // end of showing title

			//item description
			if ($show['description']!=FALSE) $tablehtml .= '		<td>'.nl2br(substr(makeClean($row['description']),0,72))."</td>\n";

			//item desiredOutcome
			if ($show['desiredOutcome']!=FALSE) $tablehtml .= '                <td>'.nl2br(substr(makeClean($row['desiredOutcome']),0,72))."</td>\n";

			//item category
			if ($show['category']!=FALSE) {
				$cleaned = makeClean($row['category']);
				$tablehtml .= '          <td><a href="reportCategory.php#'.urlencode($row['category']).'" title="Go to the  '.$cleaned.' category">'.$cleaned."</a></td>\n";
			}

			//item context name
			if ($show['context']!=FALSE) {
				$cleaned = makeClean($row['cname']);
				$tablehtml .= '		<td><a href = "reportContext.php#'.urlencode($row['cname']).'" title="Go to the  '.$cleaned.' context report">'.$cleaned."</a></td>\n";
			}
			
			//item timeframe name
			if ($show['timeframe']!=FALSE) {
				$cleaned = makeClean($row['timeframe']);
				$tablehtml .= '         <td><a href = "reportTimeContext.php#'.urlencode($row['timeframe']).'" title="Go to '.$cleaned.' time context report">'.$cleaned."</a></td>\n";
			}
			
			//item deadline
			if ($show['deadline']!=FALSE) $tablehtml .= prettyDueDate('td',$row['deadline'],$config['datemask'])."\n";

			//item repeat
			if ($show['repeat']!=FALSE) $tablehtml .= "<td>".((($row['repeat'])=="0")?'&nbsp;':($row['repeat']))."</td>\n";

			//tickler date
			if ($show['suppressUntil']!=FALSE) {
				//Calculate reminder date as # suppress days prior to deadline
				if ($row['suppress']=="y") {
					$dm=(int)substr($row['deadline'],5,2);
					$dd=(int)substr($row['deadline'],8,2);
					$dy=(int)substr($row['deadline'],0,4);
					$remind=mktime(0,0,0,$dm,($dd-(int)$row['suppressUntil']),$dy);
					$reminddate=gmdate("Y-m-d", $remind);
				} else $reminddate="--";
				$tablehtml .= "         <td>".date($config['datemask'],strtotime($reminddate))."</td>\n";
			} // end of showsuppress
						
			//item date Created
			if ($show['dateCreated']!=FALSE) $tablehtml .= '              <td>'.nl2br(htmlspecialchars(stripslashes($row['dateCreated'])))."</td>\n";

			//item last modified
			if ($show['lastModified']!=FALSE) $tablehtml .= '              <td>'.nl2br(htmlspecialchars(stripslashes($row['lastModified'])))."</td>\n";

			//item last modified
			if ($show['dateCompleted']!=FALSE) $tablehtml .= '              <td>'.nl2br(htmlspecialchars(stripslashes($row['dateCompleted'])))."</td>\n";

			//completion checkbox
			if ($show['checkbox']!=FALSE) $tablehtml .= '		<td align="center"><input type="checkbox" title="Complete '.htmlspecialchars(stripslashes($row['title'])).'" name="completedNas[]" value="'.$row['itemId'].'" /></td>'."\n";
			$tablehtml .= "	</tr>\n";

		} // end of: foreach ($result as $row) if (($filter['nextonly']!="true") ...
		
		if ($tablehtml!="") {
//                         if ($show['parent']!=FALSE) echo "<p>Click on ".$parentname." for individual report.</p>\n";
			echo '<form action="processItemUpdate.php" method="post">'."\n";
			echo '<table class="datatable sortable" summary="Table of actions" id="actiontable">'."\n";
			echo "	<thead><tr>\n";
		    if ($show['parent']!=FALSE) echo "		<td>".$parentname."</td>\n";
			if ($show['flags']!=FALSE)echo "          <td>!</td>\n";
			if ($show['title']!=FALSE) echo "		<td>".$typename."</td>\n";
			if ($show['description']!=FALSE) echo "		<td>Description</td>\n";
                        if ($show['desiredOutcome']!=FALSE) echo "         <td>Desired Outcome</td>\n";
                        if ($show['category']!=FALSE)echo "          <td>Category</td>\n";
                        if ($show['context']!=FALSE)echo "          <td>Space Context</td>\n";
			if ($show['timeframe']!=FALSE)echo "		<td>Time Context</td>\n";
			if ($show['deadline']!=FALSE)echo "		<td>Deadline</td>\n";
			if ($show['repeat']!=FALSE)echo "		<td>Repeat</td>\n";
                        if ($show['suppressUntil']!=FALSE) echo "            <td>Reminder Date</td>\n";
                        if ($show['dateCreated']!=FALSE)echo "               <td>Date Created</td>\n";
                        if ($show['lastModified']!=FALSE)echo "               <td>Last Modified</td>\n";
                        if ($show['dateCompleted']!=FALSE)echo "               <td>Date Completed</td>\n";
                        if ($show['checkbox']!=FALSE) echo "           <td>Completed</td>\n";
			echo "	</tr></thead>\n";
			echo $tablehtml;
			echo "</table>\n";
                        //referrer 
                        echo '<input type="hidden" name="referrer" value="i" />'."\n";
                        //filters 
			echo '<input type="hidden" name="type" value="'.$values['type'].'" />'."\n";
			echo '<input type="hidden" name="timeId" value="'.$values['timeframeId'].'" />'."\n";
                        echo '<input type="hidden" name="contextId" value="'.$values['contextId'].'" />'."\n";
                        echo '<input type="hidden" name="categoryId" value="'.$values['categoryId'].'" />'."\n";
                        echo '<input type="hidden" name="notcategory" value="'.$filter['notcategory'].'" />'."\n";
                        echo '<input type="hidden" name="nottimecontext" value="'.$filter['nottimecontext'].'" />'."\n";
                        echo '<input type="hidden" name="notspacecontext" value="'.$filter['notspacecontext'].'" />'."\n";
                        echo '<input type="hidden" name="tickler" value="'.$filter['tickler'].'" />'."\n";
                        echo '<input type="hidden" name="someday" value="'.$filter['someday'].'" />'."\n";
                        echo '<input type="hidden" name="completed" value="'.$filter['completed'].'" />'."\n";
                        echo '<input type="hidden" name="nextonly" value="'.$filter['nextonly'].'" />'."\n";
                        echo '<input type="hidden" name="repeatingonly" value="'.$filter['repeatingonly'].'" />'."\n";
                        echo '<input type="hidden" name="dueonly" value="'.$filter['dueonly'].'" />'."\n";
                        echo '<input type="submit" class="button" value="Complete '.$typename.'" name="submit" />'."\n";
			echo "</form>\n";
		}

        elseif($filter['completed']!="completed" && $values['type']!="t") {
                $message="You have no ".$typename." remaining.";
                $prompt="Would you like to create a new ".str_replace("s","",$typename)."?";
                $yeslink="item.php?type=".$values['type'];
                nothingFound($message,$prompt,$yeslink);
        }


        elseif($values['type']=="t") {
                $message="None";
                nothingFound($message);
        }

} // end of: if($result!="-1")

        elseif($filter['completed']!="completed" && $values['type']!="t") {
		$message="You have no ".$typename." remaining.";
		$prompt="Would you like to create a new ".str_replace("s","",$typename)."?";
		$yeslink="item.php?type=".$values['type'];
		nothingFound($message,$prompt,$yeslink);
	}


        elseif($values['type']=="t") {
                $message="None";
                nothingFound($message);
        }

	include_once('footer.php');
?>
