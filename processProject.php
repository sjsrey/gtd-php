<?php

//INCLUDES
include_once('header.php');
include_once('config.php');

//Connect to database
$connection = mysql_connect($host, $user, $pass) or die ("unable to connect");
mysql_select_db($db) or die ("unable to select database!");

//RETRIEVE FORM VARIABLES
$projectId=(int) $_POST['projectId'];
$name=mysql_real_escape_string($_POST['name']);
$description=mysql_real_escape_string($_POST['description']);
$desiredOutcome=mysql_real_escape_string($_POST['outcome']);
$categoryId=(int) $_POST['categoryId'];
$isSomeday=$_POST['isSomeday']{0};
$deadline=$_POST['deadline'];
$repeat=(int) $_POST['repeat'];
$suppress=$_POST['suppress']{0};
$suppressUntil=(int) $_POST['suppressUntil'];
$type=$_POST['type']{0};

//CRUDE error checking
if ($type=="s") $typename="Someday/Maybe";
else  $typename="Project";
if ($suppress!="y") $suppress="n";
if ($isSomeday!="y") $isSomeday="n";
if ($categoryId<=0) die ("No category choosen. Project NOT added.");

//SQL CODE AREA
//Insert new record
//don't forge null

$addquery = "INSERT INTO projects (name,description,desiredOutcome) VALUES ('$name','$description','$desiredOutcome')";
        $addresult = mysql_query($addquery) or die ("Error in query");
        //Retrieve autoincrement value for projectId
        $projectId = mysql_insert_id();

        $addquery = "INSERT INTO projectattributes (projectId,categoryId,isSomeday,deadline,`repeat`,suppress,suppressUntil) VALUES ('$projectId','$categoryId','$isSomeday','$deadline','$repeat','$suppress','$suppressUntil')";
        $addresult = mysql_query($addquery) or die ("Error in query");

        $addquery = "INSERT INTO projectstatus (projectId,dateCreated) VALUES ('$projectId',CURRENT_DATE)";
        $addresult = mysql_query($addquery) or die ("Error in query");

//Result reporting


if ($type=="s") echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listProjects.php?type=s" />';
else echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listProjects.php?type=p" />';

mysql_close($connection);
include_once('footer.php');
?>
