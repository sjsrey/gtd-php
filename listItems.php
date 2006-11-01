<?php

//INCLUDES
include_once('header.php');

//GET URL VARIABLES
$values['type']=$_GET["type"]{0};
$values['pType']=$_GET["pType"]{0};
if ($values['pType']!="s") $values['pType']="p";
if ($_GET['timeId']>0) $values['timeframeId']=(int) $_GET['timeId'];
else $values['timeframeId']=(int) $_POST['timeId'];

$values['notspacecontext']=$_POST['notspacecontext'];
$values['nottimecontext']=$_POST['nottimecontext'];
$values['notcategory']=$_POST['notcategory'];


if ($values['pType']=='s') $values['ptypequery']='y';
else $values['ptypequery']='n';

//Check Session Variables
if ($_GET['contextId']>0) $values['contextId']=(int) $_GET['contextId'];
else $values['contextId']=(int) $_POST['contextId'];

//If we have contextId from a new filter, change Session value
$contextId=$values['contextId'];
if ($contextId>=0) $_SESSION['contextId']=$contextId;
else $values['contextId']=$_SESSION['contextId'];

//ditto for category

//Check Session Variables
if ($_GET['categoryId']>0) $values['categoryId']=(int) $_GET['categoryId'];
else $values['categoryId']=(int) $_POST['categoryId'];

//If we have categoryId from a new filter, change Session value
$categoryId=$values['categoryId'];
if ($categoryId>=0) $_SESSION['categoryId']=$categoryId;
else $values['categoryId']=$_SESSION['categoryId'];

//Set page titles
if ($values['type']=="a") {
	$typename="Actions";
	$values['typequery']="a";
	}
elseif ($values['type']=="n") {
	$typename="Next Actions";
	$display="nextonly";
	$values['typequery']="a";
	}
elseif ($values['type']=="r") {
	$typename="References";
	$values['typequery']="r";
	}
elseif ($values['type']=="w") {
	$typename="Waiting On";
	$values['typequery']="w";
	}
else {
	$typename="Items";
	$values['typequery']="a";
	}

//SQL CODE

//select space contexts for dropdown list
$result = query("spacecontextselectbox",$config,$values,$options,$sort);
$cshtml="";
    foreach($result as $row) {
        $cshtml .= '                    <option value="'.$row['contextId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
        if($row['contextId']==$values['contextId']) $cshtml .= ' SELECTED';
        $cshtml .= '>'.stripslashes($row['name'])."</option>\n";
        }

//select time contexts for dropdown list
$result = query("timecontextselectbox",$config,$values,$options,$sort);
$tshtml="";
foreach($result as $row) {
    $tshtml .= '                    <option value="'.$row['timeframeId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
    if($row['timeframeId']==$values['timeframeId']) $tshtml .= ' SELECTED';
    $tshtml .= '>'.stripslashes($row['timeframe'])."</option>\n";
    }

//select all categories for dropdown list
$cashtml=categoryselectbox($config,$values,$options,$sort);

//select all nextactions for test
$result = query("getnextactions",$config,$values,$options,$sort);

if ($result!="-1") {
    foreach ($result as $row) {
    //populates $nextactions with itemIds using projectId as key
        $nextactions[$row['projectId']] = $row['nextaction'];
        }
    }
else $nextactions[0] = NULL;

//Select items

//set query fragments based on filters
$values['filterquery'] = "";

if ($values['categoryId'] != NULL && $values['notcategory']!="true") $values['filterquery'] .= sqlparts("categoryfilter",$config,$values);
if ($values['categoryId'] != NULL && $values['notcategory']=="true") $values['filterquery'] .= sqlparts("notcategoryfilter",$config,$values);

if ($values['contextId'] != NULL && $values['notcategory']!="true") $values['filterquery'] .= sqlparts("contextfilter",$config,$values);
if ($values['contextId'] != NULL && $values['notcategory']=="true") $values['filterquery'] .= sqlparts("notcontextfilter",$config,$values);

if ($values['timeframeId'] != NULL && $values['notcategory']!="true") $values['filterquery'] .= sqlparts("timeframefilter",$config,$values);
if ($values['timeframeId'] != NULL && $values['notcategory']=="true") $values['filterquery'] .= sqlparts("nottimeframefilter",$config,$values);

//Get items for display
$result = query("getitems",$config,$values,$options,$sort);

//PAGE DISPLAY CODE
	echo '<h2><a href="item.php?type='.$values['type'].'" title="Add new '.str_replace("s","",$typename).'">'.$typename."</a></h2>\n";
	echo '<form action="listItems.php?type='.$values['type'].'" method="post">'."\n";
	echo "<p>Category:&nbsp;\n";
	echo '<select name="categoryId" title="Filter items by project category">'."\n";
	echo '	<option value="">All</option>'."\n";
	echo $cashtml;
	echo "</select>\n";
        echo '<input type="checkbox" name="notcategory" title="Exclude category from list" value="true"';
        if ($values['notcategory']=="true") echo " CHECKED";
        echo '> NOT'."\n";
	echo "&nbsp;&nbsp;&nbsp;\nContext:&nbsp;\n";
	echo '<select name="contextId" title="Filter items by context">'."\n";
	echo '	<option value="">All</option>'."\n";
	echo $cshtml;
	echo "</select>\n";
        echo '<input type="checkbox" name="notspacecontext" title="Exclude spatial context from list" value="true"';
        if ($values['notspacecontext']=="true") echo " CHECKED";
        echo '> NOT'."\n";
        echo "&nbsp;&nbsp;&nbsp;\nTime:&nbsp;\n";
	echo '<select name="timeId" title="Filter items by time context">'."\n";
	echo '	<option value="">All</option>'."\n";
	echo $tshtml;
	echo "</select>\n";
        echo '<input type="checkbox" name="nottimecontext" title="Exclude time context from list" value="true"';
        if ($values['nottimecontext']=="true") echo " CHECKED";
        echo '> NOT'."\n";
        echo '&nbsp;&nbsp;&nbsp;<input type="submit" class="button" value="Filter" name="submit" title="Filter '.$typename.' by category and/or contexts">'."\n";
	echo "</p>\n";
	echo "</form>\n\n";

	if ($result!="-1") {
                $tablehtml="";
                foreach ($result as $row) {
			$showme="y";
			//filter out all but nextactions if $display=nextonly
			if (($display=='nextonly')  && !($key = array_search($row['itemId'],$nextactions))) $showme="n";
			if($showme=="y") {
				$tablehtml .= "	<tr>\n";
				$tablehtml .= '		<td><a href = "projectReport.php?projectId='.$row['projectId'].'"title="Go to '.htmlspecialchars(stripslashes($row['pname'])).' project report">'.stripslashes($row['pname'])."</a></td>\n";

				//if nextaction, add icon in front of action (* for now)
                                if ($key = array_search($row['itemId'],$nextactions)) $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">*&nbsp;'.stripslashes($row['title'])."</td>\n";
				else $tablehtml .= '		<td><a href = "item.php?itemId='.$row['itemId'].'" title="Edit '.htmlspecialchars(stripslashes($row['title'])).'">'.stripslashes($row['title']).'</td>';
				$tablehtml .= '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
				$tablehtml .= '		<td><a href = "editContext.php?contextId='.$row['contextId'].'" title="Go to '.htmlspecialchars(stripslashes($row['cname'])).' context report">'.stripslashes($row['cname'])."</td>\n";
				$tablehtml .= "		<td>";
				if(($row['deadline']) == "0000-00-00" || $row['deadline'] ==NULL) $tablehtml .= "&nbsp;";
				elseif(($row['deadline']) < date("Y-m-d")) $tablehtml .= '<font color="red"><strong title="Item overdue">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';  //highlight overdue actions
				elseif(($row['deadline']) == date("Y-m-d")) $tablehtml .= '<font color="green"><strong title="Item due today">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>'; //highlight actions due today
				else $tablehtml .= date("D M j, Y",strtotime($row['deadline']));
				$tablehtml .= "</td>\n";
				if ($row['repeat']=="0") $tablehtml .= "		<td>--</td>\n";
				else $tablehtml .= "		<td>".$row['repeat']."</td>\n";
	            $tablehtml .= '		<td align="center"><input type="checkbox" align="center" title="Complete '.htmlspecialchars(stripslashes($row['title'])).'" name="completedNas[]" value="';
                $tablehtml .= $row['itemId'];
                $tablehtml .= '" /></td>'."\n";
				$tablehtml .= "	</tr>\n";
			}
		}

		if ($tablehtml!="") {
			echo '<form action="processItemUpdate.php" method="post">'."\n";
			echo "<table class='datatable'>\n";
			echo "	<thead>\n";
			echo "		<td>Project</td>\n";
			echo "		<td>".$typename."</td>\n";
			echo "		<td>Description</td>\n";
			echo "		<td>Context</td>\n";
			echo "		<td>Deadline</td>\n";
			echo "		<td>Repeat</td>\n";
			echo "		<td>Completed</td>\n";
			echo "	</thead>\n";
			echo $tablehtml;
			echo "</table>\n";
			echo '<input type="hidden" name="type" value="'.$values['type'].'" />'."\n";
			echo '<input type="hidden" name="contextId" value="'.$values['contextId'].'" />'."\n";
			echo '<input type="hidden" name="timeId" value="'.$values['timeframeId'].'" />'."\n";
                        echo '<input type="hidden" name="contextId" value="'.$values['contextId'].'" />'."\n";
                        echo '<input type="hidden" name="categoryId" value="'.$values['categoryId'].'" />'."\n";
			echo '<input type="hidden" name="referrer" value="i" />'."\n";
			echo '<input type="submit" class="button" value="Complete '.$typename.'" name="submit">'."\n";
			echo "</form>\n";
		}else{
			$message="Nothing was found.";
			nothingFound($message);
		}
	}else{
		$message="You have no ".$typename." remaining.";
		$prompt="Would you like to create a new ".str_replace("s","",$typename)."?";
		$yeslink="item.php?type=".$values['type'];
		nothingFound($message,$prompt,$yeslink);
	}

	include_once('footer.php');
?>
