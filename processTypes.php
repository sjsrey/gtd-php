<?php
require_once 'headerDB.inc.php';
ignore_user_abort(true);
$docapture= (isset($_POST['output']) && $_POST['output']==='xml');
if ($docapture) @ob_start();

// some debugging - if debug is set to halt, dump all the variables we've got
if ($_SESSION['debug']['debug']) {
    $title='Process changes to hierarchy';
    include_once 'headerHtml.inc.php';
    echo "</head><body>";
    log_array('$_POST','$_SESSION');
    $html=true; // indicates if we are outputting html
} else {
    $html=false;
}
$levels=array('m','v','o','g');
$save=$_SESSION['hierarchy'];
if (isset($_POST['L0p8'])) {
    /*
        ======================================================================
        user has requested reset to default values
    */
    resetHierarchy();
    $nextURL= 'index.php';
    $updatetype='reverted hierarchy';
} else if (isset($_POST["label{$levels[0]}"])) {
    /*
        ======================================================================
        changing labels for levels in the hierarchy
    */
    resetHierarchyNames();
    foreach ($levels as $type)
        if ( !empty($_POST["label$type"]) )
            $_SESSION['hierarchy']['names'][$type]=makeclean($_POST["label$type"]);
    $nextURL= (isset($_POST['types2'])) ? 'types2.php' : 'index.php';
    $updatetype='level names';
} else {
    /*
        ======================================================================
        changing relationships between the levels
    */
    $_SESSION['hierarchy']['children']=$_SESSION['hierarchy']['parents']=array();
    foreach ($_SESSION['hierarchy']['names'] as $type=>$typename)
        $_SESSION['hierarchy']['children'][$type]=array();
    if (!empty($_POST['parentchild'])) foreach ($_POST['parentchild'] as $PCpair) {
        // process parent-child relationships
        $_SESSION['hierarchy']['children'][substr($PCpair,0,1)][]=substr($PCpair,1,1);
    }
    mirrorParentTypes();
    $suppressAsOrphan='';
    if (!empty($_POST['suppressAsOrphan'])) {
        preg_match_all('/[a-zA-Z0-9]*/',
                    implode('',$_POST['suppressAsOrphan']),
                    $suppressAsOrphan);
        $suppressAsOrphan=implode('',$suppressAsOrphan[0]);
    }
    $_SESSION['hierarchy']['suppressAsOrphans']=$suppressAsOrphan;

    $nextURL= 'index.php';
    $updatetype='relationships between levels';
}
/*
    ======================================================================
    Now save the changes, whatever they were, to the preferences table
*/
if ($save===$_SESSION['hierarchy']) { // session var is same now as before applying POSTed variables, so no change have been made
    $result=true;
    $_SESSION['message'][]="No changes made to $updatetype";
} else {
    $result=saveConfig();
    $_SESSION['message'][]= ($result)
        ? "Saved changes to $updatetype"
        : "Failed to save $updatetype in database table: those changes will be lost when you close the browser";
}

if ($docapture) {
    $logtext=ob_get_contents();
    ob_end_clean();
    $outtext=$_SESSION['message'];
    $_SESSION['message']=array();
    if (!headers_sent()) {
        $header="Content-Type: text/xml; charset=".$_SESSION['config']['charset'];
        header($header);
    }
    echo '<?xml version="1.0" ?',">\n<gtdphp>\n"; // encoding="{$_SESSION['config']['charset']}"
    echo '<result>';
    if (!empty($outtext)) foreach ($outtext as $line) echo "<line><![CDATA[$line]]></line>";
    echo '</result>';
    echo "<nextURL><![CDATA[$nextURL]]></nextURL>";
    echo "<log><![CDATA[$logtext]]></log>";
    echo "</gtdphp>";
    exit;
} else {
    nextScreen($nextURL);
    if ($html) include_once 'footer.inc.php';
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
