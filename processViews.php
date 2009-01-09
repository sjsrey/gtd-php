<?php
require_once 'headerDB.inc.php';
ignore_user_abort(true);
$captureOutput=(isset($_REQUEST['output']) && $_REQUEST['output']==='xml');
if ($captureOutput) {
    @ob_start();
}
if ($_SESSION['debug']['debug'] && !$captureOutput) {
    $html=true;
    include_once 'headerHtml.inc.php';
    echo "</head><body><div><div>";
} else {
    $html=false;
}
log_array('$_POST','$_GET');
// process REQUEST variables
$values=array();
foreach (array('uri'=>'','name'=>'from ProcessViews','sort'=>'','show'=>'','columns'=>'') as $field=>$default)
    $values[$field]= (array_key_exists($field,$_REQUEST))
        ? ( is_array($_REQUEST[$field])
            ? implode(',',$_REQUEST[$field])
            : $_REQUEST[$field] )
        : $default;
// save perspective to database
$success=savePerspective($values);

if ($captureOutput) {
    $logtext=ob_get_contents();
    ob_end_clean();
    $outtext=$_SESSION['message'];
    $_SESSION['message']=array();
    if (!headers_sent()) {
        $header="Content-Type: text/xml; charset=".$_SESSION['config']['charset'];
        header($header);
    }
    echo '<?xml version="1.0" ?','><gtdphp>' // encoding="{$_SESSION['config']['charset']}"
        ,'<values><success>',(true && $success),'</success></values><result>';
    if (!empty($outtext)) foreach ($outtext as $line) echo "<line><![CDATA[$line]]></line>";
    echo '</result>'
        ,"<log><![CDATA[$logtext]]></log>"
        ,"</gtdphp>";
    exit;
} else {
    nextScreen('summary.php');
    if ($html) include_once 'footer.inc.php';
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
