<?php
require_once 'ses.inc.php';
$newPrefs=$_POST;
// some variables are stored as cookies locally, rather than in the db
$cookievars=array('theme','useLiveEnhancements');
foreach ($cookievars as $key) {
    $val=$_SESSION[$key]=$newPrefs[$key];
    setcookie($key,$val,time()+31536000);  // = 60*60*24*365 = 365 days to cookie expiry
    unset($newPrefs[$key]);
}

require_once 'headerDB.inc.php';
if ($_SESSION['debug']['debug']) {
    include 'headerHtml.inc.php';
    echo '</head><body><pre>POST: ',print_r($_POST,true),
        '<br/>',
        'session pre-update: ',print_r($_SESSION,true),'</pre>';
}

unset($newPrefs['submit']);

// for each checkbox: if value is set at all, set to TRUE, otherwise set to FALSE
$checkboxes=explode(',',$newPrefs['checkboxes']);
unset($newPrefs['checkboxes']);
array_pop($checkboxes);
foreach ($checkboxes as $val)
    $newPrefs[$val]=(isset($newPrefs[$val]));

$prefixes=array('keys','sort','debg');
$preflen=array();
foreach ($prefixes as $key=>$prefix) {
    $preflen[$key]=strlen($prefix);
}
$_SESSION['debug']=$_SESSION['sort']=$_SESSION['keys']=$_SESSION['config']=array();

// apply the preferences to this session
foreach ($newPrefs as $key=>$val) {
    switch (substr($key,0,4)) {
        case 'lkey':
            $index=null;
            break;
        case 'keys':
            $sessfield='keys';
            $index= (empty($val)) ? null : $_POST["l$key"];
            break;
        case 'sort':
            $sessfield='sort';
            $index=substr($key,4);
            break;
        case 'debu':
            $sessfield='debug';
            $index=substr($key,5);
            break;
        default:
            $index=$key;
            $sessfield='config';
            break;
    }
    if ($index!==null) $_SESSION[$sessfield][$index]=$val;
}

if ($_SESSION['debug']['debug'])
    echo '<pre>Changed preferences stored in session: ',print_r($_SESSION,true),'</pre>';

$result=saveConfig(); // store preferences in the table
$_SESSION['message'][]= ($result) ? 'Preferences updated' : 'No changes made to preferences stored in database';

nextScreen('index.php');
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
