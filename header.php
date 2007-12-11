<?php
require_once("headerDB.inc.php");

if ($_SESSION['version']!==_GTD_VERSION) {
    $testver=query('getgtdphpversion',$config);
    if ($testver && _GTD_VERSION === array_pop(array_pop($testver)) ) {
        $_SESSION['version']=_GTD_VERSION;
    } else {
        $msg= ($testver)
                ? "<p class='warning'>Your version of the database needs upgrading before we can continue.</p>"
                : "<p class='warning'>No gtd-php installation found: please check the database prefix in config.php, and then install.</p>";
        $_SESSION['message']=array($msg); // remove warning about version not being found
        nextScreen('install.php');
        die;
    }
}

require_once("headerHtml.inc.php");
echo "</head><body><div id='container'>\n";
require_once("headerMenu.inc.php");
echo "<div id='main'>\n";
if ($config['debug'] & _GTD_DEBUG)
    echo '<br /><hr /><pre>Session:',print_r($_SESSION,true)
        ,'<br />Post:',print_r($_POST,true),'</pre><hr />';
include_once('showMessage.inc.php');
?>
