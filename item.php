<?php
/*
 * edit an item
 */
include_once 'headerDB.inc.php';
$values = array();
$values['itemId']= (empty($_REQUEST['itemId']))? 0 : (int) $_REQUEST['itemId'];
$values['parentId']=array();

$values['filterquery']='';
$taglisttemp=query('gettags',$values);
$taglist=array();
if ($taglisttemp) foreach ($taglisttemp as $tag) $taglist[]=$tag['tagname'];

//SQL CODE
if ($values['itemId']) { // editing an item
    $where='edit';
    $values['filterquery']=' WHERE '.sqlparts('singleitem',$values);
    $result = query("selectitem",$values);
    if ($result) {
        $values = $result[0];
        $nextaction= ($result[0]['nextaction']==='y');
        $parents = query("selectparents",$values);
    } else {
        include_once 'header.inc.php';
        echo "<p class='error'>Failed to retrieve item {$values['itemId']}</p>";
        return;
    }
} else { // creating an item
    $where='create';
    //set defaults
    $nextaction=false;
    $values['title']=$values['description']=$values['desiredOutcome']=$values['tagname']='';
    $values['deadline']=$values['dateCompleted']=$values['recurdesc']=$values['tickledate']=null;
    $values['type']=(empty($_REQUEST['type'])) ? 'a' : $_REQUEST['type'];
    $values['isSomeday']=(   (isset($_GET['isSomeday']) && $_GET['isSomeday']==='y')
                          || (isset($_GET['someday'])   && $_GET['someday']=='true')
                         ) ? 'y' : 'n';
    $nextaction=isset($_REQUEST['nextonly']) && ($_REQUEST['nextonly']=='true' || $_REQUEST['nextonly']==='y');
    foreach ( array('categoryId','contextId','timeframeId') as $cat)
        $values[$cat]= (isset($_REQUEST[$cat]))?(int) $_REQUEST[$cat]:0;

    if ($values['type']==='s') {
        $values['isSomeday']='y';
        $values['type']='p';
    } elseif ($values['type']==='n') {
        $nextaction=true;
        $values['type']='a';
    }
}
$canchangetypesafely=array('a','r','w');
$ptypes=getParentType($values['type']);
if (count($ptypes)) {
    $values['ptype']=$ptypes[0];
    if (false!==($ndx=array_search('p',$ptypes,true)))
        array_splice($ptypes,$ndx+1,0,'s');
}
$show=getShow($where,$values['type']);
$show['tags']=$show['header']=$show['footer']=$show['submitbuttons']=true;
$show['scriptparents']= $_SESSION['useLiveEnhancements'] && !empty($values['ptype']) && $show['ptitle'];
$show['dateCreated']=!empty($values['itemId']);
$show['changetypes']=    in_array($values['type'],$canchangetypesafely)
                      && $values['itemId'] ;

if (!$values['itemId']) {
    $values['tickledate']= ($show['tickledate'] && !empty($_REQUEST['tickledate']))
                            ? $_REQUEST['tickledate']
                            : NULL;
    if ($show['deadline'] && !empty($_REQUEST['deadline']))$values['deadline']=$_REQUEST['deadline'];
    $parents=array();
    if (!empty($_REQUEST['parentId'])) {
        $pids=$_REQUEST['parentId'];
        if (!is_array($pids)) $pids=array($pids);
        if ($show['ptitle']) foreach ($pids as $pid) {
            $result=query("selectitemshort",array('itemId'=>$pid));
            if ($result) $parents[]=array(
                 'parentId'=>$result[0]['itemId']
                ,'ptitle'=>$result[0]['title']
                ,'isSomeday'=>$result[0]['isSomeday']
                ,'ptype'=>$result[0]['type']);
        } else {
            $values['parentId']=$pids;
        }
    }
}
$typename=   ( $nextaction ? 'Next ' : '')
           . ( ($values['isSomeday']==="y")
                ? "Someday/Maybe"
                : getTypes($values['type'],(count($parents) ? $parents[0]['ptype'] : null) )
            );
$title=($values['itemId']>0)?("Edit $typename: ".makeclean($values['title'])):"New $typename";

if ($parents)
    foreach ($parents as $parent)
        $values['parentId'][]=$parent['parentId'];
//create filters for selectboxes
$values['timefilterquery'] = ($_SESSION['config']['useTypesForTimeContexts'] && $values['type']!=='i')?" WHERE ".sqlparts("timetype",$values):'';

//create item, timecontext, and spacecontext selectboxes
$cashtml = categoryselectbox($values);
$cshtml = contextselectbox($values);
$tshtml = timecontextselectbox($values);

$oldtype=$values['type'];

$hiddenvars=array(
            'referrer'=>(isset($_REQUEST['referrer']))?$_REQUEST['referrer']:''
            ,'afterCreate'=>''
            ,'type'   =>$values['type']
            ,'itemId' =>$values['itemId']
            ,'firstDayOfWeek'=>(int) $_SESSION['config']['firstDayOfWeek']
            );

if ($values['itemId']) {
    $hiddenvars['action']='fullUpdate';
} else
    $hiddenvars['action']='create';

if (!count($ptypes))
    $potentialparents=array();
elseif ($_SESSION['useLiveEnhancements']) {
    $alltypes=getTypes();
    $allowedSearchTypes=array();
    if (count($ptypes)>1) $allowedSearchTypes[0]='All';
    foreach($ptypes as $ptype)
        $allowedSearchTypes[$ptype]=$alltypes[$ptype].'s';
    $values['ptypefilterquery']=' AND '.sqlparts('typeinlist',
                                                 array('types'=>implode('',$ptypes)));
    $potentialparents = query("parentselectbox",$values);
    if (!$potentialparents) $potentialparents=array();
} else
    $values['ptypefilterquery']=' AND '.sqlparts('typefilter',array('type'=>$ptypes[0]));

if ($show['scriptparents']) {

    $partt= $ptitle= $pid ='[';
    $sep='';

    if (empty($_REQUEST['nextId']) && $ptypes) foreach ($ptypes as $thisptype) {
        $pid   .=$sep.'"0"';
        $ptitle.=$sep.'"Create a parent"';
        $partt .="$sep\"$thisptype\"";
        $sep=',';
    }

    foreach ($potentialparents as $oneparent) {
        $pid   .=$sep.'"'.$oneparent['itemId'].'"';
        $ptitle.=$sep.'"'.escapeforjavascript($oneparent['title']).'"'; // escape backslashes and double-quotes
        $partt .=$sep.'"'
                .(($oneparent['isSomeday']==='y')?'s':$oneparent['type'])
                .'"';
        $sep=',';
    }
    $pid   .=']';
    $ptitle.=']';
    $partt .=']';
}
$descrows=8;
$outcomerows=3;
/*
    ===========================================================================
                            recurrence handling
    ===========================================================================
*/
/*--------------------------------------------------------------------
   utility arrays used by the drop-down boxes in the form
*/
$days=array('SU'=>'Sunday','MO'=>'Monday','TU'=>'Tuesday','WE'=>'Wednesday',
            'TH'=>'Thursday','FR'=>'Friday','SA'=>'Saturday');

$dates=array(1=>'1st',2=>'2nd',3=>'3rd');
for ($i=4;$i<31;$i++) $dates[$i]="{$i}th";
$dates[21]='21st';
$dates[22]='22nd';
$dates[23]='23rd';
$dates[31]='31st';
$dates['-1']='last';

$weeks=array(1=>'1st',2=>'2nd',3=>'3rd',4=>'4th',5=>'5th',-1=>'Last');
$months=array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
        7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December');

$defaults=array('FREQtype'=>'NORECUR','FREQ'=>'NORECUR','INTERVAL'=>1,'UNTIL'=>null,
                'BYDAY'=>array(),'BYMONTHDAY'=>null,'BYMONTH'=>null,
                'day'=>null,'week'=>null );

/*--------------------------------------------------------------------
   turn rfc2445 text into form fields
*/
$recur=$defaults;
if (empty($values['recur'])) {
    $values['recur']=$values['recurdesc']=null;
} else {
    require_once 'iCalcreator.class.inc.php';
    $e = new vevent();
    $e->parse(array('RRULE:'.$values['recur']));
    $rrule=$e->getProperty('rrule');
    foreach ($defaults as $key=>$val)
        if (isset($rrule[$key])) $recur[$key]=$rrule[$key];
    $recur['FREQtype']=$recur['FREQ'];
    switch ($recur['FREQ']) {
        case 'DAILY':
            break;
        case 'WEEKLY':
            if(!empty($recur['BYDAY'])) $recur['FREQtype']='WEEKLYBYDAY';
            break;
        case 'MONTHLY':
            if(!empty($recur['BYMONTHDAY']))
                $recur['FREQtype']='MONTHLYBYDAY';
            elseif(!empty($recur['BYDAY']) && !empty($recur['BYDAY'][0]) && !empty($recur['BYDAY']['DAY']) )
                $recur['FREQtype']='MONTHLYBYWEEK';
            break;
        case 'YEARLY':
            if(!empty($recur['BYMONTHDAY']) && !empty($recur['BYMONTH']) )
                $recur['FREQtype']='YEARLYBYDATE';
            elseif (!empty($recur['BYMONTH']) && !empty($recur['BYDAY'][0]) && !empty($recur['BYDAY']['DAY'])  )
                $recur['FREQtype']='YEARLYBYWEEK';
            break;
        default:
            break;
    }
    // if the recurrence pattern is based on specific days of the week, put these
    // into a boolean array, so it's easy to test which are live
    $recur['day']=array();
    foreach ($days as $key=>$val) $recur['day'][$key]=false;
    if (!empty($recur['BYDAY']['DAY'])) {
        $recur['day'][$recur['BYDAY']['DAY']]=true;
        if (isset($recur['BYDAY'][0])) $recur['week']=$recur['BYDAY'][0];
    } elseif (!empty($recur['BYDAY']['0'])) {
        foreach ($recur['BYDAY'] as $key=>$val)
          if (array_key_exists('DAY',$val))
            $recur['day'][$val['DAY']]=true;
    }
    log_value('Recurrence values going into form',$recur);
    // get the date of the next recurrence, after the current one has been completed, for the user's information
    $nextdate=getNextRecurrence($values);
    if ($nextdate) $nextdate=date($_SESSION['config']['datemask'],strtotime($nextdate));
}
/*
    ===========================================================================
    end of recurrence handling
    end of data handling - start of html output
    ===========================================================================
*/
if ($show['header']) include_once 'headerHtml.inc.php';
gtd_handleEvent(_GTD_ON_DATA,$pagename);
if ($show['header']) {
  if ($_SESSION['useLiveEnhancements']) { ?>
    <script type="text/javascript">
    /* <![CDATA[ */
    $(document).ready(function() {
      GTD.initItem(document);
      <?php if ($show['scriptparents']) { ?>
        GTD.typenames={ <?php
          $sep='';
          foreach ($alltypes as $key=>$val) {
              echo "$sep$key:'$val'";
              $sep=',';
          }
        ?> };
        GTD.tags = ",<?php foreach ($taglist as $tag) echo "$tag,"; ?>";
        GTD.parentselect = new GTD.ParentSelector(
            <?php echo "$pid\n,\n$ptitle\n,\n$partt\n,\"{$values['ptype']}\" \n"; ?>
        );
        GTD.parentselect.refinesearch('<?php echo $values['ptype']; ?>');
      <?php } ?>
    });
    /* ]]> */
    </script>
    <?php
    }
    if ($values['itemId'])
        // don't want this appearing in TITLE, but do want it in H1
        $titlefull= "<a href='itemReport.php?itemId={$values['itemId']}'>"
            ."<img src='themes/{$_SESSION['theme']}/report.gif' class='noprint' "
            ."alt='Report' title='View Report' /></a>"
            .$title;
    include_once 'header.inc.php';
}
$sep='<p>';
if ($show['changetypes']) foreach ($canchangetypesafely as $totype)
    if ($totype!==$values['type']) {
        echo "$sep <a href='processItems.php?action=changeType&amp;safe=1"
            ,"&amp;itemId=",$values['itemId']
            ,"&amp;type=$totype"
            ,"&amp;isSomeday=",$values['isSomeday']
            ,"&amp;oldtype=",$values['type']
            ,(empty($referrer))?'':"&amp;referrer=$referrer"
            ,"'>Convert to ",getTypes($totype),"</a>\n";
        $sep=' , ';
    }
if ($show['type']) {
    echo $sep; ?>
    <a href='assignType.php?itemId=<?php echo $values['itemId']; ?>'>Convert to another type</a>
    (Warning, changing to another type will sever all relationships to its parent and child items)
    <?php
    $sep=' , ';
}
if ($sep!=='<p>') echo "</p>\n";
?>
<form action="processItems.php" id="itemform" method="post">
<div class='form'>
    <div class='formrow' id='errorbox'><span class="error" id='errorMessage'></span></div>
        <?php if($show['title']) { ?>
            <div class='formrow'>
                    <label for='title' class='left first'>Title:</label>
                    <input type="text" name="title" id="title" value="<?php echo makeclean($values['title']); ?>" />
            </div>
        <?php } else $hiddenvars['title']=$values['title'];

        if ($show['ptitle']) { ?>
            <div class='formrow'>
                <label for='parenttable' class='left first'>Parent(s):</label>
                <?php if ($show['scriptparents']) { ?>
<table summary='parents selected' class='datatable' id='parenttable' style='display:inline'>
    <tbody id='parentlist'>
        <tr>
            <td><a class='add' href='#' onclick='return GTD.parentselect.search();' title='Click here to select or create parent(s)'>+</a></td>
            <td><a href='#' onclick='return GTD.parentselect.search();' title='Click here to select parent(s)'>Select parent(s)</a>
                <?php if (!isset($_REQUEST['nextId']))
                    echo " or <a href='#' onclick='return GTD.createparent(\""
                        ,$ptypes[0]
                        ,"\");' title='Click here to create a parent(s)'>Create a parent</a></td>";
                ?>
            <td>&nbsp;</td>
        </tr><?php
            if (is_array($parents) && count($parents)) foreach ($parents as $parent) {
                $ptype=($parent['isSomeday']=='y')?'s':$parent['ptype'];
                echo "<tr id='parentrow{$parent['parentId']}'>"
                    ,"<td><a href='#' onclick='return GTD.removeParent(\"{$parent['parentId']}\")'
                            title='remove as parent' class='remove'>X</a></td>"
                    ,"<td><a href='itemReport.php?itemId={$parent['parentId']}' title='view parent'>"
                    ,makeclean($parent['ptitle']),"</a></td>"
                    ,"<td>",getTypes($ptype)
                    ,"<input type='hidden' name='parentId[]' value='{$parent['parentId']}' /></td>"
                    ,"</tr>\n";
                }
    ?></tbody>
</table><?php
                } else { ?>
                    <select name="parentId[]" id='parenttable' multiple="multiple" size="6">
                        <?php echo parentselectbox($values); ?>
                    </select>
                <?php } ?>
            </div>
        <?php } elseif (!empty($values['parentId']))
            foreach ($values['parentId'] as $pid)
                echo hidePostVar('parentId[]',$pid);
        ?><div class='formrow'>
            <?php if ($show['category']) { ?>
                <label for='categoryId' class='left first'>Category:</label>
                <select name='categoryId' id='categoryId'>
                <?php echo $cashtml; ?>
                </select>
            <?php } else $hiddenvars['categoryId']=$values['categoryId'];
            if ($show['context']) { ?>
                <label for='contextId' class='left'>Context:</label>
                <select name='contextId' id='contextId'>
                <?php echo $cshtml; ?>
                </select>
            <?php } else $hiddenvars['contextId']=$values['contextId'];
            if ($show['timeframe']) { ?>
                <label for='timeframeId' class='left'>Time:</label>
                <select name='timeframeId' id='timeframeId'>
                <?php echo $tshtml; ?>
                </select>
            <?php } else $hiddenvars['timeframeId']=$values['timeframeId']; ?>
        </div>
        
        <div class='formrow'>
            <?php
            $class=' first';
            if ($show['tickledate']) { ?>
                <label for='tickledate' class='left<?php
                    echo $class;$class='';
                ?>' title='Temporarily puts this into the tickler file, hiding it from the active view'>Suppress until:</label>
                <input type='text' size='10' name='tickledate' id='tickledate' class='hasdate' value=<?php
                    echo "'{$values['tickledate']}'";
                ?> /><button id='tickledate_trigger' class='calendarbutton' type='button'>&hellip;</button>
            <?php
            } else
                $hiddenvars['tickledate']=$values['tickledate'];
            if ($show['deadline']) { ?>
                <label for='deadline' class='left <?php
                    echo "$class";
                    $class='';
                     ?>' >Deadline:</label>
                <input type='text' size='10' name='deadline' id='deadline' class='hasdate' value=<?php
                    echo "'{$values['deadline']}'";
                    ?> /><button id='deadline_trigger' class='calendarbutton' type='button'>&hellip;</button>
            <?php } else $hiddenvars['deadline']=$values['deadline'];
            
            if ($show['dateCompleted']) { ?>
                <label for='dateCompleted' class='left<?php
                    echo $class;$class='';
                ?>'>Completed:</label>
                <input type='text' size='10' class='hasdate' name='dateCompleted' id='dateCompleted' value='<?php
                    echo $values['dateCompleted'];
                ?>'/><button id='dateCompleted_trigger' class='calendarbutton' type='button'>&hellip;</button>
				<button type='button' id='dateCompleted_today' onclick="javascript:GTD.completeToday('dateCompleted');">Today</button>
            <?php } else $hiddenvars['dateCompleted']=$values['dateCompleted']; ?>
        </div>
        
        <?php if ($show['description']) { ?>
            <div class='formrow'>
                <label for='description' class='left first'>Description:</label>
                <textarea cols='50' rows='<?php
                    echo $descrows;
                ?>' name='description' id='description'><?php echo makeclean($values['description']); ?></textarea>
            </div>
        <?php } else $hiddenvars['description']=$values['description'];
        
        if ($show['desiredOutcome']) { ?>
            <div class='formrow'>
                <label for='outcome' class='left first'>Desired Outcome:</label>
                <textarea cols='50' rows='<?php
                    echo $outcomerows;
                ?>' name='desiredOutcome' id='outcome' class='big'><?php echo makeclean($values['desiredOutcome']); ?></textarea>
            </div>
        <?php
        } else $hiddenvars['desiredOutcome']=$values['desiredOutcome'];
        
        if ($show['NA']) { ?>
            <div class='formrow'>
                <label for='nextaction' class='left first'>Next Action:</label>
                <input type="checkbox" name="nextaction" id="nextaction" value="y" <?php if ($nextaction) echo " checked='checked'"; ?> />
            </div><?php
        }  else $hiddenvars['nextaction']=($nextaction)?'y':'';
        
        if ($show['isSomeday']) { ?>
            <div class='formrow'>
                <label for='isSomeday' class='left first'>Someday:</label>
                <input type='checkbox' name='isSomeday' id='isSomeday' value='y' title='Places item in Someday file'<?php if ($values['isSomeday']==='y') echo " checked='checked'";?> />
            </div><?php
        } else $hiddenvars['isSomeday']=$values['isSomeday'];
        
        if ($show['recurdesc']) { ?>
            <div class='formrow' id='recurdiv'>
                <label class='left first'>Repeat:</label>
                <?php
                //<input type='text' name='recurdesc' id='recurdesc' value='
                //title='This description is just for your reference, and is not used by the software' /> (description of pattern)
                    echo empty($values['recurdesc'])
                            ? '(none)'
                            : $values['recurdesc']
                        ,"<label> If completed today, "
                        ,(empty($nextdate))
                            ? " there would be no further recurrence"
                            : " the next recurrence would be <span id='nextduedate'>$nextdate</span>"
                        ,"</label>";
                ?> <a href='#recurform' <?php
                    if ($_SESSION['useLiveEnhancements'])
                        echo " onclick='return GTD.showrecurbox();' ";
                ?>>Change ...</a>
                    <?php
                        if (!empty($values['recur'])) echo " <label>{$values['recur']}</label>";
                    ?>
            </div>
        <?php } else {
            $hiddenvars['recurdesc']=$values['recurdesc'];
            $hiddenvars['recur']=$values['recur'];
        }
        
        if ($show['tags']) { ?>
            <div class='formrow'>
                <label class='left first'>Tags:</label>
                <input type='text' id='tags' name='tags' size='60' value=<?php
                    echo "'{$values['tagname']}'";
                    if (empty($taglist)) { 
                        ?> /><br />
                        You have no tags yet. You can create some, separated with commas, in the box above.
                        <?php
                    } else { 
                    ?> /> (use commas to separate)
                    <a href='#' onclick='return GTD.tagShow(this);'>Show all</a>
                    <br />
                    <span id='taglist'>
                        <?php
                        $sep='';
                        if ($_SESSION['useLiveEnhancements']) {
                          $pre="<a class='add' href='#' onclick='return GTD.tagAdd(this)'>";
                          $post="</a>\n";
                        } else {
                          $pre=$post="";
                        }
                        foreach ($taglist as $tag) {
                            echo "$sep$pre$tag$post";
                            $sep=', ';
                        }
                        ?>
                    </span>
                <?php } ?>
            </div>
        <?php
    }
$key='afterCreate'.$values['type'];
// always use config value when creating
if (!empty($_SESSION['config']['afterCreate'][$values['type']]) && empty($_SESSION[$key]))
	$_SESSION[$key]=$_SESSION['config']['afterCreate'][$values['type']];

if ($values['itemId'] && !empty($_SESSION[$key]))
    $tst=$_SESSION[$key];
else
    $tst=$_SESSION['config']["afterCreate{$values['type']}"];
if ($show['submitbuttons']) { ?>
    <?php
    if (!empty($_REQUEST['nextId'])) {
        // don't show any next item buttons: we are creating a parent item here
        $hiddenvars['referrer']='item.php?itemId='.$_REQUEST['nextId'];
        $hiddenvars['addAsParentTo']=$_REQUEST['nextId'];
        ?><div class='formbuttons'>
            <input type='submit' value='Create item and assign as parent' name='submit' />
        <?php
    } else if ($_SESSION['config']['radioButtonsForNextPage']) { ?>
        <div class='formrow'>
        <label class='left first'>After <?php
            echo ($values['itemId'])?'updating':'creating';
        ?>
        </label>
        <?php
        if ($show['ptitle'])
            echo "<input type='radio' name='afterCreate' id='parentNext' value='parent' class='first'"
        	 	,($tst=='parent')?" checked='checked' ":""
        		," /><label for='parentNext' class='right'>View parent</label>\n";

        echo "<input type='radio' name='afterCreate' id='itemNext' value='item' class='notfirst'"
        	 	,($tst=='item')?" checked='checked' ":""
        		," /><label for='itemNext' class='right'>View item</label>\n"
        	,"<input type='radio' name='afterCreate' id='listNext' value='list' class='notfirst'"
        	 	,($tst=='list')?" checked='checked' ":""
        		," /><label for='listNext' class='right'>List items</label>\n"
        	,"<input type='radio' name='afterCreate' id='anotherNext' value='another' class='notfirst'"
        	 	,($tst=='another')?" checked='checked' ":""
        		," /><label for='anotherNext' class='right'>Create another $typename</label>\n";
        if ($values['type']==='p')
            echo "<input type='radio' name='afterCreate' id='childNext' value='child' class='notfirst'"
        	 	,($tst=='child')?" checked='checked' ":""
        		," /><label for='childNext' class='right'>Create a child Next Action</label>\n";
        else if ($values['type']==='C' || $values['type']==='L')
            echo "<input type='radio' name='afterCreate' id='childNext' value='child' class='notfirst'"
        	 	,($tst=='child')?" checked='checked' ":""
        		," /><label for='childNext' class='right'>Create a child item</label>\n";

        if (!empty($hiddenvars['referrer']) || !empty($_SESSION[$key])) {
            echo "<input type='radio' name='afterCreate' id='referrer' value='referrer' class='notfirst'"
        	 	,($tst=='referrer')?" checked='checked' ":''
        		," /><label for='referrer' class='right'>Return to previous list</label>\n";
        } ?>
        </div>
        <div class='formbuttons'>
        <?php echo "<input type='submit' value='"
            ,($values['itemId'])?"Update $typename":'Create'
            ,"' name='submit' />\n";
    } else { ?>
        <div class='formbuttons formrow'>
        <label class='left first'><?php
            echo ($values['itemId'])?'Update':'Create';
        ?> and then:</label>
        <?php
        $buttons=array();
        if ($show['ptitle']) $buttons['parent']='View parent';
        $buttons['item']='View item';
        $buttons['list']="List all {$typename}s";
        $buttons['another']="Create another $typename";
        if ($values['type']==='p')
            $buttons['child']='Create a child Next Action';
        else if ($values['type']==='C' || $values['type']==='L')
            $buttons['child']='Create a child item';
        if (!empty($hiddenvars['referrer']) || !empty($_SESSION[$key]))
            $buttons['referrer']='Return to previous list';

        $row1=$row2='';
        $skip=0;
        foreach ($buttons as $buttonname=>$buttonlabel) {
            $btn="<input type='submit' value='$buttonlabel' name='{$buttonname}Next'";
            if ($tst===$buttonname) {
                if ($skip) $row1="<td colspan='$skip'></td>";
                $row1.="<td rowspan='2'>$btn /></td>\n";
            } else {
                $row2.="<td>$btn /></td>\n";
                $skip++;
            }
        }
        ?>
        <table summary='submit buttons' class='submitbuttons'>
            <tbody><?php
                if (!empty($row1)) echo "<tr>$row1</tr>\n";
            ?><tr><?php echo $row2; ?></tr>
            </tbody>
        </table>
    <?php } ?>
    <input type='reset' value='Reset' />
<?php
    if ($values['itemId']) {
        echo "<input type='hidden' name='oldtype' value='$oldtype' />\n";
        if ($_SESSION['useLiveEnhancements']) { ?>
            <input type='button' class='warning' name='bDelete' value='Delete' onclick='GTD.confirmDelete(this);' />
            <input type='hidden' name='doDelete' id='doDelete' value='n' />
        <?php } else { ?>
            <input type='checkbox' name='doDelete' id='doDelete' value='y' title='Deletes item. Child items are orphaned, NOT deleted.'/>
            <label for='doDelete'>Delete&nbsp;<?php echo $typename; ?></label>
        <?php
        }
    }
?></div><?php
} // end of if ($show['submitbuttons'])
?>
<div class='hidden'>
   <input type='hidden' name='required' value="title:notnull:Title must not be blank.,tickledate:date:Suppress date must be a valid date.,deadline:date:Deadline must be a valid date.,dateCompleted:date:Completion date must be a valid date.,UNTIL:date:Dont-repeat-after date must be a valid date." />
   <input type='hidden' name='dateformat' value='ccyy-mm-dd' />
    <?php
        if (!$values['itemId']) {
            // we are creating a new item, so remember its defaults, to make it easy to create another just like it
            $hiddenvars['lastcreate']=$_SERVER['QUERY_STRING'];
        }
        foreach ($hiddenvars as $key=>$val) echo hidePostVar($key,$val);
    ?>
</div>
<?php if ($show['recurdesc']) {
?><div id='recur' <?php
    if ($_SESSION['useLiveEnhancements']) echo " class='togglehidden' ";
?>><a name='recurform' id='recurform'></a>
    <div class='formrow'>
        <span>
            <label class='left first' for='freqtext'>Repeat:</label>
            <input type='radio' id='freqtext' name='FREQtype' value='TEXT' <?php
                if ($recur['FREQ']==='TEXT') echo "checked='checked'";
                if  ($_SESSION['config']['suppressCustomRecurrences']) echo " disabled='disabled' ";
            ?> />in .ics format
            <label>RRULE: <input type='text' name='icstext' size='70' <?php
                if (!empty($values['recur']))
                    echo " value='{$values['recur']}' ";
                if  ($_SESSION['config']['suppressCustomRecurrences'])
                    echo " disabled='disabled' ";
            ?> /></label>
        </span>
    </div>

    <div class='formrow'>
        <label class='left first' for='INTERVAL'>Repeat every:</label> <input type='text' value='<?php
            echo $recur['INTERVAL'];
        ?>' name='INTERVAL' size='3' id='INTERVAL'  />
        <span>
        <?php
        foreach (array('DAILY'=>'day','WEEKLY'=>'week','MONTHLY'=>'month','YEARLY'=>'year') as $freq=>$val) { ?>
                <input type='radio' name='FREQtype' <?php
                    echo "value='$freq' id='$freq' "
                        ,($recur['FREQtype']===$freq) ?" checked='checked' " : '';
                ?> /> <label class='right' for='<?php echo $freq; ?>'><?php echo $val; ?>(s)</label>
        <?php } ?>
        after each completion date
        </span>
    </div>

    <div class='formrow'>
        <span>
        <label class='left first' for='WEEKLYBYDAY'>week(s):</label>
            <input type='radio' name='FREQtype' id='WEEKLYBYDAY' value='WEEKLYBYDAY' <?php
            if ($recur['FREQtype']==='WEEKLYBYDAY') echo "checked='checked'";
            ?> /> on <?php
            foreach ($days as $key=>$val)
                echo "<label class='left'><input type='checkbox' name='WEEKLYday[]' value='$key'"
                    ,($recur['day'][$key])?" checked='checked' ":' '
                    ,"/>",substr($val,0,3),"</label>";
            ?>
        </span>
    </div>

    <div class='formrow'>
        <span>
            <label class='left first' for='MONTHLYBYDAY'>month(s):</label>
            <input type='radio' name='FREQtype' id='MONTHLYBYDAY' value='MONTHLYBYDAY' <?php
                if ($recur['FREQtype']==='MONTHLYBYDAY') echo "checked='checked'";
        ?> /><label for='MONTHLYdate'> on the </label>
            <select name='MONTHLYdate' id='MONTHLYdate'><?php
                 foreach ($dates as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['BYMONTHDAY']==$key)?" selected='selected'":''
                        ,">$val</option>";
            ?></select><label for='MONTHLYdate'>&nbsp;day</label>
        </span>
    </div>

    <div class='formrow'>
        <span>
            <label class='left first' for='MONTHLYBYWEEK'>month(s):</label>
            <input type='radio' name='FREQtype' id='MONTHLYBYWEEK' value='MONTHLYBYWEEK' <?php
                if (!empty($recur['week'])) echo "checked='checked'";
            ?> /> <label for='MONTHLYweek'>on the</label>&nbsp;<select id='MONTHLYweek' name='MONTHLYweek'><?php
                foreach ($weeks as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['week']==$key)?" selected='selected'":''
                        ,">$val</option>";
            ?></select>&nbsp;<select name='MONTHLYweekday' id='MONTHLYweekday'><?php
                foreach ($days as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['day'][$key])?" selected='selected'":''
                        ,">$val</option>";
            ?></select>
        </span>
    </div>

    <div class='formrow'>
        <span>
            <label class='left first' for='YEARLYBYDATE'>year(s):</label>
            <input type='radio' name='FREQtype' id='YEARLYBYDATE' value='YEARLYBYDATE' <?php
                if ($recur['FREQtype']==='YEARLYBYDATE') echo "checked='checked'";
            ?> /> <label for='YEARLYdate'>on the </label>
            <select id='YEARLYdate' name='YEARLYdate'><?php
                 foreach ($dates as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['BYMONTHDAY']==$key)?" selected='selected' ":''
                        ,">$val</option>";
            ?></select><label for='YEARLYdate'> day </label><label for='YEARLYmonth'> of </label>
            <select id='YEARLYmonth' name='YEARLYmonth'><?php
                 foreach ($months as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['BYMONTH']==$key)?" selected='selected'" :''
                        ,">$val</option>";
            ?></select>
        </span>
    </div>

    <div class='formrow'>
        <span>
            <label class='left first' for='YEARLYBYWEEK'>year(s):</label>
            <input type='radio' name='FREQtype' id='YEARLYBYWEEK' value='YEARLYBYWEEK' <?php
                if ($recur['FREQtype']==='YEARLYBYWEEK') echo "checked='checked'"; ?> />
            <label for='YEARLYweeknum'>on the </label><select id='YEARLYweeknum' name='YEARLYweeknum'><?php
                foreach ($weeks as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['week']==$key)?" selected='selected'":''
                        ,">$val</option>";
            ?></select>&nbsp;<select name='YEARLYweekday' id='YEARLYweekday'><?php
                foreach ($days as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['day'][$key])?" selected='selected'":''
                        ,">$val</option>";
            ?></select>
            <label for='YEARLYweekmonth'> of </label>
            <select name='YEARLYweekmonth' id='YEARLYweekmonth'><?php
                 foreach ($months as $key=>$val)
                    echo "<option value='$key'"
                        ,($recur['BYMONTH'][0]==$key)?" selected='selected'":''
                        ,">$val</option>";
            ?></select>
        </span>
    </div>

    <div class='formrow'>
        <label class='left first' for='UNTIL'>Don't repeat after:</label>
        <input type='text' size='10' class='hasdate' name='UNTIL' id='UNTIL' value='<?php
            if (!empty($recur['UNTIL']))
              echo $recur['UNTIL']['year'],'-'
                  ,$recur['UNTIL']['month'],'-'
                  ,$recur['UNTIL']['day'];
        ?>' /><button id='UNTIL_trigger' type='button' class='calendarbutton'>&hellip;</button>
        <span>
            <input type='radio' name='FREQtype' value='NORECUR' id='NORECUR' <?php
                if ($recur['FREQtype']==='NORECUR') echo " checked='checked' ";
            ?> /><label class='right' for='NORECUR'>No repeat</label>
        </span>
    </div>
</div>
<?php } ?>
</div>
</form>
<?php
if ($show['dateCreated']) { ?>
    <div class='details'>
        <span class='detail'>Date Added: <?php echo $values['dateCreated']; ?></span>
        <span class='detail'>Last Modified: <?php echo $values['lastModified']; ?></span>
    </div>
<?php }
if ($show['scriptparents']) { 
?><div id='searcher'>
    <div id='donebox'>
        <a href='javascript:GTD.parentselect.close();' id='closesearch' title='click, or press escape, to close'>X</a>
    </div>
    <form method='get' action='#' onsubmit='return GTD.parentselect.close();'>
        <p>
            <label for="searcherneedle">Search for</label>
            <input type='text' name='searcherneedle' id='searcherneedle' onkeyup='return GTD.parentselect.refinesearch(this);' />
            in the titles of
        <br /><?php
            foreach ($allowedSearchTypes as $key=>$value)
                echo "&nbsp;&nbsp;<label for='radio$key'>$value</label>\n"
                    ,"<input type='radio' name='qtype' class='notfirst' value='$key' id='radio$key' "
                    ," onclick='return GTD.parentselect.refinesearch(this);' "
                        ,($key===$values['ptype'])?' checked="checked" ':''
                    ," />&nbsp;\n";
            ?><input type='hidden' name='returntype' value='table' />
        </p>
        <div id='searchresults'>&nbsp;</div>
    </form>
</div><?php
}
if ($show['footer']) include_once 'footer.inc.php';
function hidePostVar($name,$val) {
    $val=makeclean($val);
    return "<input type='hidden' id='$name' name='$name' value='$val' />\n";
}
?>
