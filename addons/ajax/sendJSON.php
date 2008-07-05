<?php
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

/*
    Do query, get data
*/
include_once 'headerDB.inc.php';
$values = array();
if (empty($_REQUEST['itemId'])) exit('{}');
$values['itemId']= (int) $_REQUEST['itemId'];
$values['filterquery']=' WHERE '.sqlparts('singleitem',$values);
$result = query("selectitem",$values);
if (!$result) exit('{}');
$values=$result[0];
/*
    got data, now output JSON value
*/
echo '{';
$sep='';
foreach ($values as $key=>$val) {
    echo $sep,$key,':';
    if (is_array($val)) {
        $sep2='';
        echo '[';
        foreach ($val as $nestedval)
            echo $sep2,'"',escapeforjavascript($nestedval),'"';  // TOFIX escape value
        echo ']';
    } else echo '"',escapeforjavascript($val),'"';  // TOFIX escape value
    $sep=',';
}
exit('}'); // finished, so send closing tag
?>
