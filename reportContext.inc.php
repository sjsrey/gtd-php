<?php
function makeContextRow($row) {
    global $config;
    $rowout=array();
    $rowout['itemId']=$row['itemId'];
	$rowout['description']=$row['description'];
	$rowout['repeat'] = ($row['repeat']=="0")?'&nbsp;':$row['repeat'];
    if($row['deadline']) {
        $deadline=prettyDueDate($row['deadline'],$config['datemask']);
        $rowout['deadline'] =$deadline['date'];
        $rowout['deadline.class']=$deadline['class'];
        $rowout['deadline.title']=$deadline['title'];
    } else $rowout['deadline']='';
    $rowout['title']=$row['title'];
    $rowout['title.title']='Edit';
	$rowout['ptitle']=$row['ptitle'];
	$rowout['parentId']=$row['parentId'];
	if ($row['parentId']=='') $rowout['parent.class']='noparent';
	$rowout['checkboxname']='isMarked[]';
	$rowout['checkbox.title']='Mark as complete';
	$rowout['checkboxvalue']=$row['itemId'];
    $rowout['NA'] = $row['NA'];
    return $rowout;
}
function makeContextTable($maintable) {
    global $dispArray,$show,$config;
    ob_start();
    require('displayItems.inc.php');
    $out=ob_get_contents();
    ob_end_clean();
    return $out;
}
$values=array();

//SQL CODE AREA
//obtain all contexts
$contextResults = query("getspacecontexts",$config,$values,$sort);
$contextNames=array(0=>'none');
if ($contextResults)
    foreach ($contextResults as $row)
	   $contextNames[(int) $row['contextId']]=makeclean($row['name']);

//obtain all timeframes
$values['type']='a';
$values['timefilterquery'] = ($config['useTypesForTimeContexts'])?" WHERE ".sqlparts("timetype",$config,$values):'';
$timeframeResults = query("gettimecontexts",$config,$values,$sort);
$timeframeNames=array(0=>'none');
$timeframeDesc=array(0=>'none');
if ($timeframeResults) foreach($timeframeResults as $row) {
	$timeframeNames[(int) $row['timeframeId']]=makeclean($row['timeframe']);
	$timeframeDesc[(int) $row['timeframeId']]=makeclean($row['description']);
	}

//obtain all active item timeframes and count instances of each
$NAfilter='isNA'.(($config["contextsummary"] === 'nextaction')?'only':'');
$values['filterquery'] = sqlparts($NAfilter,$config,$values);
$values['extravarsfilterquery'] =sqlparts("getNA",$config,$values);;

$thisurl=parse_url($_SERVER['PHP_SELF']);
$dispArray=array('parent'=>'Project'
    ,'NA'=>'NA'
    ,'title'=>'Action'
    ,'description'=>'Description'
    ,'deadline'=>'Deadline'
    ,'repeat'=>'Repeat'
    ,'checkbox'=>'Complete');
$show=array();
foreach ($dispArray as $key=>$val) $show[$key]=true;

$wasNAonEntry=array();

$values['type'] = "a";
$values['isSomeday'] = "n";
$values['childfilterquery']  = " WHERE ".sqlparts("typefilter",$config,$values);
$values['childfilterquery'] .= " AND ".sqlparts("activeitems",$config,$values);
$values['childfilterquery'] .= " AND ".sqlparts("issomeday",$config,$values);
$values['childfilterquery'] .= " AND ".sqlparts("pendingitems",$config,$values);
$values['parentfilterquery'] = " WHERE ".sqlparts("activeitems",$config,$values).' AND '.sqlparts("pendingitems",$config,$values);
$tstsort=array('getitemsandparent'=>'cname ASC,timeframeId ASC,'.$sort['getitemsandparent']);
$result = query("getitemsandparent",$config,$values,$tstsort);
$grandtot=count($result);
$index=0;
$lostitems=array();
//Item listings by context and timeframe
foreach ($contextNames as $cid=>$dummy1) {
    foreach ($timeframeNames as $tid=>$dummy2) {
        $maintable=array();
        $wasNAonEntry[$cid][$tid]=array();
        while ($index<$grandtot
                && (    !array_key_exists((int) $result[$index]['contextId'],$contextNames)
                     || !array_key_exists((int) $result[$index]['timeframeId'],$timeframeNames))) {
            array_push($lostitems,$result[$index++]);
        }
		while ($index<$grandtot &&
                (int) $result[$index]['contextId']===$cid &&
                (int) $result[$index]['timeframeId']===$tid ) {
            $row=$result[$index];
            if ($row['NA']) array_push($wasNAonEntry[$cid][$tid],$row['itemId']);
            array_push($maintable,makeContextRow($row));
            $index++;
		}
		$matrixcount[$cid][$tid]=count($maintable);
        if (count($maintable))
            $matrixout[$cid][$tid]=makeContextTable($maintable);
    }
}
$_SESSION['lastfilterp']=$_SESSION['lastfiltera']=basename($thisurl['path']);
if (count($lostitems)) {
    $cid='-1';
    $tid=0;
    $wasNAonEntry[$cid][$tid]=array();
    foreach ($timeframeNames as $thistid=>$dummy1) $matrixcount[$cid][$thistid]=0;
    $contextNames[$cid]="ERROR: Failed to find context";
    $maintable=array();
    $dispArray['spatialcontext']='Context Id';
    $dispArray['timeframe']='Timeframe Id';
    $show['spatialcontext']=true;
    $show['timeframe']=true;
    foreach ($lostitems as $row) {
        $rowout=makeContextRow($row);
        $thisCname=(array_key_exists($row['contextId'],$contextNames))
                    ? $contextNames[$row['contextId']]
                    : 'ERROR unknown space context id='.$row['contextId'];
        $thisTname=(array_key_exists($row['timeframeId'],$timeframeNames))
                    ? $timeframeNames[$row['timeframeId']]
                    : 'ERROR unknown time context id='.$row['timeframeId'];
                    
        $rowout['spatialcontext']   =$thisCname;
        $rowout['spatialcontextId'] =$row['contextId'];
        $rowout['timeframe']        =$thisTname;
        $rowout['timeframeId']      =$row['timeframeId'];
        array_push($maintable,$rowout);
        if ($row['NA']) array_push($wasNAonEntry[$cid][$tid],$row['itemId']);
    }
    $matrixcount[$cid][$tid]=count($maintable);
    $matrixout[$cid][$tid]=makeContextTable($maintable);
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
