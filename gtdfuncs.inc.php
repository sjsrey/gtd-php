<?php
include_once 'gtd_constants.inc.php';
/*
   ======================================================================================
*/
function escapeforjavascript($in) {
    $out=str_replace(
        array('\\'   , '"'  , '/'   ),
        array('\\\\' , '\\"', '\\/' )
        ,$in);
    return $out;
}
/*
   ======================================================================================
*/
function getEvents($addon) {
    if (   (@include "./addons/$addon/setup.inc.php")===false
        || !isset($events)) return false;
    $_SESSION["addons-$addon"]=array(); // this array will store the options for the addon
    $triggercount=0;
    foreach ($events as $trigger=>$what) {
        foreach ($what as $page=>$handler) {
            if (is_array($handler) && array_key_exists('options',$handler)) {
                // options are present, so store them in the session global, indexed by addon name, event trigger, and page name
                // TOFIX - does not get saved to db.  Should we redo this at the start of each session?
                $_SESSION["addons-$addon"]["$trigger-$page"]=$handler['options']; 
                unset($handler['options']);
            }
            $_SESSION['addons'][$trigger][$page][$addon]=$handler;
            $triggercount++;
        }
    }
    $_SESSION['addons'][$addon]=true;
    return $triggercount;
}
/*
   ======================================================================================
*/
function gtd_handleEvent($event,$page) {
    $eventhandlers=@array_merge((array)$_SESSION['addons'][$event]['*'],
                                (array)$_SESSION['addons'][$event][$page]
                                );
    foreach ($eventhandlers as $addonid=>$handler) {
        $addon=array('id'=>$addonid,'dir'=>"./addons/$addonid/");
        if ((include "{$addon['dir']}$handler")===false) break;
    }
}
/*
   ======================================================================================
*/
function query($querylabel,$values=NULL,$sort=NULL) {
    if (!empty($_SESSION['debug']['debug'])) {
        echo "<p class='debug'><b>Query label: ".$querylabel."</b></p>"
            ,"<pre>Values: ",print_r($values,true)
            ,"<br />Sort: ",print_r($sort,true),"</pre>";
    }

    if (empty($sort)) {
        $sort=$_SESSION['sort'];
    }
    //grab correct query string from query library array
    //values automatically inserted into array
    $query=getsql($querylabel,$values,$sort);

    // for testing only: display fully-formed query
    if ($_SESSION['debug']['debug']) echo "<p class='debug'>Query: $query</p>";

    //perform query
	$result=doQuery($query,$querylabel);

    //for developer testing only, print result array
    if ($_SESSION['debug']['debug']) {
        echo "<pre>Result: ";
        print_r($result);
        echo "</pre>";
        }
    return $result;
}
/*
   ======================================================================================
*/
function makeClean($textIn) {
    if (is_array($textIn)) {
        $cleaned=array();
        foreach ($textIn as $line) $cleaned[]=makeClean($line);
	} else {
        $cleaned=htmlentities(stripslashes($textIn),ENT_QUOTES,$_SESSION['config']['charset']);
    }
	return $cleaned;
}
/*
   ======================================================================================
*/
function trimTaggedString($inStr,$inLength=0,$keepTags=TRUE) { // Ensure the visible part of a string, excluding html tags, is no longer than specified) 	// TOFIX -  we don't handle "%XX" strings yet.
	// constants - might move permittedTags to config file
	// TOFIX - doesn't handle MBCS!
	$permittedTags=array(
		 array('/^<a ((href)|(file))=[^>]+>/i','</a>')
		,array('/^<b>/i','</b>')
		,array('/^<i>/i','</i>')
		,array('/^<ul>/i','</ul>')
		,array('/^<ol>/i','</ol>')
		,array('/^<li>/i','</li>')
		);
	$ellipsis='&hellip;';
	$ampStrings='/^&[#a-zA-Z0-9]+;/';
	
	// initialise variables
	if ($inLength==0) $inLength=strlen($inStr)+1;
	$outStr='';
	$visibleLength=0;
	$thisChar=0;
	$keepGoing=!empty($inStr);
	$tagsOpen=array();
	// main processing here
	while ($keepGoing) {
		$stillHere = TRUE;
		$tagToClose=end($tagsOpen);
		if ($tagToClose && strtolower(substr($inStr,$thisChar,strlen($tagToClose)))===strtolower($tagToClose) ) {
			$stillHere=FALSE;
			$thisChar+=strlen($tagToClose);
			if ($keepTags) $outStr.=array_pop($tagsOpen);
		} else foreach ($permittedTags as $thisTag) {
			if ($stillHere && ($inStr{$thisChar}==='<') && (preg_match($thisTag[0],substr($inStr,$thisChar),$matches)>0)) {
				$thisChar+=strlen($matches[0]);
				$stillHere=FALSE;
				if ($keepTags) {
					array_push($tagsOpen,$thisTag[1]);
					$outStr.=$matches[0];
				}
			} // end of if
		} // end of else foreach
		// now check for & ... control characters
		if ($stillHere && ($inStr{$thisChar}==='&') && (preg_match($ampStrings,substr($inStr,$thisChar),$matches)>0)) {
			if (strlen(html_entity_decode($matches[0]))==1) {
				$visibleLength++;
				$outStr.=$matches[0];
				$thisChar+=strlen($matches[0]);
				$stillHere=FALSE;
			}
		}
		// just a normal character, so add it to the string
		if ($stillHere) {
			$visibleLength++;
			$outStr.=$inStr{$thisChar};
			$thisChar++;
		} // end of if
		$keepGoing= (($thisChar<strlen($inStr)) && ($visibleLength<$inLength));
	} // end of while ($keepGoing)
	// add ellipsis if we have trimmed some text
	if ($thisChar<strlen($inStr) && $visibleLength>=$inLength) $outStr.=$ellipsis;
	// got the string - now close any open tags
	if ($keepTags) while (count($tagsOpen))
		$outStr.=array_pop($tagsOpen);
	$outStr=nl2br(escapeChars($outStr));
	return($outStr);
}
/*
   ======================================================================================
*/
function getTickleDate($deadline,$days) { // returns unix timestamp of date when tickle becomes active
	$dm=(int)substr($deadline,5,2);
	$dd=(int)substr($deadline,8,2);
	$dy=(int)substr($deadline,0,4);
	// relies on PHP to sanely and clevery handle dates like "the -5th of March" or "the 50th of April"
	$remind=mktime(0,0,0,$dm,($dd-$days),$dy);
	return $remind;
}
/*
   ======================================================================================
*/
function nothingFound($message, $prompt=NULL, $yeslink=NULL, $nolink="index.php"){
        //Give user ability to create a new entry, or go back to the index.
        echo "<h4>$message</h4>";
        if($prompt)
            echo "<p>$prompt;<a href='$yeslink'> Yes </a><a href='$nolink'>No</a></p>\n";
}
/*
   ======================================================================================
*/
function categoryselectbox($values) {
    $result = query("categoryselectbox",$values);
    $cashtml='<option value="0">--</option>'."\n";
    if ($result) {
        foreach($result as $row) {
            $cashtml .= '   <option value="'.$row['categoryId'].'" title="'.makeclean($row['description']).'"';
            if($row['categoryId']==$values['categoryId']) $cashtml .= ' selected="selected"';
            $cashtml .= '>'.makeclean($row['category'])."</option>\n";
            }
        }
    return $cashtml;
    }
/*
   ======================================================================================
*/
function contextselectbox($values) {
    $result = query("spacecontextselectbox",$values);
    $cshtml='<option value="0">--</option>'."\n";
    if ($result) {
            foreach($result as $row) {
            $cshtml .= '                    <option value="'.$row['contextId'].'" title="'.makeclean($row['description']).'"';
            if($row['contextId']==$values['contextId']) $cshtml .= ' selected="selected"';
            $cshtml .= '>'.makeclean($row['name'])."</option>\n";
            }
        }
    return $cshtml;
    }
/*
   ======================================================================================
*/
function timecontextselectbox($values) {
    $result = query("timecontextselectbox",$values);
    $tshtml='<option value="0">--</option>'."\n";
    if ($result) {
        foreach($result as $row) {
            $tshtml .= '                    <option value="'.$row['timeframeId'].'" title="'.makeclean($row['description']).'"';
            if($row['timeframeId']==$values['timeframeId']) $tshtml .= ' selected="selected"';
            $tshtml .= '>'.makeclean($row['timeframe'])."</option>\n";
            }
        }
    return $tshtml;
    }
/*
   ======================================================================================
*/
function makeOption($row,$selected) {
    $cleandesc=makeclean($row['description']);
    $cleantitle=makeclean($row['title']);
    if ($row['isSomeday']==="y") {
        $cleandesc.=' (Someday)';
        $cleantitle.=' (S)';
    }
    $seltext = ($selected[$row['itemId']])?' selected="selected"':'';
    $out = "<option value='{$row['itemId']}' title='$cleandesc' $seltext>$cleantitle</option>";
    return $out;
}
/*
   ======================================================================================
*/
function prettyDueDate($dateToShow,$daysdue,$thismask=null) {
    if (is_null($thismask)) $thismask=$_SESSION['config']['datemask'];
	$retval=array('class'=>'','title'=>'');
    if(trim($dateToShow)!='') {
        $timestamp=strtotime($dateToShow);
        $retval['date'] = date($thismask,$timestamp );
        if ($daysdue>0) {
            $retval['class']='overdue';
            $retval['title']="$daysdue day(s) overdue";
        } elseif(!$daysdue) {
            $retval['class']='due';
            $retval['title']='Due today';
        } elseif ($daysdue==-1) {
            $retval['title']='Due tomorrow';
            $retval['class']='comingdue';
        } elseif ($daysdue>-8) {
            $retval['title']='Due in '.-$daysdue.' days';
            $retval['class']='comingdue';
        } else {
            $retval['title']='Due in '.-$daysdue.' days';
        }
        if ($_SESSION['config']['showRelativeDeadlines']) {
            $dateOrig = $retval['date'];
            $retval['date'] = $retval['title'];
            $retval['title'] = $dateOrig;
        }
    } else
        $retval['date'] ='&nbsp;';
	return $retval;
}
/*
   ======================================================================================
*/
function getVarFromGetPost($varName,$default='') {
	$retval=(isset($_GET[$varName]))?$_GET[$varName]:( (isset($_POST[$varName]))?$_POST[$varName]:$default );
	return $retval;
}
/*
   ======================================================================================
*/
function nextScreen($url) {
    $cleanurl=htmlspecialchars($url);
    if ($_SESSION['debug']['wait']) {
        echo "<p>Next screen is <a href='$cleanurl'>$cleanurl</a> - would be auto-refresh in non-debug mode</p>";
    }elseif (headers_sent()) {
        echo "<META HTTP-EQUIV='Refresh' CONTENT='0;url=$cleanurl' />\n"
            ,"<script type='text/javascript'>window.location.replace('$cleanurl');</script>\n"
            ,"</head><body><a href='$cleanurl'>Click here to continue on to $cleanurl</a>\n";
    }else{
        $header="Location: http"
                .((empty($_SERVER['HTTPS']))?'':'s')
                ."://"
                .$_SERVER['HTTP_HOST']
                .rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
                .'/'.$url;
        header($header);
        exit;
    }
}
/*
  ==============================================================================
        functions for handling levels of the hierarchy
*/
function getTypes($type=false) {
    if ($type===false)
        $out=$_SESSION['hierarchy']['names'];
    elseif (empty($type))
        $out='item without a type assigned';
    elseif ($type==='*')
        $out='item';
    else
        $out=$_SESSION['hierarchy']['names'][$type];
    return $out;
}
//----------------------------------------------------------------
function getChildType($parentType) {
    if ($parentType==='*')
        return '';
    else
        return $_SESSION['hierarchy']['children'][$parentType];
}
//----------------------------------------------------------------
function getParentType($childType) {
    if ($childType==='*')
        return '';
    else
        return $_SESSION['hierarchy']['parents'][$childType];
}
//----------------------------------------------------------------
function getShow($where,$type) {
    $show=array(
        'title'         => true,
        'description'   => true,

        // only show if editing, not creating
        'lastModified'  =>($where==='edit'),
        'dateCreated'   =>($where==='edit'),
        'type'          =>($where==='edit' && ($type==='i' || $_SESSION['config']['allowChangingTypes'])),

        // fields suppressed on certain types
        'desiredOutcome'=>($type!=='r' && $type!=='L' && $type!=='C' && $type!=='T'),
        'category'      =>($type!=='m' && $type!=='C' && $type!=='T'),
        'ptitle'        =>($type!=='m' && $type!=='i'),
        'dateCompleted' =>($type!=='m' && $type!=='L' && $type!=='C'),

        // fields only shown for certain types
        'timeframe'     =>($type==='i' || $type==='a' || $type==='p' || $type==='g'  || $type==='o' || $type==='v'),
        'context'       =>($type==='i' || $type==='a' || $type==='w' || $type==='r'),
        'deadline'      =>($type==='p' || $type==='a' || $type==='w' || $type==='i'),
        'tickledate'    =>($type==='p' || $type==='a' || $type==='w'),
        'recurdesc'     =>($type==='p' || $type==='a' || $type==='g'),
        'NA'            =>($type==='a' || $type==='w'),
        'isSomeday'     =>($type==='p' || $type==='g'),

        // fields never shown on item.php
        'checkbox'      => false,
        'flags'         => false
        );

    if ($_SESSION['config']['forceAllFields'])
        foreach ($show as $key=>$value)
            $show[$key]=true;

    return $show;
}
//----------------------------------------------------------------
function mirrorParentTypes() {
    foreach ($_SESSION['hierarchy']['children'] as $parent=>$children)
        $_SESSION['hierarchy']['parents'][$parent]=array();
    foreach ($_SESSION['hierarchy']['children'] as $parent=>$children)
        if (!empty($children))
            foreach ($children as $child)
                $_SESSION['hierarchy']['parents'][$child][]=$parent;
}
//----------------------------------------------------------------
function resetHierarchy() {
    $_SESSION['hierarchy']=array();
    resetHierarchyNames();
    $_SESSION['hierarchy']['suppressAsOrphans']='imLC';

    $_SESSION['hierarchy']['children']=array(
        'T'=>array(),
        'L'=>array('T'),
        'C'=>array('T'),
        'a'=>array(),
        'w'=>array(),
        'r'=>array(),
        'i'=>array(),
        'p'=>array('p','a','w','r','i','C','L'),
        'g'=>array('p','L','C'),
        'o'=>array('g','p','L','C'),
        'v'=>array('o','g','L','C'),
        'm'=>array('v','o')
    );
    mirrorParentTypes();
}
//----------------------------------------------------------------
function resetHierarchyNames() {
    $_SESSION['hierarchy']['names']=array(
        'm' => 'value',
        'v' => 'vision',
        'o' => 'role',
        'g' => 'goal',
        'p' => 'Project',
        'a' => 'Action',
        'i' => 'Inbox Item',
        's' => 'Someday/Maybe',
        'r' => 'Reference',
        'w' => 'Waiting On',
        'L' => 'List',
        'C' => 'Checklist',
        'T' => 'List item'
    );
}
//----------------------------------------------------------------
function parentselectbox($values) {
    $result = query("parentselectbox",$values);
    $pshtml='';
    $parents=array();
    if (is_array($values['parentId']))
        foreach ($values['parentId'] as $key) $parents[$key]=true;
    else
        $parents[$values['parentId']]=true;
    if ($_SESSION['debug']['debug']) echo '<pre>parents:',print_r($parents,true),'</pre>';
    if ($result)
        foreach($result as $row) {
            $thisOpt= makeOption($row,$parents)."\n";
            if($parents[$row['itemId']]) {
                $pshtml =$thisOpt.$pshtml;
                $parents[$row['itemId']]=false;
            } else
                $pshtml .=$thisOpt;
        }
    foreach ($parents as $key=>$val) if ($val) {
        // $key is a parentId which wasn't found for the drop-down box, so need to add it in
        $values['itemId']=$key;
        $row=query('selectitemshort',$values);
        if ($row) $pshtml = makeOption($row[0],$parents)."\n".$pshtml;
    }
    $pshtml="<option value='0'>--</option>\n".$pshtml;
    return $pshtml;
}
//----------------------------------------------------------------
function getOrphans() { // retrieve all orphans - items without a parent assigned
    // we only want orphans of specific types, as specified by the user in the preferences screen
    $values=array('orphansfilterquery'
            =>sqlparts(
                'orphantypes',
                array('suppressAsOrphans'=>$_SESSION['hierarchy']['suppressAsOrphans'])
            )
        );
    $maintable = query("getorphaneditems",$values);
    return $maintable;
}
/*
        end of hierarchy handling
    =======================================================================
*/
function escapeChars($str) {  // TOFIX consider internationalization issues with charset coding
    $outStr=str_replace(array('&','…'),array('&amp;','&hellip'),$str);
    $outStr=str_replace(array('&amp;amp;','&amp;hellip;'),array('&amp;','&hellip;'),$outStr);
	return $outStr;
}
/*
   ======================================================================================
*/
function columnedTable($cols,$data,$link='itemReport.php') {
    $nrows=count($data);
    $displace=round($nrows/$cols+0.499,0);
    for ($i=0;$i<$nrows;) {
        echo "<tr>\n";
        for ($j=0;$j<$cols;$j++) {
            $ndx=$i/$cols+$j*$displace;
            if ($ndx<$nrows) {
                $row=$data[$ndx];
                echo "<td"
                    ,(empty($row['td.class'])) ? '' : " class='{$row['td.class']}' "
                    ,(empty($row['td.title'])) ? '' : " title='{$row['td.title']}' "
                    ,"><a href='$link?itemId={$row['itemId']}' title='"
                    ,makeclean($row['description']),"'>"
                    ,makeclean($row['title']),"</a></td>\n";
            }
        }
        echo "</tr>\n";
        $i+=$cols;
    }
}
/*
   ======================================================================================
    get the next date of a recurring item
*/
function getNextRecurrence($values) { // returns false if failed, else returns timestamp of next recurrence
    require_once 'iCalcreator.class.inc.php';

    if ($_SESSION['debug']['debug']) echo "<p class='debug'>creating vcalendar to get recurrence date";
    $vcal = new vcalendar();
    $vevent = new vevent();
    $vevent->parse(array('RRULE:'.$values['recur']));
    $rrule=$vevent->getProperty('rrule');

    if (preg_match("/^FREQ=(YEARLY|MONTHLY|WEEKLY|DAILY);INTERVAL=[0-9]+$/",$values['recur'])) {
        // very simple recurrence, so recur from dateCompleted
        if ($_SESSION['debug']['debug']) echo "<br />recur from date completed - simple recurrence";
        $startdate=$values['dateCompleted'];
    } else if (empty($values['deadline']) || $values['deadline']==='NULL') {
        //no deadline, so recur from tickler if available, and fall back to date completed
        $startdate=(empty($values['tickledate']))
            ? $values['dateCompleted']
            : $values['tickledate'];
    } else {
        // recur from deadline
        $startdate=$values['deadline'];
    }
    
    if (empty($startdate) || $startdate==='NULL') {
        // if we still haven't got a start date, use today
        $startdate=date('Y-m-d');
    } else
        $startdate=str_replace("'",'',$startdate);
        
    if ($_SESSION['debug']['debug'])
        echo "<br />recur='{$values['recur']}'<br />rrule=",print_r($rrule,true)
            ,"<br />start date (dirty)=",print_r($startdate,true);
    $startdate=$vcal->validDate($startdate);
    if ($_SESSION['debug']['debug']) echo "<br />",print_r($startdate,true);
    $vevent->setProperty( "dtstart",$startdate);

    if (empty($rrule['UNTIL'])) {
        $enddate=strtotime('+10 years');
        $enddate=date('Y-m-d',$enddate);
    } else
        $enddate=$rrule['UNTIL'];
    $enddate=$vcal->validDate($enddate);
    $rrule['COUNT']=2; // 2 = start date + next recurrence
    if (isset($rrule['UNTIL'])) unset($rrule['UNTIL']);
    $vevent->_recur2Date($recurlist,$rrule,
        $startdate,    // start date of item
        $startdate,    // start date of interval we're interested in
        $enddate       // end date
    );
    if (empty($recurlist)) {
        $nextdue=false;
    } else {
        $nextdue=date('Y-m-d',array_shift(array_keys($recurlist))); // get first key in returned array - that's the date
    }
    if ($_SESSION['debug']['debug']) echo "<br />next date=$nextdue</p>";
    return $nextdue;
}
/*
   ======================================================================================
*/
function importOldConfig() {
    // get preferences from old config.php file
    define('_GTD_WAIT'    ,1);
    define('_GTD_DEBUG'   ,2);
    define('_GTD_FREEZEDB',4);
    define('_GTD_NOTICE'  ,8);
    if (    (false===(@include 'config.php') || !isset($config) )
         && (false===(@include 'defaultconfig.inc.php')  || !isset($config) )
       ) {
        return false;
    }
    unset($config['pass']); // stop the password leaking anywhere
    $_SESSION['theme']=$config['theme'];
    $_SESSION['useLiveEnhancements']=(true && $config['useLiveEnhancements']); // force to boolean

    $_SESSION['debug']=array(
        'key'=>$config['debugKey'],
        'wait'=>true && ($config['debug'] & _GTD_WAIT),
        'debug'=>true && ($config['debug'] & _GTD_DEBUG),
        'freeze'=>true && ($config['debug'] & _GTD_FREEZEDB),
        'notice'=>true && ($config['debug'] & _GTD_NOTICE),
    );

    // we don't want to import the database login variables - they live in config.inc.php
    foreach (array('db','user','host','prefix','dbtype','theme','useLiveEnhancements','debug','debugKey') as $key)
        unset($config[$key]);

    // save preferences in session variables
    $_SESSION['config']=$config;
    $_SESSION['sort']=$sort;
    $_SESSION['keys']=array();
    foreach ($acckey as $link=>$key)
        if (!empty($key)) $_SESSION['keys'][$link]=$key;
    $_SESSION['config']['contextsummary']=($config['contextsummary']==='nextaction'); // force to boolean
    if (!array_key_exists('showRelativeDeadlines',$_SESSION['config']))
        $_SESSION['config']['showRelativeDeadlines']=false; // new value that may not be present in config.php
    resetHierarchy();
    $alltypes=getTypes();

    preg_match_all('/[mvogsparwi]/',$config['suppressAsOrphans'],$tst);
    $_SESSION['hierarchy']['suppressAsOrphans']=implode('',$tst[0]).'LC';
        
    foreach ($alltypes as $type=>$typename) {
        $_SESSION['config']["afterCreate$type"]=(isset($config['afterCreate'][$type]))
                ? $config['afterCreate'][$type]
                : 'item';
        unset($config['afterCreate'][$type]);
    }
    $_SESION['addons']=array();
    $values=array('uid'=>0,'option'=>'addons','config'=>serialize($_SESION['addons']));
    query('updateoptions',$values);
    
    if (!isset($custom_review)) $custom_review='';
    $values=array('uid'=>0,'option'=>'customreview','config'=>serialize($custom_review));
    query('updateoptions',$values);

    $_SESSION['addons']=array();
    $result=saveConfig();
    return $result;
}
/*
   ======================================================================================
*/
function saveConfig() { // store config preferences in the table
    $tst=query('updateconfig',
        array('config' =>serialize($_SESSION['config']),
            'sort'     =>serialize($_SESSION['sort']),
            'keys'     =>serialize($_SESSION['keys']),
            'hierarchy'=>serialize($_SESSION['hierarchy']),
            'debug'    =>serialize($_SESSION['debug']),
            'addons'   =>serialize($_SESSION['addons'])
        )
    );
    return $tst;
}
/*
   ======================================================================================
*/
function retrieveConfig() {
    $optionarray=query('getoptions',array('uid'=>0,'filterquery'=>'') );
    if ($optionarray) foreach ($optionarray as $options)
        $_SESSION[$options['option']]=unserialize($options['value']);

    // retrieve cookie values, and overlay them onto preferences
    foreach ($_COOKIE as $key=>$val)
        if (!empty($key) && isset($_SESSION[$key]))
            $_SESSION['config'][$key]=$_SESSION[$key]=$val;

    // go through the list of installed addons, and register them
    foreach($_SESSION['addons'] as $addon=>$dummy)
        getEvents($addon);
}
/*
   ======================================================================================
*/
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
