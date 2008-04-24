<?php
include_once('header.php');

$values=array();
$values['itemId'] = (int) $_GET['itemId'];

//Get item details
$values['childfilterquery']=' WHERE '.sqlparts('singleitem',$config,$values);
$values['filterquery']='';
$values['extravarsfilterquery'] ='';
$result = query("getitemsandparent",$config,$values,$sort);
if (!$result) {
    echo ("<p class='error'>Failed to find item {$values['itemId']}</p>");
    include_once('footer.php');
    die;
}
$item=$result[0];
$values['isSomeday']=($item['isSomeday']=="y")?'y':'n';
$values['type']=$item['type'];

//Find previous and next projects
if (isset($_SESSION['idlist-'.$item['type']])) {
    $ndx=$_SESSION['idlist-'.$item['type']];
    unset($result);
} else {
    $values['filterquery']  = " WHERE ".sqlparts("typefilter",$config,$values);
    $values['filterquery'] .= " AND ".sqlparts("activeitems",$config,$values);
    $values['filterquery'] .= " AND ".sqlparts("pendingitems",$config,$values);
    $values['filterquery'] .= " AND ".sqlparts("issomeday",$config,$values);
    $result = query("getitems",$config,$values,$sort);
    $c=0;
    $ndx=array();
    if ($result) {
        foreach ($result as $row) $ndx[]=$row['itemId'];
        $_SESSION['idlist-'.$item['type']]=$ndx;
    }
}

$cnt=count($ndx);
if($cnt>1) {
    $key=array_search($values['itemId'],$ndx);
    if ($key===false) {
        $next=0;
        $prev=$cnt-1;
    } else {
        if ($key==0)
            $prev=$cnt-1;
        else
            $prev=$key-1;
            
        if ($key==$cnt-1)
            $next=0;
        else
            $next=$key+1;
    }
    $previousId=$ndx[$prev];
    $nextId    =$ndx[$next];
    if (isset($result)) {
        $previoustitle=$result[$prev]['title'];
        $nexttitle    =$result[$next]['title'];
    } else {
        $previtem = query("selectitemtitle",$config,array('itemId'=>$previousId),$sort);
        $previoustitle=$previtem[0]['title'];
        $nextitem = query("selectitemtitle",$config,array('itemId'=>$nextId),    $sort);
        $nexttitle    =$nextitem[0]['title'];
    }
}
 
//PAGE DISPLAY AREA

//set item labels
$typename=array();
$typename=getTypes();

$childtype=array();  //I don't like this... but it's the best solution at the moment...

$childtype=getChildType($item['type']);
$afterTypeChange="itemReport.php?itemId={$values['itemId']}";
?>
<h1><span class='noprint hoverbox'><?php
if(isset($previousId))
    echo "<a href='itemReport.php?itemId=$previousId' title='Previous: ",makeclean($previoustitle),"'> &lt; </a> \n";
    
echo " <a href='item.php?itemId={$values['itemId']}'>"
        ," <img src='themes/{$config['theme']}/edit.gif' alt='Edit ' title='Edit' /> "
     ,"</a> ";

if(isset($nextId))
    echo " <a href='itemReport.php?itemId=$nextId' title='Next: ",makeclean($nexttitle),"'> &gt; </a> \n";

echo "</span>",$typename[$item['type']]." Report: "
    ,makeclean($item['title'])
    ,(($item['isSomeday']=="y")?" (Someday) ":"");
?></h1>
<?php
if ($item['type']==='i')
    echo "<div class='editbar'>"
        ,"[<a href='assignType.php?itemId={$values['itemId']}&amp;referrer=$afterTypeChange'>Set type</a>] \n"
        ,"</div>";
?>
<table id='report' summary='item attributes'><tbody>
<?php
//Item details
if ($item['description']) echo "<tr><th>Description:</th><td>",nl2br(escapeChars($item['description'])),"</td></tr>\n";
if ($item['desiredOutcome']) echo "<tr><th>Desired Outcome:</th><td>",nl2br(escapeChars($item['desiredOutcome'])),"</td></tr>\n";
if (!empty($item['parentId'])) {
    echo "<tr><th>Parents:&nbsp;</th><td>";
    $brk='';
    $pids=explode(',',$item['parentId']);
    $pnames=explode($config['separator'],$item['ptitle']);
    foreach ($pids as $pkey=>$pid) {
        $thisparent=makeclean($pnames[$pkey]);
        echo "$brk<a href='itemReport.php?itemId=$pid' title='Go to the $thisparent report'>$thisparent</a> ";
        $brk=', ';
    }
    echo "</td></tr>\n";
}
if ($item['categoryId']) echo "<tr><th>Category:</th><td><a href='editCat.php?id={$item['categoryId']}&amp;field=category'>".makeclean($item['category'])."</a></td></tr>\n";
if ($item['contextId']) echo "<tr><th>Space Context:</th><td><a href='editCat.php?id={$item['contextId']}&amp;field=context'>".makeclean($item['cname'])."</a></td></tr>\n";
if ($item['timeframeId']) echo "<tr><th>Time Context:</th><td><a href='editCat.php?id={$item['timeframeId']}&amp;field=time-context'>".makeclean($item['timeframe'])."</a></td></tr>\n";
if (!empty($item['deadline'])) {
    $deadline=prettyDueDate($item['deadline'],$config['datemask']);
    echo "<tr><th>Deadline:</th>"
        ,"<td class='{$deadline['class']}' title='{$deadline['title']}'>"
        ,$deadline['date'],"</td></tr>\n";
}
if ($item['type']==='a' || $item['type']==='w') echo '<tr><th>Next Action?</th><td>',($item['nextaction']==='y')?'Yes':'No',"</td></tr>\n";
if (!empty($item['recurdesc']) || !empty($item['recur'])) echo "<tr><th>Repeat</th><td>{$item['recurdesc']} ({$item['recur']})</td></tr>\n";
if (!empty($item['tickledate']))
	echo "<tr><th>Suppressed Until:</th><td>{$item['tickledate']}</td></tr>\n";
if (!empty($item['tags'])) {
	echo "<tr><th>Tags:</th><td>";
    $taglist=explode(',',$item['tags']);
    foreach ($taglist as $tag) echo "<a href='listitems.php?type=*&amp;tags=$tag'>$tag</a>, ";
    echo "</td></tr>\n";
}
echo '<tr><th>Created:</th><td>'.$item['dateCreated']."</td></tr>\n";
if ($item['lastModified']) echo '<tr><th>Last modified:</th><td>'.$item['lastModified']."</td></tr>\n";
if ($item['dateCompleted']) echo '<tr><th>Completed On:</th><td>'.$item['dateCompleted']."</td></tr>\n";
?>
</tbody></table>
<?php
if (empty($childtype)) {
    include_once 'footer.php';
    exit();
}
$values['parentId']=$values['itemId'];

$thisurl=parse_url($_SERVER['PHP_SELF']);
$thisfile=makeclean(basename($thisurl['path']));

if ($item['type']==='C') {  // if a checklist, wrap *all* children in a single form ?>
<form action='processItems.php' method='post'>
<?php }
//Create iteration arrays
$completed = array('n','y');

//table display loop
foreach ($completed as $comp) foreach ($childtype as $thistype) {
    $wasNAonEntry = array(); // reset for each table
    $thistableid="i$comp$thistype";
    $sectiontitle=(($comp==="y")?'Completed ':'').$typename[$thistype].'s';
    //Select items by type
    if ($thistype==='s') {
       $values['type']='p';
       $values['isSomeday']='y';
       $values['filterquery'] ='';
    } else {
        $values['isSomeday']='n';
        $values['type']=$thistype;
	    $values['filterquery'] = " AND ".sqlparts("typefilter",$config,$values); // only filter on type if not a someday
    }
    $values['filterquery'] .= " AND ".sqlparts("issomeday",$config,$values);

    $q=($comp==='y')?'completeditems':'pendingitems';  //suppressed items will be shown on report page
	$values['filterquery'] .= " AND ".sqlparts($q,$config,$values);

    $result = query("getchildren",$config,$values,$sort);

    if ($comp==='n') {
        // inherit some defaults from parent:
        $createItemId="0&amp;parentId={$values['itemId']}&amp;type=$thistype";
        foreach (array('categoryId','contextId','deadline') as $field)
            if ($item[$field]) $createItemId.="&amp;$field={$item[$field]}";
    }
    if ($result || $comp==='n') {
        $footertext=array();
        if ($comp==='y' && $config['ReportMaxCompleteChildren']
            && count($result) > $config['ReportMaxCompleteChildren']
            && $item['type']!=='L' && $item['type']!=='C' ) {
            $limit=$config['ReportMaxCompleteChildren'];
            $footertext[]="<a href='listItems.php?type=$thistype&amp;parentId={$values['parentId']}&amp;completed=true'".
                (($_SESSION['useLiveEnhancements'])?" onclick='return GTD.toggleHidden(\"$thistableid\",\"table-row\",\"f$thistableid\");'":'').
                ">".(count($result)-$limit)." more... (".count($result)." items in total)</a>";
        } else {
            $limit=count($result);
        }
        ?>
<div class='reportsection'>
        <?php
		$shownext= ($comp==='n') && ($values['type']==='a' || $values['type']==='w');
		$suppressed=0;
        $trimlength=$config[($comp==="n")?'trimLengthInReport':'trimLength'];
        if($trimlength) {
		    $descriptionField='shortdesc';
		    $outcomeField='shortoutcome';
        } else {
            $descriptionField='description';
            $outcomeField='desiredOutcome';
        }
		$dispArray=array();
        if ($shownext) $dispArray['NA']='NA';
        $dispArray['title']=$sectiontitle;
        $dispArray[$descriptionField]='Description';

        switch ($values['type']) {
            case 'T':
                // prevent display of category for (check)list items
                break;
            case 'a': // deliberately flows through to 'w'
        		if ($comp=="n") {
                    $dispArray['tickledate']='Suppress until';
        			$dispArray['deadline']='Deadline';
        			$dispArray['recurdesc']='Repeat';
                }
            case 'r': // deliberately flows through to 'w'
            case 'w':
                $dispArray['context']='context';
                $dispArray['timeframe']='time';
                break;
            case 'm': // deliberately flows through to 'p;
            case 'v': // deliberately flows through to 'p;
            case 'o': // deliberately flows through to 'p;
            case 'g': // deliberately flows through to 'p;
            case 's': // deliberately flows through to 'p;
            case 'p': // deliberately flows through to default;
                $dispArray[$outcomeField]='Outcome';
            default:
                $dispArray['category']='category';
                break;
        }

        $dispArray['created']='Date Created';
		if ($comp=="n" || $item['type']==='C') {
			$dispArray['checkbox']='Complete';
		} else {
			$dispArray['completed']='Date Completed';
		}
        foreach ($dispArray as $key=>$val) $show[$key]=true;
        if ($config['nextaction']==='single') $dispArray['NA.type']='radio';
		$i=0;
		$maintable=array();

        if ($result) foreach ($result as $row) {
			$cleantitle=makeclean($row['title']);

            $maintable[$i]=array();
            if ($i >= $limit) {
                if ($_SESSION['useLiveEnhancements']) {
                    $maintable[$i]['row.class']='togglehidden';
                } else {
                    array_pop($maintable);
                    break;
                }
            }
            $maintable[$i]['itemId']=$row['itemId'];
            $maintable[$i]['title']=$row['title'];
            $maintable[$i][$descriptionField]=$row['description'];
            $maintable[$i][$outcomeField]=$row['desiredOutcome'];
            $maintable[$i]['recurdesc']=$row['recurdesc'];
            $maintable[$i]['created']=date($config['datemask'],
                    (empty($row['dateCreated']))
                        ? null
                        : strtotime($row['dateCreated']));

			$maintable[$i]['categoryId']=$row['categoryId'];
			$maintable[$i]['category']=makeclean($row['category']);

			$maintable[$i]['contextId']=$row['contextId'];
			$maintable[$i]['context']=makeclean($row['cname']);
			$maintable[$i]['context.title']='Go to '.$maintable[$i]['context'].' context report';

			$maintable[$i]['timeframeId']=$row['timeframeId'];
			$maintable[$i]['timeframe']=makeclean($row['timeframe']);
			$maintable[$i]['timeframe.title']='Go to '.$maintable[$i]['timeframe'].' time-context report';

			if ($comp==='n') {
                if (!empty($row['tickledate'])) {
					if (strtotime($row['tickledate'])>time()) { // item is not yet tickled - count it, then skip displaying it
						$suppressed++;
						if ($_SESSION['useLiveEnhancements'])
                            $maintable[$i]['row.class']='togglehidden';
                        else {
						    array_pop($maintable);
                            continue;
                        }
					}
					$maintable[$i]['tickledate']=$row['tickledate'];
				} else
					$maintable[$i]['tickledate']=null;

                if (empty($row['deadline']))
                    $maintable[$i]['deadline']=null;
                else {
                    $deadline=prettyDueDate($row['deadline'],$config['datemask']);
                    $maintable[$i]['deadline']      =$deadline['date'];
                    $maintable[$i]['deadline.class']=$deadline['class'];
                    $maintable[$i]['deadline.title']=$deadline['title'];
                }


    			if ($shownext) {
                    $maintable[$i]['NA']=$comp!=="y" && $row['nextaction']==='y';
                    $maintable[$i]['NA.title']='Mark as a Next Action';
                    if ($maintable[$i]['NA']==='y') array_push($wasNAonEntry,$row['itemId']);
                }
   			} else {
				$maintable[$i]['completed']=date($config['datemask'],strtotime($row['dateCompleted']));
            }

			$maintable[$i]['checkbox.title']="Mark $cleantitle ".
                    ( ($comp==='y') ? 'in' : '' ).
                    "complete";
			$maintable[$i]['checkboxname']='isMarked[]';
			$maintable[$i]['checkboxvalue']=$row['itemId'];
            $maintable[$i]['checkboxchecked']=($comp==='y');

			$i++;
        }
        if ($comp==='n') {
            $maintable[$i]=array('categoryId'=>$item['categoryId'],'doreport'=>'');
            foreach ($dispArray as $field=>$dummy)
                $maintable[$i][$field]='';
            $maintable[$i]['itemId']=$createItemId;
            $maintable[$i]['title']="Add new {$typename[$thistype]}";
            $maintable[$i]['row.class']='sortbottom'.
                (($thistype==='T') ? ' creator' : '');
            $maintable[$i]['NA']=null;
		}
    	if ($suppressed) {
            $is=($suppressed===1)?'is':'are';
            $also=(count($maintable))?'also':'';
            $plural=($suppressed===1)?'':'s';
    		array_unshift($footertext,
                "<a href='listItems.php?tickler=true&amp;type={$thistype}&amp;parentId={$values['parentId']}'".
                (($_SESSION['useLiveEnhancements'])?" onclick='return GTD.toggleHidden(\"$thistableid\",\"table-row\",\"f$thistableid\");'":'').
                ">There $is $also $suppressed tickler ".$typename[$thistype].$plural." not yet due for action</a>"
            );
    	}
        if (empty($footertext)) {
            $tfoot='';
        } else {
            $tfoot="<tfoot id='f$thistableid'>\n";
            foreach ($footertext as $line) $tfoot.="<tr><td colspan='4'>\n$line\n</td></tr>\n";
            $tfoot.="</tfoot>\n";
        }
        if ($comp==='n' && $item['type']!=='C' && $result) { ?>
<form action='processItems.php' method='post'><?php
        }
        ?>
<table class='datatable sortable' id='<?php
        echo $thistableid;
?>' summary='table of children of this item'>
        <?php
        require('displayItems.inc.php');
        ?>
</table>
    	<?php
    } else {  // end of: if ($result)
        echo '<h3>No '
            ,($comp==='n')?"<a href='item.php?itemId=$createItemId' title='Create a new child'>":''
            ,$sectiontitle
            ,($comp==='n')?'</a>':''
            ,"</h3>\n";
    }
	if ( ($comp==="n" && $result && $item['type']!=='C') || ($item['type']==='C' && $comp!=="n") ) {
	   ?>
<p>
<input type="reset" class="button" />
<input type="submit" class="button" value="Update marked <?php echo $typename[$thistype]; ?>s" name="submit" />
<input type='hidden' name='referrer' value='<?php echo "{$thisfile}?itemId={$values['itemId']}"; ?>' />
        <?php if ($item['type']==='C') { ?>
<button type='submit' name='clearchecklist' value='y'>Clear Checklist</button>
        <?php } else { ?>
<input type="hidden" name="multi" value="y" />
        <?php } ?>
<input type="hidden" name="parentId" value="<?php echo $item['itemId']; ?>" />
<input type='hidden' name='ptype' value='<?php echo $item['type']; ?>' />
<input type='hidden' name='type' value='<?php echo $thistype; ?>' />
<input type="hidden" name="action" value="<?php if ($item['type']==='C') echo 'check'; ?>complete" />
<input type="hidden" name="wasNAonEntry" value='<?php echo implode(' ',$wasNAonEntry); ?>' />
</p>
        <?php
        if ($item['type']!=='C') { ?>
</form>     <?php
        }
    }
    if ($result || $comp==='n') { ?>
</div>  <?php
    }
}  // end of foreach ($completed as $comp) foreach ($childtype as $thistype)
if ($item['type']==='C') { ?>
</form><?php
}
include_once 'footer.php';
?>
