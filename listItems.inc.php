<?php
require_once 'headerDB.inc.php';
if ($_SESSION['debug']['debug']) include_once 'header.inc.php';

//GET URL VARIABLES
$values = array();
$filter = array();

/* --------------------------------------------------------------------------
 * set $filter
 *
 * NB the values for filter are assigned in alphabetic order,
 * to try to assure consistency, as we'll be using the filter as the index-key
 * to link to the right Perspective
 *
 * I've used getVarFromGetPost instead of $_REQUEST,
 * because I want $_GET to have higher priority than $_POST.
 */
$filter['categoryId']     =getVarFromGetPost('categoryId',NULL);
if ($filter['categoryId']==='0') $filter['categoryId']=NULL;
$filter['completed']      =getVarFromGetPost('completed');         //status:true/false (completed/pending)
$filter['contextId']      =getVarFromGetPost('contextId',NULL);
if ($filter['contextId']==='0') $filter['contextId']=NULL;
$filter['dueonly']        =getVarFromGetPost('dueonly');           //has due date:true/empty
$filter['everything']     =getVarFromGetPost('everything');        //overrides filter:true/empty
$filter['liveparents']    =getVarFromGetPost('liveparents');
$filter['needle']         =getVarFromGetPost('needle');            //search string (plain text)
$filter['nextonly']       =getVarFromGetPost('nextonly');          //next actions only: true/empty
$filter['notcategory']    =getVarFromGetPost('notcategory');
$filter['notspacecontext']=getVarFromGetPost('notspacecontext');
$filter['nottimecontext'] =getVarFromGetPost('nottimecontext');
$filter['parentId']       =getVarFromGetPost('parentId');
$filter['repeatingonly']  =getVarFromGetPost('repeatingonly');     //is repeating:true/empty
$filter['someday']        =getVarFromGetPost('someday');           //someday/maybe:true/empty
$filter['tags']           =strtolower(getVarFromGetPost('tags'));
$filter['tickler']        =getVarFromGetPost('tickler');           //suppressed (tickler file): true/false
$filter['timeframeId']    =getVarFromGetPost('timeframeId',NULL);
if ($filter['timeframeId']==='0') $filter['timeframeId']=NULL;
$filter['type']           =getVarFromGetPost('type','a');

if ($filter['type']==='s') {
    $filter['someday']=true;
    $filter['type']='p';
}

$quickfind=isset($_GET['quickfind']);
if ($quickfind) {
    $filter['everything']='true';
    $filter['type']='*';
}

// pass filters in referrer
$referrer = "{$pagename}.php?";
foreach($filter as $filterkey=>$filtervalue)
    if ($filtervalue!='') $referrer .= "{$filterkey}={$filtervalue}&amp;";

/* end of setting $filter
    --------------------------------------------------------------------------
*/
log_value('listitems filter',$filter);

$values['type']           =$filter['type'];
$values['parentId']       =$filter['parentId'];
$values['contextId']      =$filter['contextId'];
$values['categoryId']     =$filter['categoryId'];
$values['timeframeId']    =$filter['timeframeId'];
$values['needle']         =$filter['needle'];
$values['tags']           =$filter['tags'];

//SQL CODE

$values['filterquery']='';
$taglisttemp=query('gettags',$values);
$taglist=array();
if ($taglisttemp) foreach ($taglisttemp as $tag) $taglist[]=makeclean($tag['tagname']);

//create type-filter for timecontext select boxes
$values['timefilterquery'] = ($_SESSION['config']['useTypesForTimeContexts'] && $values['type']!=='*')?" WHERE ".sqlparts("timetype",$values):'';

//create select boxes for category, context, time:
$cashtml=str_replace('--','(any)',categoryselectbox   ($values));
$cshtml =str_replace('--','(any)',contextselectbox    ($values));
$tshtml =str_replace('--','(any)',timecontextselectbox($values));

/*
 *  -------------------------------------------------------------------
 *  determine the values of those variables that influence the columns displayed
 */
$trimlength=$_SESSION['config']['trimLength'];
if($trimlength) {
    $descriptionField='shortdesc';
    $outcomeField='shortoutcome';
} else {
    $descriptionField='description';
    $outcomeField='desiredOutcome';
}

//determine item and parent labels,
$typename=getTypes($values['type']);
$parenttypes=getParentType($values['type']);
if (empty($parenttypes)) {
    $values['ptype']=$parentname='';
} else {
    $values['ptype']=$parenttypes[0];
    $parentname=getTypes($values['ptype']);
}
/*
    ===================================================================
    setting $show/$dispArray values
*/
// default list of fields
$dispArray=array(
    'parent'=>'parents'
    ,'type'=>'type'
    ,'flags'=>'!'
    ,'NA'=>'NA'
    ,'title'=>$typename.'s'
    ,$descriptionField=>'Description'
    ,$outcomeField=>'Desired Outcome'
    ,'category'=>'Category'
    ,'context'=>'Space Context'
    ,'timeframe'=>'Time Context'
    ,'deadline'=>'Deadline'
    ,'recurdesc'=>'Repeat'
    ,'tickledate'=>'Suppress until'
    ,'dateCreated'=>'Date Created'
    ,'lastModified'=>'Last Modified'
    ,'dateCompleted'=>'Date Completed'
    ,'checkbox'=>'Complete'
    ,'tags'=>'Tags'
    ,'assignType'=>'Process inbox'
);
$show=array();
foreach ($dispArray as $key=>$dummy)
    $show[$key]=false;
    
$testuri=sha1($referrer);
//$perspectivefilter=' WHERE '.sqlparts('perspectiveuri',array('uri'=>$testuri));
//$displayoptions=query('selectperspective',array('filterquery'=>$perspectivefilter));
//}
if (false && $displayoptions) { // disabling this for now
    // we have a saved perspective setting out how pages with this filter should be displayed.

    $dispCopy=$dispArray;
    $dispArray=array();
    foreach (explode(',',$displayoptions[0]['columns']) as $field)
        $dispArray[$field]=$dispCopy[$field];
        
    $mainsort=safeIntoDB($displayoptions[0]['sort']);
    if (!empty($mainsort))
        $sort=array('getitemsandparent'=>$mainsort.','.$_SESSION['sort']['getitemsandparent']);
    
    foreach (explode(',',$displayoptions[0]['show']) as $field)
        $show[$field]=true;

    $checkchildren=array_key_exists('flags',$dispArray);
} else {
    // manually calculating which columns to show
    // set default table column display options
    $sort=false;
    $showalltypes=false;
    //page display options array--- can put defaults in preferences table/config/session and load into $show array as defaults...
    $show['parent']=TRUE;
    $show['title']=TRUE;
    $show[$descriptionField]=TRUE;
    $show['category']=TRUE;
    $show['context']=TRUE;
    $show['timeframe']=TRUE;
    $show['deadline']=TRUE;
    $show['recurdesc']=TRUE;
    $show['checkbox']=TRUE;

    switch ($values['type']) {
        case "*" : $show['type']=TRUE; $show['checkbox']=FALSE; $show['recurdesc']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=FALSE; $showalltypes=TRUE; break;
        case "m" : $show['parent']=FALSE; $show['checkbox']=FALSE; $show['recurdesc']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "v" : $show['checkbox']=FALSE; $show['recurdesc']=FALSE; $show['dateCreated']=TRUE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "o" : $show['checkbox']=FALSE; $show['recurdesc']=FALSE; $show['deadline']=FALSE; $show['desiredOutcome']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "g" : $show['desiredOutcome']=TRUE; $show['context']=FALSE; $checkchildren=TRUE; break;
        case "p" : $show['context']=FALSE; $show['timeframe']=FALSE; $checkchildren=TRUE; break;
        case "a" : $show['parent']=TRUE; $show['NA']=TRUE; $show['category']=FALSE; $checkchildren=FALSE; break;
        case "w" : $show['parent']=TRUE; $show['NA']=TRUE; $checkchildren=FALSE; break;
        case "r" : $show['parent']=TRUE; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['checkbox']=FALSE; $show['recurdesc']=FALSE; $show['dateCreated']=TRUE; $checkchildren=FALSE; break;
        case "i" : $show['parent']=FALSE; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['deadline']=FALSE; $show['dateCreated']=TRUE; $show['recurdesc']=FALSE; $show['assignType']=TRUE; $afterTypeChange='listItems.php?type=i';$checkchildren=FALSE; break;
        case "L" : // as case 'C', so deliberately flows through
        case "C" : $show['parent']=TRUE; $show['category']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['deadline']=FALSE; $show['dateCreated']=FALSE; $show['recurdesc']=FALSE; $show['checkbox']=FALSE; $checkchildren=TRUE; break;
        case "T" : $show['parent']=TRUE; $show['category']=FALSE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['deadline']=FALSE; $show['dateCreated']=TRUE; $show['recurdesc']=FALSE; $checkchildren=FALSE; break;
        default  : $typename="Item"; $parentname=$values['ptype']=""; $checkchildren=FALSE;
    }
    $show['flags']=$checkchildren; // temporary measure; to be made user-configurable later


    if ($filter['someday']=="true") {
        $show['dateCreated']=TRUE;
        $show['context']=FALSE;
        $show['recurdesc']=FALSE;
        $show['NA']=FALSE;
        $show['deadline']=FALSE;
        $show['timeframe']=FALSE;
        $checkchildren=FALSE;
    }

    if ($filter['tickler']=="true") $show['tickledate']=TRUE;

    if ($filter['dueonly']=="true") $show['deadline']=TRUE;

    if ($filter['repeatingonly']=="true") {
        $show['deadline']=TRUE;
        $show['recurdesc']=TRUE;
    }

    if ($filter['completed']=="true") {
        $show['NA']=FALSE;
        $show['flags']=FALSE;
        $show['tickledate']=FALSE;
        $show['dateCreated']=TRUE;
        $show['deadline']=FALSE;
        $show['recurdesc']=FALSE;
        $show['dateCompleted']=TRUE;
        $show['checkbox']=FALSE;
        $checkchildren=FALSE;
    }

    if ($filter['everything']=="true") {
        $show['type']=$showalltypes;
        $show['isSomeday']=TRUE;
        $show['tickledate']=TRUE;
        $show['dateCreated']=TRUE;
        $show['lastModified']=TRUE;
        $show['deadline']=TRUE;
        $show['dateCompleted']=TRUE;
        $show['checkbox']=FALSE;
    }
    if ($filter['tags'] !=NULL) $show['tags']=true;

    if (!$checkchildren) $show['flags']=FALSE;

    if (!$show['assignType'])
        unset($dispArray['assignType']);
    // end of manually calculating which columns to show
} 

/*
    end of setting $show/$dispArray values
    ===================================================================
    build the query
*/
//set query fragments based on filters
$values['childfilterquery'] = "WHERE TRUE";

//type filter
if ($values['type']!=='*')
    $values['childfilterquery'] .= " AND ".sqlparts("typefilter",$values);

// search string
if ($filter['needle']!=='')
    $values['childfilterquery'] .= " AND ".sqlparts("matchall",$values);

$linkfilter='';

if ($checkchildren) {
    $values['filterquery'] = sqlparts("checkchildren",$values);
    $values['extravarsfilterquery'] = sqlparts("countchildren",$values);;
} else $values['filterquery']=$values['extravarsfilterquery']='';

if ($filter['tags'] !=NULL) {
    // now put into array, to count
    $values['childfilterquery'] .= " AND " .sqlparts("hastags",$values);
}

/*  Only use filter selections if $filter['everything'] is not true;
    i.e. if we are not forcing the listing of *all* items
*/
if ($filter['everything']!="true") {
    if ($values['parentId']!='')
        $values['filterquery'] .= " WHERE ".sqlparts("hasparent",$values);
    else switch ($filter['liveparents']) {
        case 'false': // show only children of completed / suppressed / someday parents
            $values['filterquery'] .= ' WHERE NOT ( '.sqlparts("liveparents",$values).' )';
            break;

        case '*': // don't filter on completion status of parents
            break;

        case 'true': //Filter out items with completed/suppressed/someday parents  - deliberately flows through to default case
        default:
            $values['filterquery'] .= ' WHERE '.sqlparts("liveparents",$values);
            break;
    }

    //filter box filters
    if ($filter['categoryId'] != NULL && $filter['notcategory']=="true")
        $values['childfilterquery'] .= " AND ".sqlparts("notcategoryfilter",$values);
    elseif($filter['categoryId'] != NULL || $filter['notcategory']=="true") {
        $values['childfilterquery'] .= " AND ".sqlparts("categoryfilter",$values);
        $linkfilter .= '&amp;categoryId='.$values['categoryId'];
    }

    if ($filter['contextId'] != NULL && $filter['notspacecontext']=="true")
        $values['childfilterquery'] .= " AND ".sqlparts("notcontextfilter",$values);
    elseif ($filter['contextId'] != NULL || $filter['notspacecontext']=="true") {
        $values['childfilterquery'] .= " AND ".sqlparts("contextfilter",$values);
        $linkfilter .= '&amp;contextId='.$values['contextId'];
    }

    if ($filter['timeframeId'] != NULL && $filter['nottimecontext']=="true")
        $values['childfilterquery'] .= " AND ".sqlparts("nottimeframefilter",$values);
    elseif ($filter['timeframeId'] != NULL || $filter['nottimecontext']=="true") {
        $values['childfilterquery'] .= " AND ".sqlparts("timeframefilter",$values);
        $linkfilter .= '&amp;timeframeId='.$values['timeframeId'];
    }

    if ($filter['completed']=="true") $values['childfilterquery'] .= " AND ".sqlparts("completeditems",$values);
    else $values['childfilterquery'] .= " AND " .sqlparts("pendingitems",$values);

    if ($filter['someday']=="true") {
        $values['isSomeday']="y";
        $values['childfilterquery'] .= " AND " .sqlparts("issomeday",$values);
    } else {
        $values['isSomeday']="n";
        $values['childfilterquery'] .= " AND ".sqlparts("issomeday",$values);
    }

    if ($filter['tickler']=="true") {
        $linkfilter .='&amp;tickler=true';
        $values['childfilterquery'] .= " AND ".sqlparts("suppresseditems",$values);
    } else {
        $values['childfilterquery'] .= " AND ".sqlparts("activeitems",$values);
    }

    if ($filter['repeatingonly']=="true") $values['childfilterquery'] .= " AND " .sqlparts("repeating",$values);

    if ($filter['dueonly']=="true") $values['childfilterquery'] .= " AND " .sqlparts("due",$values);

    if ($filter['nextonly']=='true')
        $values['childfilterquery'] .= ' AND '.sqlparts("isNAonly",$values);

}
/*
Section Heading
*/
$link="item.php?type=".$values['type'];

if($filter['everything']=="true")
    $title = '';
else {
    $link .= $linkfilter;
    if ($filter['completed']=="true")
        $title = 'Completed ';
    elseif ($filter['dueonly']=="true")
        $title =  'Due ';
    else $title ='';

    if ($filter['repeatingonly']=="true")
        $title .= 'Repeating ';

    if ($filter['someday']=="true") {
        $title .= 'Someday/Maybe ';
        $link.='&amp;someday=true';
    }
    if ($filter['nextonly']=="true") {
        $title .= 'Next ';
        $link .='&amp;nextonly=true';
    }
}
$title .= $typename;
/*
    ===================================================================
    main query: build array of items
    ===================================================================
*/
if ($quickfind)
    $maintable=0;
else
    $maintable=query("getitemsandparent",$values,$sort);
    
//$maintable=array();
$allids=array();
if ($maintable) {
    $nonext=FALSE;
    $nochildren=FALSE;
    $wasNAonEntry=array();  // stash this in case we introduce marking actions as next actions onto this screen
    $max=count($maintable);
    for ($thisrow=0;$thisrow<$max;$thisrow++) {
        $row=&$maintable[$thisrow];
        $allids[]=$row['itemId'];
        $nochildren=false;
        $nonext=false;
        if ($checkchildren) {
            $nochildren=!$row['numChildren'];
            $nonext=($row['type']=='p' && !$row['numNA']);
        }
        if (isset($row['nextaction']) && $row['nextaction']==='y') {
            /* TODO - test what happens when AJAX off, and showing complete
             * and incomplete items, and user completes 1 and submits it.
             * Fortunately, this is not an issue at present, because the completion
             * checkboxes aren't displayed, if the list includes completed items
             */
            array_push($wasNAonEntry,$row['itemId']); // TOFIX need to do something similar with completed items
            $row['NA'] =true;
        } else $row['NA']=false;
        
        $row['class'] = ($nonext || $nochildren)?'noNextAction':'';
        $row['nextaction']==='y';

        if ($row['parentId']=='') {
            $row['parent.class']='noparent';
            $row['ptitle']='';
        } else {
            $row['ptitle']=$row['ptitle'];
        }
        // add markers to indicate if this is a next action, or a project with no next actions, or an item with no childern
        if ($nochildren)
            $row['flags'] = 'noChild';
        elseif ($nonext)
            $row['flags'] = 'noNA';
        else
            $row['flags'] = '';

        //item title
        if (!($row['type']=="a" || $row['type']==="r" || $row['type']==="w" || $row['type']==="i"))
            $row['doreport']=true;
        
        $cleantitle=makeclean($row['title']);
        $row['title.class'] = 'maincolumn';

        $row['checkboxname']= 'isMarked[]';
        $row['checkboxvalue']=$row['itemId'];
        
        if (!empty($row['dateCompleted']))
            $row['NAdisabled']=$row['checkboxchecked']=$row['checkboxdisabled']=true;
            

        $row[$descriptionField] = $row['description'];
        $row[$outcomeField] = $row['desiredOutcome'];

        $row['category'] =makeclean($row['category']);

        $row['context'] = makeclean($row['cname']);
        
        $row['timeframe'] = makeclean($row['timeframe']);

        $childType=array();
        $childType=getChildType($row['type']);
        if (count($childType)) $row['childtype'] =$childType[0];
        
        if($row['deadline']) {
            $deadline=prettyDueDate($row['deadline'],$row['daysdue']);
            $row['deadline'] =$deadline['date'];
            if (empty($row['dateCompleted'])) {
                $row['deadline.class']=$deadline['class'];
                $row['deadline.title']=$deadline['title'];
            }
        } else $row['deadline']='';
             
    } // end of: for
    
    log_value('values to print:',$maintable);
    unset($row);
} else $maintable=array(); // end of: if($maintable)
/*
    ===================================================================
    end of main query: finished building array of items
    ===================================================================
*/
$_SESSION['idlist-'.$values['type']]=$allids;
$numrows=count($maintable);
if ($numrows!==1) $title.='s';
if ($filter['tickler']=="true" && $filter['everything']!="true") {
    $title .= ' in Tickler File';
    $link .= '&amp;suppress=true';
}

if ($quickfind)
    $title='&nbsp;';
elseif($filter['everything']=="true") {
    switch ($numrows) {
        case 0:
            $title = 'There are no '.$title;
            break;
        case 1:
            $title = 'There is one '.$title;
            break;
        default:
            $title = "All $numrows $title";
            break;
    }
} else
    $title = $numrows.' '.$title;

if($numrows || $quickfind)
    $endmsg='';
else {
    $endmsg=array('header'=>"You have no {$typename}s remaining.");
    if ($filter['completed']!="true" && $values['type']!="t" && $values['type']!="*") {
        $endmsg['prompt']="Create a new {$typename}";
        $endmsg['link']=$link;
    }
}
if (($filter['completed']!="true" || $filter['everything']=="true") && $filter['type']!=='*')
    $sectiontitle = "<a title='Add new' href='$link'>$title</a>";
else
    $sectiontitle =$title;

$_SESSION['lastfilter'.$values['type']]=$referrer;
$showsubmit=($show['NA'] || $show['checkbox']) && count($maintable);

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
