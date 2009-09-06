<?php
@ob_start();
require_once 'headerDB.inc.php';

// make a safe copy of the prefix variable, because we might have to temporarily
// change it during the backup process
$saveprefix=$_SESSION['prefix'];

if (array_key_exists('prefix',$_REQUEST))
    $_SESSION['prefix'] = $_REQUEST['prefix'];

// get the character set for the specified dataset
$optionarray=query('getoptions',array('uid'=>$_SESSION['uid'],'filterquery'=>"AND `option`='config'") );
$tableconfig = unserialize($optionarray[0]['value']);
$charset=(empty($tableconfig['charset'])) ? '' : "charset={$tableconfig['charset']}";
@ob_end_clean();

// output the database backup
header("Content-type: text/plain; $charset");
header('Content-Disposition: attachment; filename="gtdphpBackup'
        .date("Y-m-d").'.sql"');
echo backupData($_SESSION['prefix']);


// restore the prefix variable to what it should be
$_SESSION['prefix']=$saveprefix;