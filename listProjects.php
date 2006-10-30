<?php

//INCLUDES
	include_once('header.php');


//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE URL VARIABLES
if ($_GET['categoryId']>0) $values['categoryId']=(int) $_GET['categoryId'];
else $values['categoryId']=(int) $_POST['categoryId'];
$values['pType']=$_GET["pType"]{0};

if ($values['pType']=="s") {
	$values['completed']="n";
	$values['isSomeday']="y";
	$typename="Someday/Maybe";
	}

elseif ($values['pType']=="c") {
	$values['completed']="y";
	$values['pType']="p";
	$values['isSomeday']="n";
	$typename="Projects";
	}

else {
	$values['completed']="n";
	$values['pType']="p";
	$values['isSomeday']="n";
	$typename="Projects";
	}

//Check if Session Variables Should be Updated
if ($values['categoryId']>0) $_SESSION['categoryId']=$values['categoryId'];
 else $values['categoryId']=$_SESSION['categoryId'];

//Check if Session Variables Should be Updated
if ($categoryId>0) $_SESSION['categoryId']=$categoryId;
else $categoryId=$_SESSION['categoryId'];


//SQL CODE
$values['filterquery']="";

//select all categories for dropdown list
$result = query("categoryselectbox",$config,$values,$options,$sort);
$cshtml="";
foreach($result as $row) {
        $cshtml .= '   <option value="'.$row['categoryId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
        if($row['categoryId']==$values['categoryId']) $cshtml .= ' SELECTED';
        $cshtml .= '>'.stripslashes($row['category'])."</option>\n";
        }

//Select Projects
    switch ($config['dbtype']) {
        case "frontbase":require("frontbaseparts.inc.php");
        break;
        case "msql":require("msqlparts.inc.php");
        break;
        case "mysql":require("mysqlparts.inc.php");
        break;
        case "mssql":require("mssqlparts.inc.php");
        break;
        case "postgres":require("postgresparts.inc.php");
        break;
        case "sqlite":require("sqliteparts.inc.php");
        break;
        }

if ($categoryId==NULL) $categoryId='0';
if ($categoryId!='0') $values['filterquery'] .= $sqlparts['categoryfilter'];

if ($values['completed']=="y") $values['filterquery'] .= $sqlparts['completedprojects'];
else $values['filterquery'] .= $sqlparts['activeprojects'];

$result = query("selectprojects",$config,$values,$options,$sort);

//PAGE DISPLAY CODE

	echo '<h2>';
	if ($values['completed']=="y") echo 'Completed&nbsp;'.$typename."</h2>\n";
	else echo '<a href="project.php?type='.$values['pType'].'" title="Add new '.str_replace("s","",$typename).'">'.$typename."</a></h2>\n";

	//category selection form
	echo '<div id="filter">'."\n";
	echo '<form action="listProjects.php?pType='.$values['pType'].'" method="post">'."\n";
	echo "<p>Category:&nbsp;\n";
	echo '<select name="categoryId">'."\n";
	echo '	<option value="0">All</option>'."\n";
	echo $cshtml."</select>\n";
	echo '<input type="submit" class="button" value="Filter" name="submit" title="Filter '.$typename.' by category" />'."\n";
	echo "</p>\n";
	echo "</form>\n";
	echo "</div>\n";

if ($result!="-1"){

//Project Update form
	echo "<p>Select project for individual report.</p>\n";
	echo '<form action="processProjectUpdate.php" method="post">'."\n";
	echo "<table class='datatable'>\n";
	echo "	<thead>\n";
	echo "		<td>Title</td>\n";
	echo "		<td>Description</td>\n";
	echo "		<td>Category</td>\n";
	echo "		<td>Deadline</td>\n";
	echo "		<td>Repeat</td>\n";
	echo "		<td>Edit</td>\n";
	if ($values['completed']!="y") echo "		<td>Completed</td>\n";
	echo "	</thead>\n";

	foreach ($result as $row) {
		echo "	<tr>\n";
		echo "		<td>";

//$nonext=nonext($row['projectId']);

                $values['projectId']=$row['projectId'];
                $nexttext=query("selectnextaction",$config,$values);
                //if ($nexttext[0]['nextaction']!="") $nonext="false";
                //else $nonext="true";
                $nonext="false";
                
		echo '<a href = "projectReport.php?projectId='.$row['projectId'].'" title="Go to '.htmlspecialchars(stripslashes($row['name'])).' project report">';
		if ($nonext=="true" && $values['completed']!="y") echo '<span class="noNextAction" title="No next action defined!">!</span>';
		echo stripslashes($row['name'])."</a></td>\n";
		echo '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
		echo '		<td><a href="editCategory.php?categoryId='.$row['categoryId'].'" title="Edit the '.htmlspecialchars(stripslashes($row['category'])).' category">'.stripslashes($row['category'])."</a></td>\n";
		echo '		<td>';
                if(($row['deadline']) == "0000-00-00" || $row['deadline']==NULL) $tablehtml .= "&nbsp;";
                elseif(($row['deadline']) < date("Y-m-d") && $values['completed']!="y") echo '<font color="red"><strong title="Project overdue">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
                elseif(($row['deadline']) == date("Y-m-d") && $values['completed']!="y") echo '<font color="green"><strong title="Project due today">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
                else echo date("D M j, Y",strtotime($row['deadline']));

		echo "</td>\n";
		if ($row['repeat']=="0") echo "		<td>--</td>\n";
		else echo "		<td>".$row['repeat']."</td>\n";
		echo '		<td><a href="project.php?projectId='.$row['projectId'].'" title="Edit '.htmlspecialchars(stripslashes($row['name'])).' project">Edit</a></td>'."\n";
        if ($values['completed']!="y") echo '		<td align="center"><input type="checkbox" align="center" title="Mark '.htmlspecialchars(stripslashes($row['name'])).' project completed. Will hide incomplete associated items." name="completedProj[]" value="'.$row['projectId'].'" /></td>'."\n";
		echo "	</tr>\n";
                }
	echo "</table>\n";
	echo '<input type="hidden" name="referrer" value="l" />'."\n";
	echo '<input type="hidden" name="type" value="'.$values['pType'].'" />'."\n";
	echo '<input type="hidden" name="categoryId" value="'.$categoryId.'" />'."\n";
	echo '<input type="submit" class="button" value="Complete '.$typename.'" name="submit" />'."\n";
	echo "</form>\n";
        }

//        else {
//            $message="Nothing was found.";
//           nothingFound($message);
//             }
        
        else {
                $message="You have no ".$typename." remaining.";
                $prompt="Would you like to create a new ".str_replace("s","",$typename)."?";
                $yeslink="project.php?type=p";
                nothingFound($message,$prompt,$yeslink);
        }

	include_once('footer.php');
?>

