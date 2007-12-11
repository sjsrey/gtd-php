<?php
//INCLUDES
include_once('header.php');

$values = array();
$values['itemId']= (isset($_REQUEST['itemId']))?(int) $_REQUEST['itemId']:0;
$values['parentId']=array();

//SQL CODE
if ($values['itemId']) { // editing an item
    $where='edit';
    $result = query("selectitem",$config,$values,$sort);
    if ($result) {
        $values = $result[0];
        //Test to see if nextaction
        $result = query("testnextaction",$config,$values,$sort);
        $nextaction= ($result && $result[0]['nextaction']==$values['itemId']);
        $parents = query("lookupparent",$config,$values);
        // if any are somedays, turn type 'p' into type 's'
    } else {
        echo "<p class='error'>Failed to retrieve item {$values['itemId']}</p>";
        return;
    }
} else { // creating an item
    $where='create';
    //set defaults
    $nextaction=false;
    $values['title']='';
    $values['description']='';
    $values['desiredOutcome']='';
    $values['deadline']=null;
    $values['dateCompleted']=null;
    $values['repeat']=null;
    $values['suppressUntil']=null;
    $values['type']=$_REQUEST['type'];
    $values['isSomeday']=(isset($_GET['someday']) &&  $_GET['someday']=='true')?'y':'n';
    $nextaction=isset($_REQUEST['nextonly']) && ($_REQUEST['nextonly']=='true' || $_REQUEST['nextonly']==='y');
    foreach ( array('category','context','timeframe') as $cat)
        $values[$cat.'Id']= (isset($_REQUEST[$cat.'Id']))?(int) $_REQUEST[$cat.'Id']:0;

    if ($values['type']==='s') {
        $values['isSomeday']='y';
        $values['type']='p';
    } elseif ($values['type']==='n') {
        $nextaction=true;
        $values['type']='a';
    }
}
$show=getShow($where,$values['type']);
if (!$values['itemId']) {
    if ($show['suppress'] && isset($_REQUEST['suppress']) && ($_REQUEST['suppress']=='true' || $_REQUEST['suppress']==='y')) {
        $values['suppress']='y';
        $values['suppressUntil']=$_REQUEST['suppressUntil'];
    } else $values['suppress']='n';
    if ($show['deadline'] && !empty($_REQUEST['deadline']))$values['deadline']=$_REQUEST['deadline'];
    $parents=array();
    if ($show['ptitle']   && !empty($_REQUEST['parentId'])) {
        $values['parentId']=explode(',',$_REQUEST['parentId']);
        foreach ($values['parentId'] as $parent) {
            $result=query("selectitemshort",$config,array('itemId'=>$parent),$sort);
            if ($result) {
                $newparent=array(
                     'parentId'=>$result[0]['itemId']
                    ,'ptitle'=>$result[0]['title']
                    ,'isSomeday'=>$result[0]['isSomeday']
                    ,'ptype'=>$result[0]['type']);
                $parents[]=$newparent;
            }
        }
    }
}

if (is_array($parents) && count($parents))
    foreach ($parents as $row)
        $values['parentId'][]=$row['parentId'];

if ($values['isSomeday']==="y")
    $typename="Someday/Maybe";
else
    $typename=getTypes($values['type']);
if ($nextaction) $typename="Next ".$typename;
//create filters for selectboxes
$values['timefilterquery'] = ($config['useTypesForTimeContexts'] && $values['type']!=='i')?" WHERE ".sqlparts("timetype",$config,$values):'';

//create item, timecontext, and spacecontext selectboxes
$cashtml = categoryselectbox($config,$values,$sort);
$cshtml = contextselectbox($config,$values,$sort);
$tshtml = timecontextselectbox($config,$values,$sort);

$oldtype=$values['type'];

//PAGE DISPLAY CODE
$title=(($values['itemId']>0)?'Edit ':'New ').$typename;

$hiddenvars=array(
            'referrer'=>(isset($_REQUEST['referrer']))?$_REQUEST['referrer']:''
            ,'type'   =>$values['type']
            ,'itemId' =>$values['itemId']
            );
            
if ($values['itemId']) {
    $hiddenvars['action']='fullUpdate';
} else
    $hiddenvars['action']='create';

$ptypes=getParentType($values['type']);
if ($_SESSION['useLiveEnhancements']) {
    $alltypes=getTypes();
    $allowedSearchTypes=array();
    if (count($ptypes)>1) $allowedSearchTypes[0]='All';
    foreach($ptypes as $ptype)
        $allowedSearchTypes[$ptype]=$alltypes[$ptype].'s';
    $values['ptypefilterquery']=" AND ia.`type` IN ('".implode("','",$ptypes)."') ";
    $potentialparents = query("parentselectbox",$config,$values,$sort);
    if (!$potentialparents) $potentialparents=array();
} elseif (count($ptypes))
    $values['ptypefilterquery']=" AND ia.`type`='{$ptypes[0]}' ";
if (count($ptypes)) $values['ptype']=$ptypes[0];
?><h2><?php
    if ($values['itemId'])
        echo "\n<a href='itemReport.php?itemId={$values['itemId']}'>"
            ,"<img src='themes/{$config['theme']}/report.gif' class='noprint' "
            ,"alt='Report' title='View Report' /></a>\n";
    echo $title;
?></h2><?php
if (!empty($_REQUEST['createnote'])) { ?>
    <p class='warning'>Notes have been superseded by tickler actions. These actions get
    suppressed until a specified number of days before their deadlines</p>
<?php }
$canchangetypesafely=array('a','r','w');
$sep='<p>';
if (in_array($values['type'],$canchangetypesafely) && $values['itemId'])
    foreach ($canchangetypesafely as $totype)
        if ($totype!==$values['type']) {
            echo "$sep <a href='processItems.php?action=changeType&amp;itemId="
                ,$values['itemId'],"&amp;referrer=item.php?itemId=",$values['itemId']
                ,"&amp;safe=y&amp;type=$totype&amp;isSomeday="
                ,$values['isSomeday']
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
<form action="processItems.php" method="post" onsubmit="return validate(this);"><div class='form'>
    <div class='formrow'><span class="error" id='errorMessage'></span></div>
        <?php if($show['title']) { ?>
            <div class='formrow'>
                    <label for='title' class='left first'>Title:</label>
                    <input type="text" name="title" id="title" value="<?php echo makeclean($values['title']); ?>" />
            </div>
        <?php } else $hiddenvars['title']=$values['title'];

        if ($show['ptitle']) { ?>
            <div class='formrow'>
                <label for='parenttable' class='left first'>Parent(s):</label>
                <?php if ($_SESSION['useLiveEnhancements']) {
                    include_once('liveParents.inc.php');
                } else { ?>
                    <select name="parentId[]" id='parenttable' multiple="multiple" size="6">
                        <?php echo parentselectbox($config,$values,$sort); ?>
                    </select>
                <?php } ?>
            </div>
        <?php } elseif (is_array($parents))
            foreach ($values['parentId'] as $parent)
                echo hidePostVar('parentId[]',$parent);
        ?><div class='formrow'>
            <?php if ($show['category']) { ?>
                <label for='category' class='left first'>Category:</label>
                <select name='categoryId' id='category'>
                <?php echo $cashtml; ?>
                </select>
            <?php } else $hiddenvars['categoryId']=$values['categoryId'];
            if ($show['context']) { ?>
                <label for='context' class='left'>Context:</label>
                <select name='contextId' id='context'>
                <?php echo $cshtml; ?>
                </select>
            <?php } else $hiddenvars['contextId']=$values['contextId'];
            if ($show['timeframe']) { ?>
                <label for='timeframe' class='left'>Time:</label>
                <select name='timeframeId' id='timeframe'>
                <?php echo $tshtml; ?>
                </select>
            <?php } else $hiddenvars['timeframeId']=$values['timeframeId']; ?>
        </div>
        <div class='formrow'>
            <?php if ($show['deadline']) { ?>
                <label for='deadline' class='left first'>Deadline:</label>
                <input type='text' size='10' name='deadline' id='deadline' class='hasdate' value='<?php echo $values['deadline']; ?>'/>
                <button type='reset' id='deadline_trigger'>...</button>
                    <script type='text/javascript'>
                        Calendar.setup({
							firstDay    :   <?php echo (int) $config['firstDayOfWeek']; ?>,
                            inputField  :	'deadline',	  // id of the input field
                            ifFormat	:	'%Y-%m-%d',	   // format of the input field
                            showsTime	:	false,			// will display a time selector
                            button		:	'deadline_trigger',   // trigger for the calendar (button ID)
                            singleClick	:	true,		   // single-click mode
                            step		:	1				// show all years in drop-down boxes (instead of every other year as default)
                        });
                    </script>
            <?php } else $hiddenvars['deadline']=$values['deadline'];
            if ($show['dateCompleted']) { ?>
                <label for='dateCompleted' class='left'>Completed:</label><input type='text' size='10' class='hasdate' name='dateCompleted' id='dateCompleted' value='<?php echo $values['dateCompleted'] ?>'/>
                <button type='reset' id='dateCompleted_trigger'>...</button>
                    <script type='text/javascript'>
                        Calendar.setup({
							firstDay    :    <?php echo (int) $config['firstDayOfWeek']; ?>,
                            inputField	:	'dateCompleted',	  // id of the input field
                            ifFormat	:	'%Y-%m-%d',	   // format of the input field
                            showsTime	:	false,			// will display a time selector
                            button		:	'dateCompleted_trigger',   // trigger for the calendar (button ID)
                            singleClick	:	true,		   // single-click mode
                            step		:	1				// show all years in drop-down boxes (instead of every other year as default)
                        });
                    </script>
				<button type='button' id='dateCompleted_today' onclick="javascript:completeToday('dateCompleted');">Today</button>
            <?php } else $hiddenvars['dateCompleted']=$values['dateCompleted']; ?>
        </div>
        <?php if ($show['description']) { ?>
            <div class='formrow'>
                    <label for='description' class='left first'>Description:</label>
                    <textarea rows='12' cols='50' name='description' id='description'><?php echo makeclean($values['description']); ?></textarea>
            </div>
        <?php } else $hiddenvars['description']=$values['description'];
        if ($show['desiredOutcome']) { ?>
            <div class='formrow'>
                    <label for='outcome' class='left first'>Desired Outcome:</label>
                    <textarea rows='4'  cols='50' name='desiredOutcome' id='outcome' class='big'><?php echo makeclean($values['desiredOutcome']); ?></textarea>
            </div>
        <?php
        } else $hiddenvars['desiredOutcome']=$values['desiredOutcome'];
        if ($show['repeat']) { ?>
            <div class='formrow'>
                    <label for='repeat' class='left first'>Repeat every&nbsp;</label><input type='text' name='repeat' id='repeat' size='3' value='<?php echo $values['repeat']; ?>' /><label for='repeat'>&nbsp;days</label>
            </div>
        <?php } else $hiddenvars['repeat']=$values['repeat'];
        if ($show['suppress']) { ?>
            <div class='formrow'>
                    <label for='suppress' class='left first'>Tickler:</label>
                    <input type='checkbox' name='suppress' id='suppress' value='y' title='Temporarily puts this into the tickler file, hiding it from the active view' <?php if ($values['suppress']=="y") echo " checked='checked' "; ?>/>
                    <label for='suppressUntil'>Tickle&nbsp;</label>
                    <input type='text' size='3' name='suppressUntil' id='suppressUntil' value='<?php echo $values['suppressUntil'];?>' /><label for='suppressUntil'>&nbsp;days before deadline</label>
            </div>
        <?php } else {
            $hiddenvars['suppress']=$values['suppress'];
            $hiddenvars['suppressUntil']=$values['suppressUntil'];
        } ?>
        <div class='formrow'>
            <?php if ($show['NA']) { ?>
                <label for='nextAction' class='left first'>Next Action:</label><input type="checkbox" name="nextAction" id="nextAction" value="y" <?php if ($nextaction) echo " checked='checked'"; ?> />
            <?php } else $hiddenvars['nextAction']=($nextaction)?'y':'';
            if ($show['isSomeday']) { ?>
                <label for='isSomeday' class='left first'>Someday:</label><input type='checkbox' name='isSomeday' id='isSomeday' value='y' title='Places item in Someday file'<?php if ($values['isSomeday']==='y') echo " checked='checked'";?> />
            <?php } else $hiddenvars['isSomeday']=$values['isSomeday']; ?>
        </div>
    	<input type='hidden' name='required'
    	value='title:notnull:Title can not be blank.,deadline:date:Deadline must be a valid date.,dateCompleted:date:Completion date is not valid.,suppress:depends:You must set a deadline to base the tickler on:deadline,suppress:depends:You must set a number of days for the tickler to be active from:suppressUntil' />
    	<input type='hidden' name='dateformat' value='ccyy-mm-dd' />
<?php
if (!$values['itemId']) $hiddenvars['lastcreate']=$_SERVER['QUERY_STRING'];
foreach ($hiddenvars as $key=>$val) echo hidePostVar($key,$val);
$key='afterCreate'.$values['type'];
// always use config value when creating
if (isset($config['afterCreate'][$values['type']]) && !isset($_SESSION[$key]))
	$_SESSION[$key]=$config['afterCreate'][$values['type']];
	
if ($values['itemId'])
    $tst=$_SESSION[$key];
else
    $tst=$config['afterCreate'][$values['type']];

echo "<div class='formrow'>\n<label class='left first'>After "
    ,($values['itemId'])?'updating':'creating'
    ,":</label>\n";
    
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
if (!$values['itemId'] && $values['type']==='p')
    echo "<input type='radio' name='afterCreate' id='childNext' value='child' class='notfirst'"
	 	,($tst=='child')?" checked='checked' ":""
		," /><label for='childNext' class='right'>Create a child action</label>\n";

if (!empty($hiddenvars['referrer']) || !empty($_SESSION[$key])) {
    echo "<input type='radio' name='afterCreate' id='referrer' value='referrer' class='notfirst'"
	 	,($tst=='referrer')?" checked='checked' ":''
		," /><label for='referrer' class='right'>Return to previous list</label>\n";
}

echo "</div>\n</div> <!-- form div -->\n<div class='formbuttons'>\n"
    ,"<input type='submit' value='"
    ,($values['itemId'])?"Update $typename":'Create'
    ,"' name='submit' />\n"
    ,"<input type='reset' value='Reset' />\n";
if ($values['itemId']) {
    echo "<input type='checkbox' name='delete' id='delete' value='y' title='Deletes item. Child items are orphaned, NOT deleted.'/>\n"
        ,"<label for='delete'>Delete&nbsp;$typename</label>\n"
        ,"<input type='hidden' name='oldtype' value='$oldtype' />\n";
}
echo "</div>\n</form>\n";

if ($values['itemId']) {
        echo "	<div class='details'>\n";
        echo "		<span class='detail'>Date Added: ".$values['dateCreated']."</span>\n";
        echo "		<span class='detail'>Last Modified: ".$values['lastModified']."</span>\n";
        echo "	</div>\n";
}
if ($_SESSION['useLiveEnhancements'] && !empty($values['ptype'])) {
    include_once ('searcher.inc.php');
    $partt= $ptitle= $pid ='new Array(';
    $sep   ='';
    foreach ($potentialparents as $oneparent) {
        $pid   .=$sep.'"'.$oneparent['itemId'].'"';
        $ptitle.=$sep.'"'.rawurlencode($oneparent['title']).'"';
        $partt .=$sep.'"'
                .(($oneparent['isSomeday']==='y')?'s':$oneparent['type'])
                .'"';
        $sep    =',';
    }
    $pid   .=')';
    $ptitle.=')';
    $partt .=')';
    if (count($allowedSearchTypes)===1) $partt='""';
    // TOFIX - this javascript is very probably inefficient, but I don't have the resources to optimise it
    ?><script type='text/javascript'>
        /* <![CDATA[ */
        addEvent(window,'load',function() {
            var types=new Object();
            <?php
                foreach ($alltypes as $key=>$val)
                    echo "types['$key']='$val';\n";
            ?>
            mapTypeToName(types);
            gtd_searchdiv_init(
                <?php echo "$pid\n,\n$ptitle\n,\n$partt\n,\"{$values['ptype']}\" \n"; ?>
            );
            gtd_refinesearch('<?php echo $values['ptype']; ?>');
        });
        /* ]]> */
    </script><?php
    }
include_once('footer.php');
function hidePostVar($name,$val) {
    $val=makeclean($val);
    return "<input type='hidden' name='$name' value='$val' />\n";
}
?>
