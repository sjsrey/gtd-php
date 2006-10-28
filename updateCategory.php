<?php
include_once('header.php');

//need to connect to database to escape data
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

//GET URL AND FORM DATA
$values['categoryId'] = (int) $_GET['categoryId'];
$values['category']=mysql_real_escape_string($_POST['category']);
$values['description']=mysql_real_escape_string($_POST['description']);
$values['delete']=$_POST['delete']{0};
$values['newCategoryId']=(int) $_POST['newCategoryId'];

if ($values['delete']=="y") {
    query("reassigncategory",$config,$values);
    query("deletecategory",$config,$values);
	}

else $result = query("updatecategory",$config,$values);

    if ($result['ecode']=="0") echo "Category ".$values['category']." updated.";
    else {
        echo "Category NOT updated.";
        if ($config['debug']=="true" || $config['debug']=="developer") echo $result['ecode'].": ".$result['etext'];
        }

echo '<META HTTP-EQUIV="Refresh" CONTENT="20; url=listProjects.php"/>';

include_once('footer.php');
?>
