<?php
include_once 'headerDB.inc.php';
function makeContextRow($row) {
    $rowout=array();
    $rowout['itemId']=$row['itemId'];
    $rowout['type']='a';
	$rowout['description']=$row['description'];
	$rowout['recurdesc'] = ($row['recurdesc']=="0")?'&nbsp;':$row['recurdesc'];
    if($row['deadline']) {
        $deadline=prettyDueDate($row['deadline'],$row['daysdue']);
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
    $rowout['NA'] = $row['nextaction']==='y';
    return $rowout;
}
function makeContextTable($maintable,$dispArray,$show,$trimlength) {
    @ob_start();
    require 'displayItems.inc.php';
    $out=ob_get_flush();
    return $out;
}
$values=array();

//SQL CODE AREA
//obtain all contexts
$contextResults = query("getspacecontexts",$values);
$contextNames=array(0=>'none');
if ($contextResults)
    foreach ($contextResults as $row)
	   $contextNames[(int) $row['contextId']]=$row['name'];

//obtain all timeframes
$values['type']='a';
$values['timefilterquery'] = ($_SESSION['config']['useTypesForTimeContexts'])?" WHERE ".sqlparts("timetype",$values):'';
$timeframeResults = query("gettimecontexts",$values);
$timeframeNames=array(0=>'none');
$timeframeDesc=array(0=>'none');
if ($timeframeResults) foreach($timeframeResults as $row) {
	$timeframeNames[(int) $row['timeframeId']]=$row['timeframe'];
	$timeframeDesc[(int) $row['timeframeId']]=$row['description'];
	}

$values['extravarsfilterquery'] ='';

$dispArray=array('parent'=>'Project'
    ,'NA'=>'NA'
    ,'title'=>'Action'
    ,'description'=>'Description'
    ,'deadline'=>'Deadline'
    ,'recurdesc'=>'Repeat'
    ,'checkbox'=>'Complete');
$show=array();
foreach ($dispArray as $key=>$val) $show[$key]=true;

$wasNAonEntry=array();
$trimlength=$_SESSION['config']['trimLength'];

$values['type'] = "a";
$values['isSomeday'] = "n";
$values['childfilterquery']  = " WHERE ".sqlparts("typefilter",$values)
                            ." AND ".sqlparts("activeitems",$values)
                            ." AND ".sqlparts("issomeday",$values)
                            ." AND ".sqlparts("pendingitems",$values);

if ($_SESSION['config']["contextsummary"])
    $values['childfilterquery'] .= ' AND '.sqlparts('isNAonly',$values);

$values['filterquery'] =' WHERE '.sqlparts("liveparents",$values);;
$tstsort=array('getitemsandparent'=>'cname ASC,timeframeId ASC,'.$_SESSION['sort']['getitemsandparent']);
$result = query("getitemsandparent",$values,$tstsort);
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
            if ($row['nextaction']==='y') array_push($wasNAonEntry[$cid][$tid],$row['itemId']);
            array_push($maintable,makeContextRow($row));
            $index++;
        }
		$matrixcount[$cid][$tid]=count($maintable);
        if (count($maintable))
            $matrixout[$cid][$tid]=makeContextTable($maintable,$dispArray,$show,$trimlength);
    }
}
$_SESSION['lastfilterp']=$_SESSION['lastfiltera']="{$pagename}.php";
if (count($lostitems)) {
    $cid='-1';
    $tid=0;
    $wasNAonEntry[$cid][$tid]=array();
    foreach ($timeframeNames as $thistid=>$dummy2) $matrixcount[$cid][$thistid]=0;
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
        if ($rowout['NA']) array_push($wasNAonEntry[$cid][$tid],$row['itemId']);
    }
    $matrixcount[$cid][$tid]=count($maintable);
    $matrixout[$cid][$tid]=makeContextTable($maintable,$dispArray,$show,$trimlength);
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser


