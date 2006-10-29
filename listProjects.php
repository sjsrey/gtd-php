<?php

//INCLUDES
	include_once('header.php');


//CONNECT TO DATABASE
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//RETRIEVE URL VARIABLES
$categoryId=(int) $_POST['categoryId'];
$pType=$_GET["pType"]{0};

if ($pType=="s") {
	$completed="n";
	$isSomeday="y";
	$typename="Someday/Maybe";
	}

elseif ($pType=="c") {
	$completed="y";
	$pType="p";
	$isSomeday="n";
	$typename="Projects";
	}

else {
	$completed="n";
	$pType="p";
	$isSomeday="n";
	$typename="Projects";
	}


//SQL CODE
//select all categories for dropdown list
$query = "SELECT categories.categoryId, categories.category, categories.description from categories ORDER BY categories.category ASC";
$result = mysql_query($query) or die("Error in query");
$cshtml="";
while($row = mysql_fetch_assoc($result)) {
        $cshtml .= '	<option value="'.$row['categoryId'].'" title="'.htmlspecialchars(stripslashes($row['description'])).'"';
	if($row['categoryId']==$categoryId) $cshtml .= ' SELECTED';
	$cshtml .= '>'.stripslashes($row['category'])."</option>\n";
        }
mysql_free_result($result);

if ($completed=="y") $compq = "projectstatus.dateCompleted > 0";
else $compq = "(projectstatus.dateCompleted IS NULL OR projectstatus.dateCompleted = '0000-00-00')
                AND (((CURDATE()>=DATE_ADD(projectattributes.deadline, INTERVAL -(projectattributes.suppressUntil) DAY))
                    OR projectattributes.suppress='n'))";

//Select Projects
if ($categoryId==NULL) $categoryId='0';
if ($categoryId=='0') {
	$query="SELECT projects.projectId, projects.name, projects.description, projectattributes.categoryId, categories.category,
		projectattributes.deadline, projectattributes.repeat, projectattributes.suppress, projectattributes.suppressUntil
		FROM projects, projectattributes, projectstatus, categories
		WHERE projectattributes.projectId=projects.projectId AND projectattributes.categoryId=categories.categoryId
		AND projectstatus.projectId=projects.projectId AND projectattributes.isSomeday = '$isSomeday' AND ".$compq."
		ORDER BY categories.category, projects.name ASC";

	$result = mysql_query($query) or die ("Error in query");
	}

else {
	$query="SELECT projects.projectId, projects.name, projects.description, projectattributes.categoryId, categories.category,
		projectattributes.deadline, projectattributes.repeat, projectattributes.suppress, projectattributes.suppressUntil
		FROM projects, projectattributes, projectstatus, categories
		WHERE projectattributes.projectId=projects.projectId AND projectattributes.categoryId=categories.categoryId
		AND projectstatus.projectId=projects.projectId AND (".$compq.") AND projectattributes.categoryId='$categoryId'
		AND projectattributes.isSomeday='$isSomeday'
		ORDER BY categories.category, projects.name ASC";
	$result = mysql_query($query) or die ("Error in query");
	}

//PAGE DISPLAY CODE

	echo '<h2>';
	if ($completed=="y") echo 'Completed&nbsp;'.$typename."</h2>\n";
	else echo '<a href="project.php?type='.$pType.'" title="Add new '.str_replace("s","",$typename).'">'.$typename."</a></h2>\n";

	//category selection form
	echo '<div id="filter">'."\n";
	echo '<form action="listProjects.php?pType='.$pType.'" method="post">'."\n";
	echo "<p>Category:&nbsp;\n";
	echo '<select name="categoryId">'."\n";
	echo '	<option value="0">All</option>'."\n";
	echo $cshtml."</select>\n";
	echo '<input type="submit" class="button" value="Filter" name="submit" title="Filter '.$typename.' by category" />'."\n";
	echo "</p>\n";
	echo "</form>\n";
	echo "</div>\n";

if (mysql_num_rows($result) > 0){

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
	if ($completed!="y") echo "		<td>Completed</td>\n";
	echo "	</thead>\n";

	while($row = mysql_fetch_assoc($result)){
		echo "	<tr>\n";
		echo "		<td>";

//$nonext=nonext($row['projectId']);

                $values['projectId']=$row['projectId'];
                $nexttext=query("selectnextaction",$config,$values);
                if ($nexttext[0]['nextaction']!="") $nonext="false";
                else $nonext="true";
                
		echo '<a href = "projectReport.php?projectId='.$row['projectId'].'" title="Go to '.htmlspecialchars(stripslashes($row['name'])).' project report">';
		if ($nonext=="true" && $completed!="y") echo '<span class="noNextAction" title="No next action defined!">!</span>';
		echo stripslashes($row['name'])."</a></td>\n";
		echo '		<td>'.nl2br(stripslashes($row['description']))."</td>\n";
		echo '		<td><a href="editCategory.php?categoryId='.$row['categoryId'].'" title="Edit the '.htmlspecialchars(stripslashes($row['category'])).' category">'.stripslashes($row['category'])."</a></td>\n";
		echo '		<td>';
                if(($row['deadline']) == "0000-00-00" || $row['deadline']==NULL) $tablehtml .= "&nbsp;";
                elseif(($row['deadline']) < date("Y-m-d") && $completed!="y") echo '<font color="red"><strong title="Project overdue">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
                elseif(($row['deadline']) == date("Y-m-d") && $completed!="y") echo '<font color="green"><strong title="Project due today">'.date("D M j, Y",strtotime($row['deadline'])).'</strong></font>';
                else echo date("D M j, Y",strtotime($row['deadline']));

		echo "</td>\n";
		if ($row['repeat']=="0") echo "		<td>--</td>\n";
		else echo "		<td>".$row['repeat']."</td>\n";
		echo '		<td><a href="project.php?projectId='.$row['projectId'].'" title="Edit '.htmlspecialchars(stripslashes($row['name'])).' project">Edit</a></td>'."\n";
        if ($completed!="y") echo '		<td align="center"><input type="checkbox" align="center" title="Mark '.htmlspecialchars(stripslashes($row['name'])).' project completed. Will hide incomplete associated items." name="completedProj[]" value="'.$row['projectId'].'" /></td>'."\n";
		echo "	</tr>\n";
                }
	echo "</table>\n";
	echo '<input type="hidden" name="referrer" value="l" />'."\n";
	echo '<input type="hidden" name="type" value="'.$pType.'" />'."\n";
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

