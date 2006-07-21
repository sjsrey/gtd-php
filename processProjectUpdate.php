<?php
//INCLUDES
include_once('header.php');
include_once('config.php');

//GET URL VARIABLES
$categoryId=(int) $_POST['categoryId'];
$type=$_POST['type']{0};
$referrer=$_POST['referrer']{0};
$projectId=(int) $_GET['projectId'];

//GET FORM VARIABLES
$completedProj = $_POST['completedProj'];

//SQL CODE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

if(isset($completedProj)){
	$today=strtotime("now");
	$date=date('Y-m-d');
        foreach ($completedProj as $completedPr) {
                echo "Updating project: ";
                echo $completedPr.'<br>';

//test to see if project is repeating
		$testquery = "SELECT projectattributes.repeat FROM projectattributes WHERE projectattributes.projectId='$completedPr'";
		$testresult = mysql_query($testquery) or die ("Error in query");
		$testrow = mysql_fetch_assoc($testresult);		

//if repeating, copy result row to new row (new project) with updated due date

		if ($testrow['repeat']!=0) {
			
			$nextdue=strtotime("+".$testrow['repeat']."day");
			$nextduedate=gmdate("Y-m-d", $nextdue);

			//retrieve project details
			$copyquery = "SELECT projects.name, projects.description FROM projects WHERE projects.projectId='$completedPr'";
			$copyresult = mysql_query($copyquery) or die ("Error in query");
			$copyproject = mysql_fetch_assoc($copyresult);

			//copy data to projects table with new id
			$addquery = "INSERT INTO `projects` (name,description) VALUES ('".$copyproject['name']."','".$copyproject['description']."')";
			$addresult = mysql_query($addquery) or die ("Error in query");
			$newprojectId = mysql_insert_id();

			//retrieve project attributes
			$copyquery = "SELECT projectattributes.projectId, projectattributes.categoryId, 
					projectattributes.isSomeday, projectattributes.deadline, 
					projectattributes.repeat, projectattributes.suppress, projectattributes.suppressUntil
					FROM projectattributes WHERE projectattributes.projectId='$completedPr'";
			$copyresult = mysql_query($copyquery) or die ("Error in query");
			$copyattributes = mysql_fetch_assoc($copyresult);

			//copy data to projectattributes table with newid and new due date
			$addquery = "INSERT INTO `projectattributes` (projectId,categoryId,isSomeday,deadline,`repeat`,suppress,suppressUntil) VALUES ('$newprojectId','".$copyattributes['categoryId']."','".$copyattributes['isSomeday']."','$nextduedate','".$copyattributes['repeat']."','".$copyattributes['suppress']."','".$copyattributes['suppressUntil']."')";
			$addresult = mysql_query($addquery) or die ("Error in query");

			//add newid to projectstatus table
			$addquery = "INSERT INTO `projectstatus` (projectId,dateCreated) VALUES ('$newprojectId','$date')";
			$addresult = mysql_query($addquery) or die ("Error in query");
               		}

		//in either case, set original row completed
                $query= "UPDATE projectstatus SET dateCompleted='$date' where projectId='$completedPr'";
                $result = mysql_query($query) or die ("Error in query");
        	}
	}

if ($referrer=="p") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=projectReport.php?projectId='.$projectId.'">';
	}

elseif ($referrer=="l") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listProjects.php?pType='.$type.'">';
	}

elseif ($referrer=="c") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=reportContext.php">';
	}

elseif ($referrer=="t") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=tickler.php">';
	}

mysql_close($connection);
include_once('footer.php');
?>
