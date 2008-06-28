<?php
function cleanpref($field) {
    return str_replace(array("\x00","\n","\r","'",'"',"\x1a"),'', stripslashes($field));
}
$_SESSION['config']['title']='Updating preferences'; // force it to be non-blank to avoid unnecessary options retrieval in headerDB
require_once 'headerDB.inc.php';
ignore_user_abort(true);

if (isset($_REQUEST['restoredefaults'])) {
    $_SESSION['message'][]= (importOldConfig())
        ? 'Reverted to default preferences'
        : 'Failed to find default preferences in either config.php or defaultconfig.inc.php';
    nextScreen('preferences.php');
    exit;
}

$newPrefs=$_POST;
unset($newPrefs['submit']);

// for each checkbox: if value is set at all, set to TRUE, otherwise set to FALSE
$checkboxes=explode(',',$newPrefs['checkboxes']);
unset($newPrefs['checkboxes']);
array_pop($checkboxes);
foreach ($checkboxes as $val)
    $newPrefs[$val]=(isset($newPrefs[$val]));

// some variables are stored as cookies locally, rather than in the db
$cookievars=array('theme','useLiveEnhancements');
foreach ($cookievars as $key) {
    $val=$_SESSION[$key]=$newPrefs[$key];
    setcookie($key,$val,time()+160000000);  // = roughly 4 years to cookie expiry
}

if ($_SESSION['debug']['debug']) {
    include 'headerHtml.inc.php';
    echo '</head><body><pre>POST: ',print_r($_POST,true),
        '<br/>',
        'session pre-update: ',print_r($_SESSION,true),'</pre>';
}

$_SESSION['addons']=$_SESSION['debug']=$_SESSION['sort']=$_SESSION['keys']=
    $_SESSION['config']=array();

/* -------------------------------------------------------------------
     apply the preferences to this session
*/
foreach ($newPrefs as $key=>$val) {
    switch (substr($key,0,4)) {
    
        case 'addo': // addon activated
            if ($val) getEvents(substr($key,6));
            break;
            
        case 'debu': // set one of the debug controls
            $_SESSION['debug'][substr($key,5)]=$val;
            break;
            
        case 'keys': // assign a shortcut key
            if (!empty($val))
                $_SESSION['keys'][$_POST["l$key"]]=$val;
            break;
            
        case 'lkey':  // value is used when assigning keys, no processing needed here
            break;
            
        case 'sort': // sorting tables in listItems, reportContext, etc
            $_SESSION['sort'][substr($key,4)]=cleanpref($val);
            break;
            
        default: // standard config item
            $_SESSION['config'][$key]=$val;
            break;
    }
}

$config['separator']=cleanpref($config['separator']);

if  (strtolower($_SESSION['config']['charset'])==='utf-8') checkUTF8();

if ($_SESSION['debug']['debug'])
    echo '<pre>Changed preferences stored in session: ',print_r($_SESSION,true),'</pre>';

$result=saveConfig(); // store preferences in the table
$_SESSION['message'][]= ($result) ? 'Preferences updated' : 'No changes made to preferences stored in database';

nextScreen('index.php');
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
