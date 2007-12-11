<?php

//INCLUDES
include_once('header.php');

//page display options array--- can put defaults in preferences table/config/session and load into $show array as defaults...
$show=array();

//GET URL VARIABLES
$values = array();
$filter = array();

if (isset($_GET['type'])) $values['type']=$_GET["type"]{0};
else $values['type']="p";

if ($_GET['categoryId']>0) $values['categoryId']=(int) $_GET['categoryId'];
    else $values['categoryId']=(int) $_POST['categoryId'];

if (isset($_GET['notcategory'])) $filter['notcategory']= $_GET['notcategory'];
    else $filter['notcategory']=$_POST['notcategory'];

if ($_GET['contextId']>0) $values['contextId']=(int) $_GET['contextId'];
    else $values['contextId']=(int) $_POST['contextId'];

if (isset($_GET['notspacecontext'])) $filter['notspacecontext']= $_GET['notspacecontext'];
    else $filter['notspacecontext']=$_POST['notspacecontext'];

if ($_GET['timeframeId']>0) $values['timeframeId']=(int) $_GET['timeframeId'];
    else $values['timeframeId']=(int) $_POST['timeframeId'];

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
$values['timefilterquery'] = ($config['useTypesForTimeContexts'])?" WHERE ".sqlparts("timetype",$config,$values):'';

//create filter selectboxes
$cashtml=categoryselectbox($config,$values,$sort);
$cshtml=contextselectbox($config,$values,$sort);
$tshtml=timecontextselectbox($config,$values,$sort);

//Select notes
if ($filter['tickler']=="true") {
    $values['filterquery'] = "";
    $reminderresult = query("getnotes",$config,$values,$sort);
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
        case "m" : $typename="Values"; $parentname=""; $values['ptype']=""; $values['ctype']="v"; $recurse=5; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "v" : $typename="Visions"; $parentname="Value"; $values['ptype']="m"; $values['ctype']="g"; $recurse=4; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "g" : $typename="Goals"; $parentname="Role"; $values['ptype']="o"; $values['ctype']="o"; $recurse=3; $show['desiredOutcome']=TRUE; $show['context']=FALSE; break;
        case "o" : $typename="Roles"; $parentname="Vision"; $values['ptype']="v"; $values['ctype']="p"; $recurse=2; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "p" : $typename="Projects"; $parentname="Goal"; $values['ptype']="g"; $values['ctype']="a"; $recurse=1; $show['context']=FALSE; $show['timeframe']=FALSE; break;
        case "a" : $typename="Actions"; $parentname="Project"; $values['ptype']="p"; $show['parent']=TRUE; $values['ctype']=""; $recurse=0; $show['category']=FALSE; break;
        case "w" : $typename="Waiting On"; $parentname="Project"; $values['ptype']="p"; $show['parent']=TRUE; $values['ctype']=""; $recurse=0; break;
        case "r" : $typename="References"; $parentname="Project"; $values['ptype']="p"; $show['parent']=TRUE; $values['ctype']=""; $recurse=0; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['checkbox']=FALSE; $show['repeat']=FALSE; $show['dateCreated']=TRUE; break;
        case "i" : $typename="Inbox Items"; $parentname=""; $values['ptype']=""; $values['ctype']=""; $recurse=0; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['deadline']=FALSE; $show['dateCreated']=TRUE; $show['repeat']=FALSE; break;
        default  : $typename="Items"; $parentname=""; $values['ptype']=""; $values['ctype']=""; $recurse=0;
        }


if ($filter['someday']=="true") {
    $show['dateCreated']=TRUE;
    $show['context']=FALSE;
    $show['repeat']=FALSE;
    $show['deadline']=FALSE;
    $show['timeframe']=FALSE;
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
}

//set query fragments based on filters
//$values['childfilterquery'] = "";
//$values['parentfilterquery'] = "";
$values['filterquery'] = "";
$categoryfilter = "";
$contextfilter = "";
$timefilter = "";
$completedfilter = "";
$somedayfilter = "";
$ticklerfilter = "";
$repeatfilter = "";
$duefilter = "";

//filter box filters
if ($values['categoryId'] != NULL && $filter['notcategory']!="true") $categoryfilter = " AND ".sqlparts("categoryfilter",$config,$values);
if ($values['categoryId'] != NULL && $filter['notcategory']=="true") $categoryfilter = " AND ".sqlparts("notcategoryfilter",$config,$values);

if ($values['contextId'] != NULL && $filter['notspacecontext']!="true") $contextfilter = " AND ".sqlparts("contextfilter",$config,$values);
if ($values['contextId'] != NULL && $filter['notspacecontext']=="true") $contextfilter = " AND ".sqlparts("notcontextfilter",$config,$values);

if ($values['timeframeId'] != NULL && $filter['nottimecontext']!="true") $timefilter = " AND ".sqlparts("timeframefilter",$config,$values);
if ($values['timeframeId'] != NULL && $filter['nottimecontext']=="true") $timefilter = " AND ".sqlparts("nottimeframefilter",$config,$values);

if ($filter['completed']=="completed") $completedfilters = " AND ".sqlparts("completeditems",$config,$values);
else $completedfilter = " AND " .sqlparts("pendingitems",$config,$values);

//problem with project somedays vs actions...want an OR, but across subqueries;
if ($filter['someday']=="true") {
    $values['isSomeday']="y";
    $somedayfilter = " AND " .sqlparts("issomeday",$config,$values);
    }

else {
    $values['isSomeday']="n";
    $somedayfilter = " AND " .sqlparts("issomeday",$config,$values);
    }

//problem: need to get all items with suppressed parents(even if child is not marked suppressed), as well as all suppressed items
if ($filter['tickler']=="true") $ticklerfilter = " AND ".sqlparts("suppresseditems",$config,$values);

else {
    $ticklerfilter = " AND ".sqlparts("activeitems",$config,$values);
    }

if ($filter['repeatingonly']=="true") $repeatfilter = " AND " .sqlparts("repeating",$config,$values);

if ($filter['dueonly']=="true") $duefilter = " AND " .sqlparts("due",$config,$values);

/*
$filter['nextonly']
*/

//create filter query string, and add type filter
$values['filterquery'] = " WHERE ".sqlparts("typefilter",$config,$values);
$values['filterquery'] .= $categoryfilter.$contextfilter.$timefilter.$completedfilter.$somedayfilter.$ticklerfilter.$repeatfilter.$duefilter;
$childfilter = $completedfilter.$somedayfilter.$ticklerfilter.$repeatfilter.$duefilter;

//Get items for display
$result = query("getitems",$config,$values,$sort);

//PAGE DISPLAY CODE
?>

<div id="filter">
    <form action="management.php?type=<?php echo $values['type']?>" method="post">
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
            <label for='timeframeId' class='left'>Time:</label>
            <select name="timeframeId" title="Filter items by time context">
            <?php echo $tshtml ?>
            </select>
            <input type="checkbox" name="nottimecontext" title="Exclude time context from list" value="true" <?php if ($filter['nottimecontext']=="true") echo 'CHECKED'?> />
            <label for='nottimecontext' class='notfirst'>NOT</label>
        </div>
        <div class="formrow">
            <label class='left'>Status:</label>
            <input type='radio' name='completed' id='pending' value='pending' class="first" <?php if ($filter['completed']=="pending") echo 'CHECKED'?> title="Show incomplete <?php echo $typename ?>" /><label for='pending' class='right' >Pending</label>
            <input type='radio' name='completed' id='completed' value='completed' class="notfirst" <?php if ($filter['completed']=="completed") echo 'CHECKED'?> title="Show achievements" /><label for='completed' class='right'>Completed</label>
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
                    $tablehtml .= '         <td><a href = "note.php?noteId='.$row['ticklerId'].'&type='.$values['type'].'&referrer=t" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.htmlspecialchars(stripslashes($row['title']))."</td>\n";
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
            foreach ($result as $item) {
                $html .= '<h3><a href = "itemReport.php?itemId='.$item['itemId'].'"><img src="themes/'.$config['theme'].'/report.gif" alt="Go to '.htmlspecialchars(stripslashes($item['title'])).' report" /></a><a href = "item.php?itemId='.$item['itemId'].'"><img src="themes/'.$config['theme'].'/edit.gif" alt="Edit '.htmlspecialchars(stripslashes($item['title'])).'" /></a>'.$item['title']."</h3>\n";
                $values['filterquery'] = $childfilter;
                $values['parentId']=$item['itemId'];
                $level2 = query("getchildren",$config,$values,$sort);
                if ($level2!="-1") {
                    $html .= '<ul>'."\n";
                    foreach ($level2 as $child2) {
                        $html .= '<li><a href = "itemReport.php?itemId='.$child2['itemId'].'"><img src="themes/'.$config['theme'].'/report.gif" alt="Go to '.htmlspecialchars(stripslashes($child2['title'])).' report" /></a><a href = "item.php?itemId='.$child2['itemId'].'"><img src="themes/'.$config['theme'].'/edit.gif" alt="Edit '.htmlspecialchars(stripslashes($child2['title'])).'" /></a>'.$child2['title']."\n";
                        $values['filterquery'] = $childfilter;
                        $values['parentId']=$child2['itemId'];
                        $level3 = query("getchildren",$config,$values,$sort);
                        if ($level3!="-1") {
                            $html .= '<ul>'."\n";
                            foreach ($level3 as $child3) {
                                $html .= '<li><a href = "itemReport.php?itemId='.$child3['itemId'].'"><img src="themes/'.$config['theme'].'/report.gif" alt="Go to '.htmlspecialchars(stripslashes($child3['title'])).' report" /></a><a href = "item.php?itemId='.$child3['itemId'].'"><img src="themes/'.$config['theme'].'/edit.gif" alt="Edit '.htmlspecialchars(stripslashes($child3['title'])).'" /></a>'.$child3['title']."\n";
                                $values['filterquery'] = $childfilter;
                                $values['parentId']=$child3['itemId'];
                                $level4 = query("getchildren",$config,$values,$sort);
                                if ($level4!="-1") {
                                    $html .= '<ul>'."\n";
                                    foreach ($level4 as $child4) {
                                        $html .= '<li><a href = "itemReport.php?itemId='.$child4['itemId'].'"><img src="themes/'.$config['theme'].'/report.gif" alt="Go to '.htmlspecialchars(stripslashes($child4['title'])).' report" /></a><a href = "item.php?itemId='.$child4['itemId'].'"><img src="themes/'.$config['theme'].'/edit.gif" alt="Edit '.htmlspecialchars(stripslashes($child4['title'])).'" /></a>'.$child4['title']."\n";
                                        $values['filterquery'] = $childfilter;
                                        $values['parentId']=$child4['itemId'];
                                        $level5 = query("getchildren",$config,$values,$sort);
                                        if ($level5!="-1") {
                                            $html .= '<ul>'."\n";
                                            foreach ($level5 as $child5) {
                                                $html .= '<li><a href = "itemReport.php?itemId='.$child5['itemId'].'"><img src="themes/'.$config['theme'].'/report.gif" alt="Go to '.htmlspecialchars(stripslashes($child5['title'])).' report" /></a><a href = "item.php?itemId='.$child5['itemId'].'"><img src="themes/'.$config['theme'].'/edit.gif" alt="Edit '.htmlspecialchars(stripslashes($child5['title'])).'" /></a>'.$child5['title']."\n";
                                                $values['filterquery'] = $childfilter;
                                                $values['parentId']=$child5['itemId'];
                                                $level6 = query("getchildren",$config,$values,$sort);
                                                if ($level6!="-1") {
                                                    $html .= '<ul>'."\n";
                                                    foreach ($level6 as $child6) {
                                                        $html .= '<li><a href = "itemReport.php?itemId='.$child6['itemId'].'"><img src="themes/'.$config['theme'].'/report.gif" alt="Go to '.htmlspecialchars(stripslashes($child6['title'])).' report" /></a><a href = "item.php?itemId='.$child6['itemId'].'"><img src="themes/'.$config['theme'].'/edit.gif" alt="Edit '.htmlspecialchars(stripslashes($child6['title'])).'" /></a>'.$child6['title']."</li>\n";
                                                        }
                                                    $html .= '</ul>'."\n";
                                                    }
												$html .= '</li>'."\n";
                                                }
                                            $html .= '</ul>'."\n";
                                            }
										$html .= '</li>'."\n";
                                        }
                                    $html .= '</ul>'."\n";
                                    }
								$html .= '</li>'."\n";
                                }
                            $html .= '</ul>'."\n";
                            }
						$html .= '</li>'."\n";
                        }
                    $html .= '</ul>'."\n";
                    }
                }
            }


echo $html;

        include_once('footer.php');
?>
