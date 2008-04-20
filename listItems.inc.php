<?php
require_once('headerDB.inc.php');
if ($config['debug'] & _GTD_DEBUG) {
    include_once('header.php');
    echo '<pre>POST: ',var_dump($_POST),'</pre>';
}
//page display options array--- can put defaults in preferences table/config/session and load into $show array as defaults...
$show=array();

//GET URL VARIABLES
$values = array();
$filter = array();

// I've used getVarFromGetPost instead of $_REQUEST, because I want $_GET to have higher priority than $_POST.
$filter['needle']         =getVarFromGetPost('needle');            //search string (plain text)
$filter['type']           =getVarFromGetPost('type','a');
$filter['everything']     =getVarFromGetPost('everything');        //overrides filter:true/empty
$filter['contextId']      =getVarFromGetPost('contextId',NULL);
if ($filter['contextId']==='0') $filter['contextId']=NULL;
$filter['categoryId']     =getVarFromGetPost('categoryId',NULL);
if ($filter['categoryId']==='0') $filter['categoryId']=NULL;
$filter['timeframeId']    =getVarFromGetPost('timeframeId',NULL);
if ($filter['timeframeId']==='0') $filter['timeframeId']=NULL;
$filter['notcategory']    =getVarFromGetPost('notcategory');
$filter['notspacecontext']=getVarFromGetPost('notspacecontext');
$filter['nottimecontext'] =getVarFromGetPost('nottimecontext');
$filter['tickler']        =getVarFromGetPost('tickler');           //suppressed (tickler file): true/false
$filter['someday']        =getVarFromGetPost('someday');           //someday/maybe:true/empty
$filter['nextonly']       =getVarFromGetPost('nextonly');          //next actions only: true/empty 
$filter['completed']      =getVarFromGetPost('completed');         //status:true/false (completed/pending)
$filter['dueonly']        =getVarFromGetPost('dueonly');           //has due date:true/empty
$filter['repeatingonly']  =getVarFromGetPost('repeatingonly');     //is repeating:true/empty
$filter['parentId']       =getVarFromGetPost('parentId');
$filter['liveparents']    =getVarFromGetPost('liveparents');
$filter['tags']           =strtolower(getVarFromGetPost('tags'));

if ($filter['type']==='s') {
    $filter['someday']=true;
    $filter['type']='p';
}

$quickfind=isset($_GET['quickfind']);
if ($quickfind) {
    $filter['everything']='true';
    $filter['type']='*';
}

/* end of setting $filter
 --------------------------------------*/
if ($config['debug'] & _GTD_DEBUG) echo '<pre>Filter:',print_r($filter,true),'</pre>';

$values['type']           =$filter['type'];
$values['parentId']       =$filter['parentId'];
$values['contextId']      =$filter['contextId'];
$values['categoryId']     =$filter['categoryId'];
$values['timeframeId']    =$filter['timeframeId'];
$values['needle']         =$filter['needle'];
$values['tags']           =$filter['tags'];

//SQL CODE

$values['filterquery']='';
$taglisttemp=query('gettags',$config,$values);
$taglist=array();
if ($taglisttemp) foreach ($taglisttemp as $tag) $taglist[]=$tag['tagname'];

//create filters for selectboxes
$values['timefilterquery'] = ($config['useTypesForTimeContexts'] && $values['type']!=='*')?" WHERE ".sqlparts("timetype",$config,$values):'';

//create filter selectboxes
$cashtml=str_replace('--','(any)',categoryselectbox   ($config,$values,$sort));
$cshtml =str_replace('--','(any)',contextselectbox    ($config,$values,$sort));
$tshtml =str_replace('--','(any)',timecontextselectbox($config,$values,$sort));

// pass filters in referrer
$thisurl=parse_url($_SERVER['PHP_SELF']);
$referrer = basename($thisurl['path']).'?';
foreach($filter as $filterkey=>$filtervalue)
    if ($filtervalue!='') $referrer .= "{$filterkey}={$filtervalue}&amp;";


//Select items
$trimlength=$config['trimLength'];
if($trimlength) {
    $descriptionField='shortdesc';
    $outcomeField='shortoutcome';
} else {
    $descriptionField='description';
    $outcomeField='desiredOutcome';
}

//set default table column display options (kludge-- needs to be divided into multidimensional array for each table type and added to preferences table
$show['parent']=TRUE;
$show['type']=FALSE;
$show['NA']=FALSE;
$show['title']=TRUE;
$show[$descriptionField]=TRUE;
$show[$outcomeField]=FALSE;
$show['isSomeday']=FALSE;
$show['tickledate']=FALSE;
$show['dateCreated']=FALSE;
$show['lastModified']=FALSE;
$show['category']=TRUE;
$show['context']=TRUE;
$show['timeframe']=TRUE;
$show['deadline']=TRUE;
$show['recurdesc']=TRUE;
$show['dateCompleted']=FALSE;
$show['checkbox']=TRUE;
$show['tags']=FALSE;
$show['assignType']=FALSE;
$showalltypes=false;
//determine item and parent labels, set a few defaults
$typename=getTypes($values['type']);
$parenttypes=getParentType($values['type']);
if (empty($parenttypes)) {
    $values['ptype']=$parentname='';
} else {
    $values['ptype']=$parenttypes[0];
    $parentname=getTypes($values['ptype']);
}
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
    case "L" : // as case 'C'
    case "C" : $show['parent']=TRUE; $show['category']=TRUE; $show['context']=FALSE; $show['timeframe']=FALSE; $show['deadline']=FALSE; $show['dateCreated']=FALSE; $show['recurdesc']=FALSE; $show['assignType']=FALSE; $show['checkbox']=FALSE; $checkchildren=TRUE; break;
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
if (!$checkchildren) $show['flags']=FALSE;
//set query fragments based on filters
$values['childfilterquery'] = "WHERE TRUE";

//type filter
if ($values['type']!=='*')
    $values['childfilterquery'] .= " AND ".sqlparts("typefilter",$config,$values);

// search string
if ($filter['needle']!=='')
    $values['childfilterquery'] .= " AND ".sqlparts("matchall",$config,$values);

$linkfilter='';

if ($checkchildren) {
    $values['filterquery'] = sqlparts("checkchildren",$config,$values);
    $values['extravarsfilterquery'] = sqlparts("countchildren",$config,$values);;
} else $values['filterquery']=$values['extravarsfilterquery']='';

if ($filter['tags'] !=NULL) {
    // now put into array, to count
    $values['childfilterquery'] .= " AND " .sqlparts("hastags",$config,$values);
    $show['tags']=true;
}

/*  Only use filter selections if $filter['everything'] is not true;
    i.e. if we are not forcing the listing of *all* items
*/
if ($filter['everything']!="true") {
    if ($values['parentId']!='')
        $values['filterquery'] .= " WHERE ".sqlparts("hasparent",$config,$values);
    else switch ($filter['liveparents']) {
        case 'false': // show only children of completed / suppressed / someday parents
            $values['filterquery'] .= ' WHERE NOT ( '.sqlparts("liveparents",$config,$values).' )';
            break;

        case '*': // don't filter on completion status of parents
            break;

        case 'true': //Filter out items with completed/suppressed/someday parents  - deliberately flows through to default case
        default:
            $values['filterquery'] .= ' WHERE '.sqlparts("liveparents",$config,$values);
            break;
    }

    //filter box filters
    if ($filter['categoryId'] != NULL && $filter['notcategory']=="true")
        $values['childfilterquery'] .= " AND ".sqlparts("notcategoryfilter",$config,$values);
    elseif($filter['categoryId'] != NULL || $filter['notcategory']=="true") {
        $values['childfilterquery'] .= " AND ".sqlparts("categoryfilter",$config,$values);
        $linkfilter .= '&amp;categoryId='.$values['categoryId'];
    }
    
    if ($filter['contextId'] != NULL && $filter['notspacecontext']=="true")
        $values['childfilterquery'] .= " AND ".sqlparts("notcontextfilter",$config,$values);
    elseif ($filter['contextId'] != NULL || $filter['notspacecontext']=="true") {
        $values['childfilterquery'] .= " AND ".sqlparts("contextfilter",$config,$values);
        $linkfilter .= '&amp;contextId='.$values['contextId'];
    }
    
    if ($filter['timeframeId'] != NULL && $filter['nottimecontext']=="true")
        $values['childfilterquery'] .= " AND ".sqlparts("nottimeframefilter",$config,$values);
    elseif ($filter['timeframeId'] != NULL || $filter['nottimecontext']=="true") {
        $values['childfilterquery'] .= " AND ".sqlparts("timeframefilter",$config,$values);
        $linkfilter .= '&amp;timeframeId='.$values['timeframeId'];
    }
    
    if ($filter['completed']=="true") $values['childfilterquery'] .= " AND ".sqlparts("completeditems",$config,$values);
    else $values['childfilterquery'] .= " AND " .sqlparts("pendingitems",$config,$values);
    
    if ($filter['someday']=="true") {
        $values['isSomeday']="y";
        $values['childfilterquery'] .= " AND " .sqlparts("issomeday",$config,$values);
    } else {
        $values['isSomeday']="n";
        $values['childfilterquery'] .= " AND ".sqlparts("issomeday",$config,$values);
    }
    
    if ($filter['tickler']=="true") {
        $linkfilter .='&amp;tickler=true';
        $values['childfilterquery'] .= " AND ".sqlparts("suppresseditems",$config,$values);
    } else {
        $values['childfilterquery'] .= " AND ".sqlparts("activeitems",$config,$values);
    }
    
    if ($filter['repeatingonly']=="true") $values['childfilterquery'] .= " AND " .sqlparts("repeating",$config,$values);
    
    if ($filter['dueonly']=="true") $values['childfilterquery'] .= " AND " .sqlparts("due",$config,$values);

    if ($filter['nextonly']=='true' && !$checkchildren)
        $values['childfilterquery'] .= ' AND '.sqlparts("isNAonly",$config,$values);

}
/*
Section Heading
*/
$link="item.php?type=".$values['type'];

if($filter['everything']=="true")
    $sectiontitle = '';
else {
    $link .= $linkfilter;
    if ($filter['completed']=="true")
        $sectiontitle = 'Completed ';
    elseif ($filter['dueonly']=="true")
        $sectiontitle =  'Due ';
    else $sectiontitle ='';

    if ($filter['repeatingonly']=="true")
        $sectiontitle .= 'Repeating ';

    if ($filter['someday']=="true") {
        $sectiontitle .= 'Someday/Maybe ';
        $link.='&amp;someday=true';
    }
    if ($filter['nextonly']=="true") {
        $sectiontitle .= 'Next ';
        $link .='&amp;nextonly=true';
    }
}
$sectiontitle .= $typename;
/*
    ===================================================================
    main query: build array of items
    ===================================================================
*/
if ($quickfind)
    $result=0;
else
    $result=query("getitemsandparent",$config,$values,$sort);
    
$maintable=array();
$thisrow=0;
$allids=array();
if ($result) {
    $nonext=FALSE;
    $nochildren=FALSE;
    $wasNAonEntry=array();  // stash this in case we introduce marking actions as next actions onto this screen
    foreach ($result as $row) {
        $allids[]=$row['itemId'];
    
        $nochildren=false;
        $nonext=false;
        if ($checkchildren) {
            $nochildren=!$row['numChildren'];
            $nonext=($row['type']=='p' && !$row['numNA']);
        }
        if (isset($row['nextaction']) && $row['nextaction']==='y') {
            array_push($wasNAonEntry,$row['itemId']);
        } else $row['nextaction']=false;
        
        $maintable[$thisrow]=array();
        $maintable[$thisrow]['itemId']=$row['itemId'];
        $maintable[$thisrow]['class'] = ($nonext || $nochildren)?'noNextAction':'';
        $maintable[$thisrow]['NA'] =$row['nextaction']==='y';

        $maintable[$thisrow]['dateCreated'] = $row['dateCreated'];
        $maintable[$thisrow]['lastModified']= $row['lastModified'];
        $maintable[$thisrow]['dateCompleted']= $row['dateCompleted'];
        $maintable[$thisrow]['isSomeday'] =$row['isSomeday'];
        $maintable[$thisrow]['type'] =$row['type'];

        if ($row['parentId']=='') {
            $maintable[$thisrow]['parent.class']='noparent';
            $maintable[$thisrow]['ptitle']='';
        } else {
            $maintable[$thisrow]['ptitle']=$row['ptitle'];
            $maintable[$thisrow]['parentId']=$row['parentId'];
        }
        // add markers to indicate if this is a next action, or a project with no next actions, or an item with no childern
        if ($nochildren)
            $maintable[$thisrow]['flags'] = 'noChild';
        elseif ($nonext)
            $maintable[$thisrow]['flags'] = 'noNA';
        else
            $maintable[$thisrow]['flags'] = '';

        //item title
        if (!($row['type']=="a" || $row['type']==="r" || $row['type']==="w" || $row['type']==="i"))
            $maintable[$thisrow]['doreport']=true;
        
        $cleantitle=makeclean($row['title']);
        $maintable[$thisrow]['title.class'] = 'maincolumn';
        $maintable[$thisrow]['title'] =$row['title'];

        $maintable[$thisrow]['checkbox.title']='Complete '.$cleantitle;
        $maintable[$thisrow]['checkboxname']= 'isMarked[]';
        $maintable[$thisrow]['checkboxvalue']=$row['itemId'];

        $maintable[$thisrow][$descriptionField] = $row['description'];
        $maintable[$thisrow][$outcomeField] = $row['desiredOutcome'];

        $maintable[$thisrow]['category'] =makeclean($row['category']);
        $maintable[$thisrow]['categoryId'] =$row['categoryId'];

        $maintable[$thisrow]['context'] = makeclean($row['cname']);
        $maintable[$thisrow]['contextId'] = $row['contextId'];
        
        $maintable[$thisrow]['timeframe'] = makeclean($row['timeframe']);
        $maintable[$thisrow]['timeframeId'] = $row['timeframeId'];

        $maintable[$thisrow]['tags'] =$row['tags'];

        $childType=array();
        $childType=getChildType($row['type']);
        if (count($childType)) $maintable[$thisrow]['childtype'] =$childType[0];
        
        if($row['deadline']) {
            $deadline=prettyDueDate($row['deadline'],$config['datemask']);
            $maintable[$thisrow]['deadline'] =$deadline['date'];
            if (empty($row['dateCompleted'])) {
                $maintable[$thisrow]['deadline.class']=$deadline['class'];
                $maintable[$thisrow]['deadline.title']=$deadline['title'];
            }
        } else $maintable[$thisrow]['deadline']='';
             
        $maintable[$thisrow]['recurdesc'] =$row['recurdesc'];
        $maintable[$thisrow]['tickledate']= $row['tickledate'];
        $thisrow++;
    } // end of: foreach ($result as $row)
    
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
        ,'assignType'=>'Assign'
        ,'checkbox'=>'Complete'
        ,'tags'=>'Tags'
        );
    if ($config['debug'] & _GTD_DEBUG) echo '<pre>values to print:',print_r($maintable,true),'</pre>';
} // end of: if($result)
/*
    ===================================================================
    end of main query: finished building array of items
    ===================================================================
*/
$_SESSION['idlist-'.$values['type']]=$allids;
$numrows=count($maintable);
if ($numrows!==1) $sectiontitle.='s';
if ($filter['tickler']=="true" && $filter['everything']!="true") {
    $sectiontitle .= ' in Tickler File';
    $link .= '&amp;suppress=true';
}

if ($quickfind)
    $sectiontitle='&nbsp;';
elseif($filter['everything']=="true") {
    switch ($numrows) {
        case 0:
            $sectiontitle = 'There are no '.$sectiontitle;
            break;
        case 1:
            $sectiontitle = 'There is one '.$sectiontitle;
            break;
        default:
            $sectiontitle = "All $numrows $sectiontitle";
            break;
    }
} else
    $sectiontitle = $numrows.' '.$sectiontitle;

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
    $sectiontitle = "<a title='Add new' href='$link'>$sectiontitle</a>";

$_SESSION['lastfilter'.$values['type']]=$referrer;
$showsubmit=($show['NA'] || $show['checkbox']) && count($maintable);

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
