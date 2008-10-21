<?php
include_once 'gtd_constants.inc.php';
/*
   ======================================================================================
   functions for logging debug text in a secure, HTML-valid form
*/
if (empty($_SESSION['debug']['debug'])) {
    function log_value(){}
    function log_text(){}
    function log_array(){}
} else {
    function log_array($varlist) { // dump a variable name and its contents
        global $log_count;
        if (!isset($log_count)) $log_count=0;

    	if (!is_array($varlist)) $varlist=func_get_args();

        if(array_key_exists('tag',$varlist)) {
            $tag=$varlist['tag'];
            unset($varlist['tag']);
        } else $tag='pre'; // default tag to wrap debug-log is PRE

        foreach ($varlist as $pretext=>$varname) {
            echo "<$tag class='debug'>"
                ,"<a name='log$log_count' id='log$log_count'></a>"
                ,($log_count) ? '<a href="#log'.(-1+$log_count).'">&uarr;</a>' : ''
                ,' <a href="#log',++$log_count,'">&darr;</a> ';

            if (gettype($pretext)==='string') {
                $truevar=$varname;
            } else {
                $pretext=$varname;
                eval("\$truevar=(isset($varname))?$varname:\$GLOBALS['".substr($varname,1)."'];");
            }
            echo '<b>',htmlentities($pretext,ENT_NOQUOTES),'</b> '
                ,htmlentities(print_r($truevar,true),ENT_NOQUOTES)  //,$_SESSION['config']['charset'])
        	    ,"</$tag>";
        }
    }
    //-------------------------------------------------
    function log_value($name,$value){
        log_array(array($name=>$value));
    }
    //-------------------------------------------------
    function log_text($text){
        log_array(array('tag'=>'p',$text=>''));
    }
}
/*
    end of functions for logging debug text
   ======================================================================================
   functions to identify loops in the lookup table: items that are ancestors of themselves
*/
function scanforcircular1tree($map,&$loops,$item,$stack=array() ) { // recursive function used by scanforcircularparents
    if (array_key_exists($item,$loops)) // we've processed this item before, so don't do it again
        return $loops[$item];
    if (in_array($item,$stack,true)) {
        //we've already seen this item in this tree, so it's circular
        $loops[$item]=true;
        //and all of the items in the stack from previous occurence of $item are loopers too
        while($item!==$alsobad=array_pop($stack))
            $loops[$alsobad]=true;
        return false;
    }
    $stack[]=$item;
    if (array_key_exists($item,$map))
        foreach ($map[$item] as $child)
            scanforcircular1tree($map,$loops,$child,$stack);
    if (!array_key_exists($item,$loops)) $loops[$item]=false;
    return true;
}
//-------------------------------------------------
function scanforcircularandmap($parents,&$map,&$ids,$seeds=null) { // get the map of all parent->child relationships
    log_array(array(
        'Calling scanforcircularandmap with seeds'=>$seeds
        ,'and parents'=>$parents));
    if (!$parents) return array();
    $map=$bad=$looplist=array();
    foreach ($parents as $pair)
        $children[]=$map[$pair['parentId']][] = $pair['itemId'];
    if (empty($seeds)) {
        // if we are not given a parent seed, make a list of all items which might be
        // part of a loop:an item can only be in a loop if it's both a parent and a child
        $seeds=array_intersect(array_keys($map),$children);
    }
    // check the descendants of each of those seeds
    foreach ($seeds as $test) {
        log_array(array(
            "Calling scanforcircular1tree with arguments: seed="=>$test
            ,'and looplist'=>$looplist));
        scanforcircular1tree($map,$looplist,$test);
    }
    //return the list of bad items
    $ids=array_keys($looplist);
    $bad=array_keys($looplist,true);
    return $bad;
}
//-------------------------------------------------
function scanforcircularparents() {
    $parents=query('getparents');
    $children=$map=array();
    return scanforcircularandmap($parents,$map,$children);
}
/*
    end of functions to identify loops in the lookup table: items that are ancestors of themselves
   ======================================================================================
   event-handling functions
*/
function getEvents($addon) {
    if (   (@include $_SESSION['addonsdir'].$addon.'/setup.inc.php')===false
        || !isset($events)) return false;
    $_SESSION["addons-$addon"]=array(); // this array will store the options for the addon
    $triggercount=0;
    foreach ($events as $trigger=>$what) {
        foreach ($what as $page=>$handler) {
            if (is_array($handler) && array_key_exists('options',$handler)) {
                // options are present, so store them in the session global, indexed by addon name, event trigger, and page name
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
//-------------------------------------------------
function gtd_handleEvent($event,$page) {
    $eventhandlers=@array_merge((array)$_SESSION['addons'][$event]['*'],
                                (array)$_SESSION['addons'][$event][$page]
                                );
    foreach ($eventhandlers as $addonid=>$handler) {
        $addon=array('id'=>$addonid,
                     'dir'=>$_SESSION['addonsdir'].$addonid.'/',
                     'urlprefix'=>"addon.php?addonid=$addonid&url="
                     );
         if (   !($fp = @fopen($fn="{$addon['dir']}$handler", 'r', 1))
             or !fclose($fp)
             or ((include $fn)===false)
            ) {
            $_SESSION['message'][]="Failed to load addon '$addonid' - please check the addons section of the preferences screen";
        }
    }
}
/*
    end of event-handling functions
   ======================================================================================
    functions to prettify text, and remove possibly-harmful elements
*/
function escapeforjavascript($txt) {
    foreach (array('/\\/'=>'\\\\' , '"'=>'\\"' , '/'=>'\\/') as $from=>$to)
        $txt=ereg_replace($from,$to,$txt);
    return $txt;
}
//-------------------------------------------------
function escapeChars($str) {  // TOFIX consider internationalization issues with charset coding
    foreach (array('/\&/u'=>'&amp;','/\& /'=>'&amp; ','/&amp;hellip;/'=>'&hellip;') as $from=>$to)
        $str=preg_replace($from,$to,$str);
	return $str;
}
//-------------------------------------------------
function makeClean($textIn) {
    if (is_array($textIn)) {
        $cleaned=array();
        foreach ($textIn as $line) $cleaned[]=makeClean($line);
	} else {
        $cleaned=htmlentities(stripslashes($textIn),ENT_QUOTES,$_SESSION['config']['charset']);
    }
	return $cleaned;
}
//-------------------------------------------------
function trimTaggedString($inStr,$inLength=0,$keepTags=TRUE) { // Ensure the visible part of a string, excluding html tags, is no longer than specified) 	// TOFIX -  we don't handle "%XX" strings yet.
	// constants - might move permittedTags to config file
    $permittedTags=array(
		 '/^<a ((href)|(file))=[^>]+>/i'=>'</a>'
		,'/^<b>/i'=>'</b>'
		,'/^<i>/i'=>'</i>'
		,'/^<span [^>]*>/i'=>'</span>'
		,'/^<ul>/i'=>'</ul>'
		,'/^<ol>/i'=>'</ol>'
		,'/^<li>/i'=>'</li>'
		);
	$ellipsis='&hellip;';
	$ampStrings='/^&[#a-zA-Z0-9]+;/';
	
	// initialise variables
    $instrlen=strlen($inStr);
	if ($inLength==0) $inLength=$instrlen*4+1;
	$outStr='';
	$visibleLength=0;
	$thisChar=0;
    $tagToCloselen=0;
	$keepGoing=!empty($inStr);
	$tagsOpen=array();
    $tagToClose='';
	// main processing here
	while ($keepGoing) {
        $totest=substr($inStr,$thisChar);
		$stillHere = TRUE;
		if ($tagToCloselen && strtolower(substr($totest,0,$tagToCloselen))===$tagToClose ) {
			$stillHere=FALSE;
			$thisChar+=$tagToCloselen;
			if ($keepTags) {
                $outStr.=array_pop($tagsOpen);
            } else array_pop($tagsOpen);
            $tagToClose=end($tagsOpen);
		} else {
            $totest0=substr($totest,0,1);
            if ($totest0==='<') {
                foreach ($permittedTags as $thisTag=>$thisClosingTag) {
			        if ( preg_match($thisTag,$totest,$matches)===1 ) { 
				        $thisChar+=strlen($matches[0]);
				        $stillHere=FALSE;
				        if ($keepTags) {
					        array_push($tagsOpen,$thisClosingTag);
					        $outStr.=$matches[0];
                            $tagToClose=$thisClosingTag;
				        }
                        break;
			        } // end of if
		        } // end of else foreach
            }
        }
        if (!$stillHere) // we've got a new end tag that we're watching for, so save its length
            $tagToCloselen=strlen($tagToClose);
		// now check for & ... control characters
		if ($stillHere 
            && ($totest0==='&') 
            && (preg_match($ampStrings,$totest,$matches)===1)) {
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
			$outStr.=$totest0;
			$thisChar++;
		} // end of if
		$keepGoing= ($thisChar<$instrlen && $visibleLength<$inLength);
	} // end of while ($keepGoing)
	// add ellipsis if we have trimmed some text
	if ($thisChar<$instrlen && $visibleLength>=$inLength) $outStr.=$ellipsis;
	// got the string - now close any open tags
	if ($keepTags) while (count($tagsOpen))
		$outStr.=array_pop($tagsOpen);
	$outStr=nl2br(escapeChars($outStr));
	return $outStr;
}
//-------------------------------------------------
function prettyDueDate($dateToShow,$daysdue,$thismask=null) {
    if (is_null($thismask)) $thismask=$_SESSION['config']['datemask'];
	$retval=array('class'=>'','title'=>'');
    if($dateToShow) {
        $retval['date'] = date($thismask,$dateToShow );
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
    end of functions to prettify text, and remove possibly-harmful elements
   ======================================================================================
   functions to generate HTML for SELECT boxes
*/
function categoryselectbox($values) {
    $result = query("categoryselectbox",$values);
    $cashtml='<option value="0">--</option>'."\n";
    if ($result) {
        foreach($result as $row) {
            $cashtml .= '<option value="'.$row['categoryId'].'" title="'.makeclean($row['description']).'"';
            if($row['categoryId']==$values['categoryId']) $cashtml .= ' selected="selected"';
            $cashtml .= '>'.makeclean($row['category'])."</option>\n";
            }
        }
    return $cashtml;
}
//-------------------------------------------------
function contextselectbox($values) {
    $result = query("spacecontextselectbox",$values);
    $cshtml='<option value="0">--</option>'."\n";
    if ($result) {
            foreach($result as $row) {
            $cshtml .= '<option value="'.$row['contextId'].'" title="'.makeclean($row['description']).'"';
            if($row['contextId']==$values['contextId']) $cshtml .= ' selected="selected"';
            $cshtml .= '>'.makeclean($row['name'])."</option>\n";
            }
        }
    return $cshtml;
}
//-------------------------------------------------
function timecontextselectbox($values) {
    $result = query("timecontextselectbox",$values);
    $tshtml='<option value="0">--</option>'."\n";
    if ($result) {
        foreach($result as $row) {
            $tshtml .= '<option value="'.$row['timeframeId'].'" title="'.makeclean($row['description']).'"';
            if($row['timeframeId']==$values['timeframeId']) $tshtml .= ' selected="selected"';
            $tshtml .= '>'.makeclean($row['timeframe'])."</option>\n";
            }
        }
    return $tshtml;
}
//----------------------------------------------------------------
function parentselectbox($values) {
    //----------------------------------------
    function makeOption($row,$selected) {
        $cleandesc=makeclean($row['description']);
        $cleantitle=makeclean($row['title']);
        if ($row['isSomeday']==="y") {
            $cleandesc.=' (Someday)';
            $cleantitle.=' (S)';
        }
        $seltext = ($selected)?' selected="selected"':'';
        $out = "<option value='{$row['itemId']}' title='$cleandesc' $seltext>$cleantitle</option>";
        return $out;
    }
    //----------------------------------------
    $pshtml='';
    $parents=array();
    if (is_array($values['parentId']))
        foreach ($values['parentId'] as $key) $parents[$key]=true;
    else
        $parents[$values['parentId']]=true;
    log_value('parents',$parents);

    $result = query("parentselectbox",$values);
    if ($result)
        foreach($result as $row) {
            if(empty($parents[$row['itemId']])) {
                $pshtml .=makeOption($row,false)."\n";
            } else {
                $pshtml =makeOption($row,true)."\n".$pshtml;
                $parents[$row['itemId']]=false;
            }
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
/*
    end of functions to generate HTML for SELECT boxes
  ==============================================================================
    functions for handling levels of the hierarchy
*/
function getTypes($type=false,$ptype=null) {
    if ($type===false)
        $out=$_SESSION['hierarchy']['names'];
    elseif (empty($type))
        $out='item without a type assigned';
    elseif ($type==='*')
        $out='item';
    elseif ($type==='T') {               // ugly, but at least puts a plaster on the wound
        if ($ptype==='C')
            $out='checklist item';
        elseif ($ptype==='L')
            $out='list item';
        else
            $out = '(check)list item';
    } else
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
        'context'       =>($type==='i' || $type==='a' || $type==='w' || $type==='r'  || $type==='p' ),
        'deadline'      =>($type==='p' || $type==='a' || $type==='w' || $type==='i' || $type==='g'),
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
    /* now we need to make the first child in the list, the primary one
      Take the last one in the list, unless it's the same as the type itself,
      in which case take the penultimate one, if it exists
    */
    foreach ($_SESSION['hierarchy']['parents'] as $child=>$parents) {
        $last=count($parents)-1;
        if ($last<=0) continue;
        $slicer= ($parents[$last]===$child) ? $last-1 : $last;
        $mainparent=array_splice($parents,$slicer,1);
        array_unshift($parents,$mainparent[0]);
        $_SESSION['hierarchy']['parents'][$child]=$parents;
    }
    // now make a sane order for children: the order below will determine the order in itemReport
    $baseorder=array('a','w','m','v','o','g','p','r','i','C','L','T');
    foreach ($_SESSION['hierarchy']['children'] as $parent=>$children)
        if (!empty($children)) {
            $_SESSION['hierarchy']['children'][$parent]=array_values(array_intersect($baseorder,$children));
        }
}
//----------------------------------------------------------------
function resetHierarchy() {
    $_SESSION['hierarchy']=array();
    resetHierarchyNames();
    $_SESSION['hierarchy']['suppressAsOrphans']='imLC';

    $_SESSION['hierarchy']['children']=array(
        'm'=>array('v','o'),
        'v'=>array('o','g','L','C'),
        'o'=>array('g','p','L','C'),
        'g'=>array('p','L','C'),
        'p'=>array('p','a','w','r','C','L'),
        'w'=>array(),
        'a'=>array(),
        'r'=>array(),
        'i'=>array(),
        'C'=>array('T'),
        'L'=>array('T'),
        'T'=>array()
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
function getOrphans() { // retrieve all orphans - items without a parent assigned
    // we only want orphans of specific types, as specified by the user in the preferences screen
    if (empty($_SESSION['hierarchy']['suppressAsOrphans']))
        $orphanfilter='';
    else
        $orphanfilter='AND NOT ('
            .sqlparts('typeinlist',
                        array('types'=>$_SESSION['hierarchy']['suppressAsOrphans'])
                     )
            .')';
    $maintable = query("getorphaneditems",array('orphansfilterquery'=>$orphanfilter));
    return $maintable;
}
/*
    end of hierarchy handling
    =======================================================================
    functions to handle the user-preferences and configuration
*/
function overlayConfig(&$baseconfig,&$baseacckey,&$basesort) {
    if (false===(@include 'config.php') || !isset($config) ) return;
    // successfully loaded config.php too, so merge values over top of defaults, where available
    unset($config['pass']); // stop the password leaking anywhere
    array_merge($baseconfig,$config);
    array_merge($baseacckey,$acckey);
    foreach ($sort as $key=>$val)
        if (array_key_exists($key,$basesort))
            $basesort[$key]=str_replace(
                array('ia.`type`'),
                array('its.`type`'),
                $val);
}
//----------------------------------------------------------------
function importOldConfig() { // get preferences from old config.php file
    define('_GTD_WAIT'    ,1);
    define('_GTD_DEBUG'   ,2);
    define('_GTD_FREEZEDB',4);
    define('_GTD_NOTICE'  ,8);
    if ( false===(@include 'defaultconfig.inc.php')  || !isset($config) ) {
        $_SESSION['message'][]='Unable to find default configuration in defaultconfig.inc.php';
        return false;
    }
    overlayConfig($config,$acckey,$sort);
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
    foreach (array('db','user','host','prefix','dbtype','debug','debugKey') as $key)
        unset($config[$key]);

    // save preferences in session variables
    $_SESSION['config']=$config;
    $_SESSION['sort']=$sort;
    $_SESSION['keys']=array();
    foreach ($acckey as $link=>$key)
        if (!empty($key)) $_SESSION['keys'][$link]=$key;

    resetHierarchy();
    $alltypes=getTypes();

    /*------------------------------------------
        tweaksome config entries which have changed name, or type of data held
    */
    $_SESSION['config']['contextsummary']=($config['contextsummary']==='nextaction'); // force to boolean
    if (!array_key_exists('showRelativeDeadlines',$_SESSION['config']))
        $_SESSION['config']['showRelativeDeadlines']=false; // new value that may not be present in config.php

    $_SESSION['config']['suppressAdmin'] = isset($config['showAsAdmin']) && !$config['showAsAdmin'];
    $_SESSION['config']['suppressCustomRecurrences'] = empty($config['allowCustomRecurrences']);
    
    preg_match_all('/[mvogsparwi]/',$config['suppressAsOrphans'],$tst);
    $_SESSION['hierarchy']['suppressAsOrphans']=implode('',$tst[0]).'LC';
        
    foreach ($alltypes as $type=>$typename) {
        $_SESSION['config']["afterCreate$type"]=(isset($config['afterCreate'][$type]))
                ? $config['afterCreate'][$type]
                : 'item';
        unset($config['afterCreate'][$type]);
    }
    /*
        end of tweaking new entries
    --------------------------------------*/
    $_SESSION['addons']=array();
    $_SESSION['uid']=0;
    
    $values=array('uid'=>$_SESSION['uid'],'option'=>'addons','config'=>serialize($_SESSION['addons']));
    query('updateoptions',$values);
    
    if (!isset($custom_review)) $custom_review=array();
    $values=array('uid'=>$_SESSION['uid'],'option'=>'customreview','config'=>serialize($custom_review));
    //query('updateoptions',$values); // TOFIX this is causing trouble when saving custom review - don't know why

    $result=saveConfig();
    return $result;
}
//----------------------------------------------------------------
function saveConfig() { // store config preferences in the table
    $tst=query('updateconfig',
        array('config' =>serialize($_SESSION['config']),
            'sort'     =>serialize($_SESSION['sort']),
            'keys'     =>serialize($_SESSION['keys']),
            'hierarchy'=>serialize($_SESSION['hierarchy']),
            'debug'    =>serialize($_SESSION['debug']),
            'addons'   =>serialize($_SESSION['addons']),
            'uid'      =>$_SESSION['uid']
        )
    );
    return $tst;
}
//----------------------------------------------------------------
function checkRegisterGlobals() { // check php ini values are ok for utf-8
    $out = (!ini_get('register_globals')) ? '' : <<<RGWARN
<p class='warning'>
<b>WARNING: Running in this configuration is not supported.</b>  Your current
PHP configuration has <tt>register globals</tt> set <tt>on</tt>. This creates
security vulnerabilities, and may intefere with the running of gtd-php.  You
can continue, but the application will behave unpredictably and unreliably.
You can switch <tt>register_globals</tt> off globally in php.ini, if you are
confident that this will not intefere with any of the other PHP applications on
this server.  Or you can switch it off locally in the gtd-php installation
directory by adding the following line to the <tt>.htaccess</tt> file in this
directory:<br />
<tt>php_flag register_globals off</tt>
</p>
RGWARN;
    return $out;
}
//----------------------------------------------------------------
function checkUTF8() { // check php ini values are ok for utf-8

    $passed=true;
    $_SESSION['message'][]='Enabling experimental UTF-8 support';
    
    if (!extension_loaded('mbstring')) {
        $_SESSION['message'][]='In php.ini, enable the mbstring extension';
        $passed=false;
    }

    if (stristr(ini_get('mbstring.http_input'  ),'UTF-8')===false) {
        $_SESSION['message'][]="Either set mbstring.http_input=UTF-8,ASCII in php.ini;
            or add this line to .htaccess: phpvalue mbstring.http_input UTF-8,ASCII";
        $passed=false;
    }
        
    if (stristr(ini_get('mbstring.detect_order'),'UTF-8')===false) {
        $_SESSION['message'][]="Either set mbstring.detect_order=UTF-8,ASCII in php.ini;
            or add this line to .htaccess: phpvalue mbstring.detect_order UTF-8,ASCII";
        $passed=false;
    }

    if (!(ini_get('mbstring.func_overload') & 6)) {
        $_SESSION['message'][]="Either set mbstring.func_overload=6 in php.ini;
            or add this line to .htaccess: phpvalue mbstring.func_overload 6";
        $passed=false;
    }

    include 'config.inc.php';
    if (!array_key_exists('charset',$config) ||  strtoupper($config["charset"]) !=='UTF8') {
        $_SESSION['message'][]="In config.inc.php, set \$config['charset']='UTF8'";
        $passed=false;
    }
    unset($config);
}
//----------------------------------------------------------------
function retrieveConfig() {
    $optionarray=query('getoptions',array('uid'=>$_SESSION['uid'],'filterquery'=>'') );
    if ($optionarray) foreach ($optionarray as $options)
        $_SESSION[$options['option']]=unserialize($options['value']);

    // retrieve cookie values, and overlay them onto preferences
    foreach ($_COOKIE as $key=>$val)
        if (!empty($key) && isset($_SESSION['config'][$key]))
            

    foreach (array('theme'=>'default','useLiveEnhancements'=>false) as $key=>$val) {
        if (array_key_exists($key,$_COOKIE))
            $_SESSION['config'][$key]=$_SESSION[$key]=$_COOKIE[$key];
        if (empty($_SESSION[$key]))
            $_SESSION['config'][$key] =$_SESSION[$key] = $val;
    }

    // go through the list of installed addons, and register them
    foreach($_SESSION['addons'] as $addon=>$dummy)
        getEvents($addon);
}
//----------------------------------------------------------------
function savePerspective($values) {
    // first save the view to a table
    if (query('newperspective',$values)) {
        // and if that worked, save the map from URI to the view
        $values['perspectiveid']=SHA1("{$values['sort']}{$values['columns']}{$values['show']}");
        if (query('newperspectivemap',$values)) {
            return true; // good, that worked too, so everything's ok.
        } else {
            $_SESSION['message'][]="Created new perspective, but failed to map it to URI: $uri";
        }
    } else {
        $_SESSION['message'][]="Failed to create the perspective DB entry for the URI: $uri";
    }
    return false; // something failed
}
//----------------------------------------------------------------

/*
    end of functions to handle the user-preferences and configuration
   ======================================================================================
*/
function getVarFromGetPost($varName,$default='') {
	$retval=(isset($_GET[$varName]))?$_GET[$varName]:( (isset($_POST[$varName]))?$_POST[$varName]:$default );
	return $retval;
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
function getAbsolutePath() {
    global $thisurl;
    $out='http'
        .((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off')?'':'s')
                ."://"
                .$_SERVER['HTTP_HOST']
                .rtrim(dirname($thisurl['path']), '/\\').'/';
    return $out;
}
/*
   ======================================================================================
*/
function nextScreen($url) {
    /* TOFIX Session ID is not passed with Location header
            even if session.use_trans_sid is enabled.
            It must by passed manually using SID constant: strip_tags(SID);
            Need to check whether it's stored in cookie (preferable).
        OR
            we could just insist that session cookies are enabled: that's not
            too unreasonable. (note by Andrew)
    */
    $cleanurl=htmlspecialchars($url);
    if ($_SESSION['debug']['wait']) {
        echo "<p>Next screen is <a href='$cleanurl'>$cleanurl</a> - would be auto-refresh in non-debug mode</p>";
    }elseif (headers_sent()) {
        echo "<META HTTP-EQUIV='Refresh' CONTENT='0;url=$cleanurl' />\n"
            ,"<script type='text/javascript'>window.location.replace('$cleanurl');</script>\n"
            ,"</head><body><a href='$cleanurl'>Click here to continue on to $cleanurl</a>\n";
    }elseif (empty($_SESSION['config']['basepath'])) {
        $header="Location: ".getAbsolutePath().$url;
        header($header);
        exit;
    } else {
        header("Location: {$_SESSION['config']['basepath']}$url");
        exit;
    }
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
*/
function query($querylabel,$values=NULL,$sort=NULL) {
    if (empty($sort)) $sort=$_SESSION['sort'];

    log_array(array(
        'Query Label:'=>$querylabel
        ,'Values to be made safe:'=>$values
        ,'Sort array:'=>$sort));

    //grab correct query string from query library array
    //values automatically inserted into array
    $query=getsql($querylabel,$values,$sort);

    // for testing only: display fully-formed query
    log_value('Query: ',$query);

    //perform query
	$result=doQuery($query,$querylabel);

    //for developer testing only, print result array
    log_value('Query Result:',$result);

    return $result;
}
/*
   ======================================================================================
*/
function getNextRecurrence($values) {
/*
 *  get the next date of a recurring item
 *  returns false if failed, else returns timestamp of next recurrence
 */
    require_once 'iCalcreator.class.inc.php';

    log_text("creating vcalendar to get recurrence date");
    $vcal = new vcalendar();
    $vevent = new vevent();
    $vevent->parse(array('RRULE:'.$values['recur']));
    $rrule=$vevent->getProperty('rrule');

    if (preg_match("/^FREQ=(YEARLY|MONTHLY|WEEKLY|DAILY);INTERVAL=[0-9]+$/",$values['recur'])) {
        // very simple recurrence, so recur from dateCompleted
        log_text("recur from date completed - simple recurrence");
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

    log_array(array(
        'recur='=>$values['recur']
        ,'$rrule'
        ,'start date (dirty)='=>$startdate));
            
    $startdate=$vcal->validDate($startdate);
    log_value('start date (normalised)',$startdate);
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
    log_value('next due date',$nextdue);
    return $nextdue;
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
