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
function gtd_handleEvent($event,$page) {
    global $config;
    $eventhandlers=@array_merge((array)$config['events'][$event][$page],(array)$config['events'][$event]['*']);
    foreach ($eventhandlers as $thishandler) {
        $addonpath=dirname($thishandler).'/';
        include $thishandler;
    }
}
/*
   ======================================================================================
*/
function query($querylabel,$config,$values=NULL,$sort=NULL) {
    //for developer testing only--- testing data handling
    //testing passed variables
    if ($config['debug'] & _GTD_DEBUG) {
        echo "<p class='debug'><b>Query label: ".$querylabel."</b></p>";
        echo "<pre>Config: ";
        print_r($config);
        echo "<br />Values: ";
        print_r($values);
        echo "<br />Sort: ";
        print_r($sort);
        echo "</pre>";
    }

    //grab correct query string from query library array
    //values automatically inserted into array
    $query=getsql($config,$values,$sort,$querylabel);

    // for testing only: display fully-formed query
    if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>Query: ".$query."</p>";

    //perform query
	$result=doQuery($query,$querylabel);

    //for developer testing only, print result array
    if ($config['debug'] & _GTD_DEBUG) {
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
    global $config;
    if (is_array($textIn)) {
        $cleaned=array();
        foreach ($textIn as $line) $cleaned[]=makeClean($line);
	} else {
        $cleaned=htmlentities(stripslashes($textIn),ENT_QUOTES,$config['charset']);
    }
	return $cleaned;
}

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

function getTickleDate($deadline,$days) { // returns unix timestamp of date when tickle becomes active
	$dm=(int)substr($deadline,5,2);
	$dd=(int)substr($deadline,8,2);
	$dy=(int)substr($deadline,0,4);
	// relies on PHP to sanely and clevery handle dates like "the -5th of March" or "the 50th of April"
	$remind=mktime(0,0,0,$dm,($dd-$days),$dy);
	return $remind;
}

function nothingFound($message, $prompt=NULL, $yeslink=NULL, $nolink="index.php"){
        //Give user ability to create a new entry, or go back to the index.
        echo "<h4>$message</h4>";
        if($prompt)
            echo "<p>$prompt;<a href='$yeslink'> Yes </a><a href='$nolink'>No</a></p>\n";
}

function categoryselectbox($config,$values,$sort) {
    $result = query("categoryselectbox",$config,$values,$sort);
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

function contextselectbox($config,$values,$sort) {
    $result = query("spacecontextselectbox",$config,$values,$sort);
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

function timecontextselectbox($config,$values,$sort) {
    $result = query("timecontextselectbox",$config,$values,$sort);
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

function parentselectbox($config,$values,$sort) {
    $result = query("parentselectbox",$config,$values,$sort);
    $pshtml='';
    $parents=array();
    if (is_array($values['parentId']))
        foreach ($values['parentId'] as $key) $parents[$key]=true;
    else
        $parents[$values['parentId']]=true;
    if ($config['debug'] & _GTD_DEBUG) echo '<pre>parents:',print_r($parents,true),'</pre>';
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
        $row=query('selectitemshort',$config,$values,$sort);
        if ($row) $pshtml = makeOption($row[0],$parents)."\n".$pshtml;
    }
    $pshtml="<option value='0'>--</option>\n".$pshtml;
    return $pshtml;
}

function listselectbox($config,&$values,$sort,$check=NULL) { // NB $values is passed by reference
    $result = query("get{$check}lists",$config,array('filterquery'=>''),$sort);
    $lshtml='';
    if ($result) {
        foreach($result as $row) {
            $lshtml .= "<option value='{$row['id']}' title='".makeclean($row['description'])."'";
            if($row['id']==$values['id']) {
                $lshtml .= " selected='selected' ";
                $values['listTitle']=$row['title'];
            }
            $lshtml .= '>'.makeclean($row['title'])."</option>\n";
            }
        }
    return $lshtml;
    }

function prettyDueDate($dateToShow,$thismask) {
	$retval=array('class'=>'','title'=>'');
    if(trim($dateToShow)!='') {
        $retval['date'] = date($thismask,strtotime($dateToShow) );
        if ($dateToShow<date("Y-m-d")) {
            $retval['class']='overdue';
            $retval['title']='Overdue';
        } elseif($dateToShow===date("Y-m-d")) {
            $retval['class']='due';
            $retval['title']='Due today';
        }
    } else
        $retval['date'] ='&nbsp;';
	return $retval;
}

function getVarFromGetPost($varName,$default='') {
	$retval=(isset($_GET[$varName]))?$_GET[$varName]:( (isset($_POST[$varName]))?$_POST[$varName]:$default );
	return $retval;
}

function nextScreen($url) {
    global $config;
    $cleanurl=htmlspecialchars($url);
    if ($config['debug'] & _GTD_WAIT) {
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

function getChildType($parentType) {
switch ($parentType) {
    case "m" : $childtype=array("v","o","g"); break;
    case "v" : $childtype=array("o","g"); break;
    case "o" : $childtype=array("g","p","s"); break;
    case "g" : $childtype=array("p","s"); break;
    case "s" : // as case 'p'
    case "p" : $childtype=array("a","w","r","p","s",'L','C'); break;
    case "C" : // as case 'L'
    case "L" : $childtype=array("T"); break;
    default  : $childtype=NULL; break; // all other items have no children
    }
return $childtype;
}

function getParentType($childType) {
$parentType=array();
switch ($childType) {
    case "T" : $parentType=array('L','C');
        break;
    case 'L' : // deliberately flows through to "r"
    case 'C' : // deliberately flows through to "r"
    case "a" : // deliberately flows through to "r"
    case "w" : // deliberately flows through to "r"
    case "r" : $parentType=array('p','s');
        break;
    case "i" : $parentType=array();
        break;
    case "p" :  // deliberately flows through to "s"
    case "s" : $parentType=array('g','p','s','o');
        break;
    case "g" : $parentType[]='o'; // deliberately flows through to "v"
    case "o" : $parentType[]='v'; // deliberately flows through to "v"
    case "v" : $parentType[]='m';
        break;
    default  :
        $parentType=array('p','s');
        break;
    }
return $parentType;
}

function getTypes($type=false) {
$types=array("m" => "Value",
            "v" => "Vision",
            "o" => "Role",
            "g" => "Goal",
            "p" => "Project",
            "a" => "Action",
            "i" => "Inbox Item",
            "s" => "Someday/Maybe",
            "r" => "Reference",
            "w" => "Waiting On",
            "C" => "Checklist",
            "L" => "List",
            "T" => "(Check)List item"
        );
if ($type===false)
    $out=$types;
elseif (empty($type))
    $out='item without a type assigned';
elseif ($type==='*')
    $out='item';
else
    $out=$types[$type];
return $out;
}


function escapeChars($str) {  // TOFIX consider internationalization issues with charset coding
    $outStr=str_replace(array('&','…'),array('&amp;','&hellip'),$str);
    $outStr=str_replace(array('&amp;amp;','&amp;hellip;'),array('&amp;','&hellip;'),$outStr);
	return $outStr;
}

function getShow($where,$type) {
    global $config;
    $show=array(
        'title'         => true,
        'description'   => true,

        // only show if editing, not creating
        'lastModified'  =>($where==='edit'),
        'dateCreated'   =>($where==='edit'),
        'type'          =>($where==='edit' && ($type==='i' || $config['allowChangingTypes'])),

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

    if ($config['forceAllFields'])
        foreach ($show as $key=>$value)
            $show[$key]=true;
                
    return $show;
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
function getNextRecurrence() { // returns false if failed, else returns timestamp of next recurrence
    global $config,$values;
    require_once 'iCalcreator.class.inc.php';

    if ($config['debug'] & _GTD_DEBUG) echo "<p class='debug'>creating vcalendar to get recurrence date";
    $vcal = new vcalendar();
    $vevent = new vevent();
    $vevent->parse(array('RRULE:'.$values['recur']));
    $rrule=$vevent->getProperty('rrule');

    if (preg_match("/^FREQ=(YEARLY|MONTHLY|WEEKLY|DAILY);INTERVAL=[0-9]+$/",$values['recur'])) {
        // very simple recurrence, so recur from dateCompleted
        if ($config['debug'] & _GTD_DEBUG) echo "<br />recur from date completed - simple recurrence";
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
        
    if ($config['debug'] & _GTD_DEBUG)
        echo "<br />recur='{$values['recur']}'<br />rrule=",print_r($rrule,true)
            ,"<br />start date (dirty)=",print_r($startdate,true);
    $startdate=$vcal->validDate($startdate);
    if ($config['debug'] & _GTD_DEBUG) echo "<br />",print_r($startdate,true);
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
    if ($config['debug'] & _GTD_DEBUG) echo "<br />next date=$nextdue</p>";
    return $nextdue;
}
/*
   ======================================================================================
*/
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
