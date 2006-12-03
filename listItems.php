<?php

//INCLUDES
include_once('header.php');

//GET URL VARIABLES
$values = array();
$type=$_GET["type"]{0};
if ($_GET['categoryId']>0) $values['categoryId']=(int) $_GET['categoryId'];
else $values['categoryId']=(int) $_POST['categoryId'];
if ($_GET['contextId']>0) $values['contextId']=(int) $_GET['contextId'];
else $values['contextId']=(int) $_POST['contextId'];
if ($_GET['timeId']>0) $values['timeframeId']=(int) $_GET['timeId'];
else $values['timeframeId']=(int) $_POST['timeId'];

$values['notspacecontext']=$_POST['notspacecontext'];
$values['nottimecontext']=$_POST['nottimecontext'];
$values['notcategory']=$_POST['notcategory'];

if ($type=='s') $values['isSomeday']='y';
else $values['isSomeday']='n';

//Check Session Variables
//If we have contextId from a new filter, change Session value
$contextId=$values['contextId'];
if ($contextId>=0) $_SESSION['contextId']=$contextId;
else $values['contextId']=$_SESSION['contextId'];

//If we have categoryId from a new filter, change Session value
$categoryId=$values['categoryId'];
if ($categoryId>=0) $_SESSION['categoryId']=$categoryId;
else $values['categoryId']=$_SESSION['categoryId'];

//page display options array--- can put defaults in preferences table/config/session and load into $show array as defaults...
$show=array();

/*

Other fields to consider...
parentdetails
    description
    desiredOutcome
    isSomeday
    suppressUntil
    dateCreated
    lastModified
    category
    space context
    time context
    deadline / due
    neglected
*/

/*

$filter=array();
type
parent type
issomeday //should handle like completed-- not as seperate type
tickler vs active vs completed //?  completed overlays all item types ; remove type=c option from referrers
repeats/doesnotrepeat
space context
time context
category
deadline
due today
neglected
*/

/*
$dynamicsort=array();
on column header
*/



//SQL CODE

//create filters for selectboxes
if ($type=="g") $values['timefilterquery'] = sqlparts("timegoals",$config,$values);
else $values['timefilterquery'] = sqlparts("timeitems",$config,$values);

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
if ($type=="t") $reminderresult = query("getnotes",$config,$values,$options,$sort);


//Select items

//set query fragments based on filters  : parent and child filters!
//add other filter possibilities


$loop=array();
if ($type=="t") {
    $loop = array("m","v","g","o","p","a","w","r");
    $suppressed=TRUE;
    }

else {
    $loop = array($type);
    $suppresed=FALSE;
    }


//Tickler file header and notes section
if ($type=="t") {
    echo "<h1>Tickler File</h1>\n";
    echo "<div class='tickler'>\n";
    echo '<h4>Today is '.date("l, F jS, Y").'. (Week '.date("w").'/52 & Day '.date("z").'/'.(365+date("L")).")</h4>\n";
    echo '<p>To add an item to the tickler file:
            <ul>
                    <li>Add a new item (<a href="item.php?type=a" title="Add new action">action</a>,
                            <a href="item.php?type=w" title="Add new waitingOn">waiting</a>,
                            <a href="item.php?type=r" title="Add new reference">reference</a>), 
                            <a href="note.php?referrer=t" title="Add new reminder note">note</a>, 
                            <a href="item.php?type=p">project</a>, or <a href="item.php?type=s">someday/maybe</a> as appropriate,</li>
                    <li>Select the tickler option, and fill in the details as desired.</li>
            </ul></p>';
    echo "</div>\n";
    if ($reminderresult!="-1") {
            echo "<div class='notes'>\n";
            echo '<h2><a href="note.php?&type='.$type.'&referrer=t" Title="Add new reminder">Reminder Notes</a></h2>';
            $tablehtml="";
            foreach ($reminderresult as $row) {
                    $tablehtml .= " <tr>\n";
                    $tablehtml .= "         <td>".$row['date']."</td>\n";
                    $tablehtml .= '         <td><a href = "note.php?noteId='.$row['ticklerId'].'&type='.$type.'&referrer=t" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.htmlspecialchars(stripslashes($row['title']))."</td>\n";
                    $tablehtml .= '         <td>'.nl2br(htmlspecialchars(stripslashes($row['note'])))."</td>\n";
                    $tablehtml .= " </tr>\n";
            }

            echo "<table class='datatable'>\n";
            echo "  <thead>\n";
            echo "          <td>Reminder</td>\n";
            echo "          <td>Title</td>\n";
            echo "          <td>Note</td>\n";
            echo "  </thead>\n";
            echo $tablehtml;
            echo "</table>\n";
            echo "</div>\n";
        }
    }
    
$filterdisplay=0;
foreach ($loop as $values['type']) {

//reset default table column display options (kludge-- needs to be divided into multidimensional array for each table type
$show['parent']=TRUE;
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
$show['checkbox']=TRUE;


//determine item and parent labels, set a few defaults
    switch ($values['type']) {
        case "m" : $typename="Values"; $parentname=""; $values['ptype']=""; $show['parent']=FALSE; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "v" : $typename="Visions"; $parentname="Value"; $values['ptype']="m"; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "g" : $typename="Goals"; $parentname="Vision"; $values['ptype']="v"; $show['desiredOutcome']=TRUE; $show['context']=FALSE; break;
        case "o" : $typename="Roles"; $parentname="Goal"; $values['ptype']="g"; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "p" : $typename="Projects"; $parentname="Role"; $values['ptype']="o"; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "s" : $typename="Someday/Maybe"; $parentname="Role"; $values['ptype']="o"; $values['type']="p"; $show['context']=FALSE; $show['repeat']=FALSE; $show['deadline']=FALSE; $show['timeframe']=FALSE; $show['dateCreated']=TRUE; break;
        case "a" : $typename="Actions"; $parentname="Project"; $values['ptype']="p"; $show['category']=FALSE; break;
        case "n" : $typename="Next Actions"; $parentname="Project";$values['ptype']="p"; $values['type']="a"; $display="nextonly"; $show['category']=FALSE; break;
        case "w" : $typename="Waiting On"; $parentname="Project"; $values['ptype']="p"; break;
        case "r" : $typename="References"; $parentname="Project"; $values['ptype']="p"; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; break;
        case "i" : $typename="Inbox Items"; $parentname=""; $values['ptype']=""; $show['parent']=FALSE; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['deadline']=FALSE; $show['dateCreated']=TRUE; $show['repeat']=FALSE; break;
        default  : $typename="Items"; $parentname=""; $values['ptype']="";
        }

if ($suppressed==TRUE) $show['suppressUntil']=TRUE;

//make generic based on type/someday, etc.
$values['parentfilterquery'] = sqlparts("ptypefilter-w",$config,$values);
//$values['parentfilterquery'] .= sqlparts("issomeday",$config,$values);
$values['parentfilterquery'] .= sqlparts("activeitems",$config,$values);


$values['childfilterquery'] = sqlparts("typefilter-w",$config,$values);
$values['childfilterquery'] .= sqlparts("issomeday",$config,$values);  //?

if ($suppressed==TRUE) $values ['childfilterquery'] .= sqlparts("suppresseditems",$config,$values);
else $values['childfilterquery'] .= sqlparts("activeitems",$config,$values);


if ($values['categoryId'] != NULL && $values['notcategory']!="true") $values['childfilterquery'] .= sqlparts("categoryfilter",$config,$values);
if ($values['categoryId'] != NULL && $values['notcategory']=="true") $values['childfilterquery'] .= sqlparts("notcategoryfilter",$config,$values);

if ($values['contextId'] != NULL && $values['notspacecontext']!="true") $values['childfilterquery'] .= sqlparts("contextfilter",$config,$values);
if ($values['contextId'] != NULL && $values['notspacecontext']=="true") $values['childfilterquery'] .= sqlparts("notcontextfilter",$config,$values);

if ($values['timeframeId'] != NULL && $values['nottimecontext']!="true") $values['childfilterquery'] .= sqlparts("timeframefilter",$config,$values);
if ($values['timeframeId'] != NULL && $values['nottimecontext']=="true") $values['childfilterquery'] .= sqlparts("nottimeframefilter",$config,$values);

//Get items for display
$result = query("getitemsandparent",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
    if ($filterdisplay<1) {
        if ($display=="nextonly" && $values['type']=="a") $urltype="n";
        else $urltype=$values['type'];
        echo '<div id="filter">'."\n";
	echo '<form action="listItems.php?type='.$urltype.'" method="post">'."\n";
	echo "<p>Category:&nbsp;\n";
	echo '<select name="categoryId" title="Filter items by parent category">'."\n";
	echo '	<option value="0">All</option>'."\n";
	echo $cashtml;
	echo "</select>\n";
        echo '<input type="checkbox" name="notcategory" title="Exclude category from list" value="true"';
        if ($values['notcategory']=="true") echo " CHECKED";
        echo '> NOT'."\n";
	echo "&nbsp;&nbsp;&nbsp;\nContext:&nbsp;\n";
	echo '<select name="contextId" title="Filter items by context">'."\n";
	echo '	<option value="">All</option>'."\n";
	echo $cshtml;
	echo "</select>\n";
        echo '<input type="checkbox" name="notspacecontext" title="Exclude spatial context from list" value="true"';
        if ($values['notspacecontext']=="true") echo " CHECKED";
        echo '> NOT'."\n";
        echo "&nbsp;&nbsp;&nbsp;\nTime:&nbsp;\n";
	echo '<select name="timeId" title="Filter items by time context">'."\n";
	echo '	<option value="">All</option>'."\n";
	echo $tshtml;
	echo "</select>\n";
        echo '<input type="checkbox" name="nottimecontext" title="Exclude time context from list" value="true"';
        if ($values['nottimecontext']=="true") echo " CHECKED";
        echo '> NOT'."\n";
        echo '&nbsp;&nbsp;&nbsp;<input type="submit" class="button" value="Filter" name="submit" title="Filter '.$typename.' by category and/or contexts">'."\n";
	echo "</p>\n";
	echo "</form>\n\n";
        echo "</div>\n";
        }
        $filterdisplay++;

        echo '<h2>';
        if ($values['completed']=="y") echo 'Completed&nbsp;'.$typename."</h2>\n";
        else echo '<a href="item.php?type='.$values['type'].'" title="Add new '.str_replace("s","",$typename).'">'.$typename."</a></h2>\n";


	if ($result!="-1") {
                $tablehtml="";
                foreach ($result as $row) {
                    $showme="y";
                    //filter out all but nextactions if $display=nextonly
                    if (($display=='nextonly')  && !($key = array_search($row['itemId'],$nextactions))) $showme="n";
                    if($showme=="y") {
                        $tablehtml .= "	<tr>\n";

                        //parent title
                            if ($show['parent']!=FALSE) {
                                $tablehtml .= '		<td><a href = "itemReport.php?itemId='.$row['parentId'].'" title="Go to '.htmlspecialchars(stripslashes($row['ptitle'])).' '.$parentname.' report">';
                                if ($nonext=="true" && $values['completed']!="y") echo '<span class="noNextAction" title="No next action defined!">!</span>';
                                $tablehtml .= htmlspecialchars(stripslashes($row['ptitle']))."</a></td>\n";
                                }

                        //item title
                        if ($show['title']!=FALSE && ($row['type']=="a" || $row['type']=="r" || $row['type']=="w" || $row['type']=="i")) $tablehtml .= '         <td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">';

                        elseif ($show['title']!=FALSE) $tablehtml .= '         <td><a href = "itemReport.php?itemId='.$row['itemId'].'" title="Go to '.htmlspecialchars(stripslashes($row['title'])).' report">';

                        //if nextaction, add icon in front of action (* for now)
                        if ($key = array_search($row['itemId'],$nextactions) && ($show['title']!=FALSE)) $tablehtml .= '*&nbsp;';

                        if ($show['title']!=FALSE) $tablehtml .=htmlspecialchars(stripslashes($row['title']))."</td>\n";

                        //item description
                        if ($show['description']!=FALSE) $tablehtml .= '		<td>'.nl2br(substr(htmlspecialchars(stripslashes($row['description'])),0,72))."</td>\n";

                        //item desiredOutcome
                        if ($show['desiredOutcome']!=FALSE) $tablehtml .= '                <td>'.nl2br(substr(htmlspecialchars(stripslashes($row['desiredOutcome'])),0,72))."</td>\n";

                        //item category
                        if ($show['category']!=FALSE) $tablehtml .= '          <td><a href="reportCategory.php#'.$row['category'].'" title="Go to the  '.htmlspecialchars(stripslashes($row['category'])).' category">'.htmlspecialchars(stripslashes($row['category']))."</a></td>\n";

                        //item context name
                        if ($show['context']!=FALSE) $tablehtml .= '		<td><a href = "reportContext.php#'.$row['cname'].'" title="Go to the  '.htmlspecialchars(stripslashes($row['cname'])).' context report">'.htmlspecialchars(stripslashes($row['cname']))."</td>\n";
                        
                        //item timeframe name
                        if ($show['timeframe']!=FALSE) $tablehtml .= '         <td><a href = "reportTimeContext.php#'.$row['timeframe'].'" title="Go to '.htmlspecialchars(stripslashes($row['timeframe'])).' time context report">'.htmlspecialchars(stripslashes($row['timeframe']))."</td>\n";
                        
                        //item deadline
                        if ($show['deadline']!=FALSE) {
                            $tablehtml .= "		<td>";
                            if(($row['deadline']) == "0000-00-00" || $row['deadline'] ==NULL) $tablehtml .= "&nbsp;";
                            elseif(($row['deadline']) < date("Y-m-d")) $tablehtml .= '<font color="red"><strong title="Item overdue">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';  //highlight overdue actions
                            elseif(($row['deadline']) == date("Y-m-d")) $tablehtml .= '<font color="green"><strong title="Item due today">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>'; //highlight actions due today
                            else $tablehtml .= date("D M j, Y",strtotime($row['deadline']));
                            $tablehtml .= "</td>\n";
                            }

                        //item repeat
                        if ($show['repeat']!=FALSE) {
                            if ($row['repeat']=="0") $tablehtml .= "		<td></td>\n";
                            else $tablehtml .= "		<td>".$row['repeat']."</td>\n";
                            }

                        //tickler date
                        if ($show['suppressUntil']!=FALSE) {
                                    //Calculate reminder date as # suppress days prior to deadline
                                    if ($row['suppress']=="y") {
                                    $dm=(int)substr($row['deadline'],5,2);
                                    $dd=(int)substr($row['deadline'],8,2);
                                    $dy=(int)substr($row['deadline'],0,4);
                                    $remind=mktime(0,0,0,$dm,($dd-(int)$row['suppressUntil']),$dy);
                                    $reminddate=gmdate("Y-m-d", $remind);
                                    }
                                    else $reminddate="--";
                                    $tablehtml .= "         <td>".date("D M j, Y",strtotime($reminddate))."</td>\n";
                                    }
                                    
                        //item date Created
                        if ($show['dateCreated']!=FALSE) $tablehtml .= '              <td>'.nl2br(htmlspecialchars(stripslashes($row['dateCreated'])))."</td>\n";

                        //item last modified
                        if ($show['lastModified']!=FALSE) $tablehtml .= '              <td>'.nl2br(htmlspecialchars(stripslashes($row['lastModified'])))."</td>\n";

                        //completion checkbox
                        if ($values['completed']!="y" && ($show['checkbox']!=FALSE)) $tablehtml .= '		<td align="center"><input type="checkbox" align="center" title="Complete '.htmlspecialchars(stripslashes($row['title'])).'" name="completedNas[]" value="'.$row['itemId'].'" /></td>'."\n";
                        $tablehtml .= "	</tr>\n";
                        }
                    }

		if ($tablehtml!="") {
//                         if ($show['parent']!=FALSE) echo "<p>Click on ".$parentname." for individual report.</p>\n";
			echo '<form action="processItemUpdate.php" method="post">'."\n";
			echo "<table class='datatable'>\n";
			echo "	<thead>\n";
		        if ($show['parent']!=FALSE) echo "		<td>".$parentname."</td>\n";
			if ($show['title']!=FALSE) echo "		<td>".$typename."</td>\n";
			if ($show['description']!=FALSE) echo "		<td>Description</td>\n";
                        if ($show['desiredOutcome']!=FALSE) echo "         <td>Desired Outcome</td>\n";
                        if ($show['category']!=FALSE)echo "          <td>Category</td>\n";
                        if ($show['context']!=FALSE)echo "          <td>Space Context</td>\n";
			if ($show['timeframe']!=FALSE)echo "		<td>Time Context</td>\n";
			if ($show['deadline']!=FALSE)echo "		<td>Deadline</td>\n";
			if ($show['repeat']!=FALSE)echo "		<td>Repeat</td>\n";
                        if ($show['suppressUntil']!=FALSE) echo "            <td>Reminder Date</td>\n";
                        if ($show['dateCreated']!=FALSE)echo "               <td>dateCreated</td>\n";
                        if ($show['lastModified']!=FALSE)echo "               <td>lastModified</td>\n";
                        if ($show['checkbox']!=FALSE) echo "           <td>Completed</td>\n";
			echo "	</thead>\n";
			echo $tablehtml;
			echo "</table>\n";
			echo '<input type="hidden" name="type" value="'.$values['type'].'" />'."\n";
			echo '<input type="hidden" name="timeId" value="'.$values['timeframeId'].'" />'."\n";
                        echo '<input type="hidden" name="contextId" value="'.$values['contextId'].'" />'."\n";
                        echo '<input type="hidden" name="categoryId" value="'.$values['categoryId'].'" />'."\n";
			echo '<input type="hidden" name="referrer" value="i" />'."\n";
			echo '<input type="submit" class="button" value="Complete '.$typename.'" name="submit">'."\n";
			echo "</form>\n";
		}
	}


        elseif($values['completed']!="y" && $type!="t") {
		$message="You have no ".$typename." remaining.";
		$prompt="Would you like to create a new ".str_replace("s","",$typename)."?";
		$yeslink="item.php?type=".$values['type'];
		nothingFound($message,$prompt,$yeslink);
	}
        
        elseif($type="t") {
                $message="None";
                nothingFound($message);
        }

}

	include_once('footer.php');
?>
