<?php

$values=array();

//SQL CODE AREA
//obtain all contexts
$contextResults = query("getspacecontexts",$config,$values,$sort);
$contextNames=array(0=>'none');
if ($contextResults)
    foreach ($contextResults as $row)
	   $contextNames[$row['contextId']]=makeclean($row['name']);

//obtain all timeframes
$values['type']='a';
$values['timefilterquery'] = ($config['useTypesForTimeContexts'])?" WHERE ".sqlparts("timetype",$config,$values):'';
$timeframeResults = query("gettimecontexts",$config,$values,$sort);
$timeframeNames=array(0=>'none');
$timeframeDesc=array(0=>'none');
if ($timeframeResults) foreach($timeframeResults as $row) {
	$timeframeNames[$row['timeframeId']]=makeclean($row['timeframe']);
	$timeframeDesc[$row['timeframeId']]=makeclean($row['description']);
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

//Item listings by context and timeframe
foreach ($contextNames as $values['contextId'] => $contextname) {
    foreach ($timeframeNames as $values['timeframeId'] => $timeframename) {

        $values['type'] = "a";
        $values['isSomeday'] = "n";
        $values['childfilterquery']  = " WHERE ".sqlparts("typefilter",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("activeitems",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("timeframefilter",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("contextfilter",$config,$values);
        $values['childfilterquery'] .= " AND ".sqlparts("issomeday",$config,$values);
		$values['childfilterquery'] .= " AND ".sqlparts("pendingitems",$config,$values);
        $values['parentfilterquery'] = '';
        $result = query("getitemsandparent",$config,$values,$sort);

        $maintable=array();
        $i=0;
        $wasNAonEntry[$values['contextId']][$values['timeframeId']]=array();
		if (is_array($result)) foreach ($result as $row) {
            $maintable[$i]=array();
            $maintable[$i]['itemId']=$row['itemId'];
			$maintable[$i]['description']=$row['description'];
			$maintable[$i]['repeat'] = ($row['repeat']=="0")?'&nbsp;':$row['repeat'];

            if($row['deadline']) {
                $deadline=prettyDueDate($row['deadline'],$config['datemask']);
                $maintable[$i]['deadline'] =$deadline['date'];
                $maintable[$i]['deadline.class']=$deadline['class'];
                $maintable[$i]['deadline.title']=$deadline['title'];
            } else $maintable[$i]['deadline']='';
        
            $maintable[$i]['title']=$row['title'];
            $maintable[$i]['title.title']='Edit';

			$maintable[$i]['ptitle']=$row['ptitle'];
			$maintable[$i]['parentId']=$row['parentId'];
			if ($row['parentId']=='') $maintable[$i]['parent.class']='noparent';

			$maintable[$i]['checkboxname']='isMarked[]';
			$maintable[$i]['checkbox.title']='Mark as complete';
			$maintable[$i]['checkboxvalue']=$row['itemId'];

            $maintable[$i]['NA'] = $row['NA'];
            if ($row['NA']) array_push($wasNAonEntry[$values['contextId']][$values['timeframeId']],$row['itemId']);

			$i++;
		}
		$matrixcount[$values['contextId']][$values['timeframeId']]=count($maintable);
        if (count($maintable)) {
            ob_start();
            require('displayItems.inc.php');
            $matrixout[$values['contextId']][$values['timeframeId']]=ob_get_contents();
            ob_end_clean();
        }
    }
}
$_SESSION['lastfilterp']=$_SESSION['lastfiltera']=basename($thisurl['path']);

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
