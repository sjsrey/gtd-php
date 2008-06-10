<?php
require_once 'headerDB.inc.php';
ignore_user_abort(true);
$html=false;
if ($_SESSION['debug']['debug']) {
    $html=true;
    include_once 'headerHtml.inc.php';
    echo "</head><body><pre>\n",print_r($_POST,true),"</pre>\n";
}

$values=array();
$field=$_POST['field'];

if (isset($_POST['id'])) {
    $values['id']=(int) $_POST['id'];
    $values['name']=$_POST['name'];
    $values['description']=$_POST['description'];
    switch ($field) {
        case 'category':
            $query='category';
            $getId='category';
            break;
        case 'context':
            $query='spacecontext';
            $getId='context';
            break;
        case 'time-context':
            $query='timecontext';
            $getId='timecontext';
            if ($_SESSION['config']['useTypesForTimeContexts'] && isset($_POST['type']) && $_POST['type']!='')
                $values['type']=$_POST['type'];
            else
                $values['type']='a';
            break;
        default:
            break;
    }
    if ($values['id']==0) {
        $result = query("new$query",$values);
        $msg='Created';
    } elseif (isset($_POST['delete']) && $_POST['delete']==="y") {
        $values['newId']=(int) $_POST['replacewith'];
        $result=query("reassign$query",$values);
        if ($result!==false) $result=query("delete$query",$values); // don't delete if reassign fails
        $msg='Deleted';
    } else {
        $result=query("update$query",$values);
        $msg='Updated';
    }
} // end of: if (isset($_POST['id']))
if ($result) $_SESSION['message'][]="$msg $field '{$values['name']}'";

$nexturl="editCat.php?field=$field";
if (isset($_POST['next']))
    $nexturl.='&id='.$_POST['next'];
nextScreen($nexturl);

if ($html)
    include_once 'footer.inc.php';
else
    echo '</head></html>';

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
