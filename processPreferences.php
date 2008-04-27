<?php
require_once('ses.inc.php');
require_once('gtdfuncs.inc.php');
unset($_POST['submit']);
$newPrefs=$_POST;
// for each checkbox: if value is set at all, set to TRUE, otherwise set to FALSE
foreach ($_POST['checkboxes'] as $val) $newPrefs[$val]=(isset($_POST[$val]));
unset($newPrefs['checkboxes']);
foreach ($newPrefs as $key=>$val) {
    $_SESSION[$key]=$val;
    setcookie($key,$val,time()+31536000);  // = 60*60*24*365 = 365 days to cookie expiry
}
nextScreen('index.php');
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
