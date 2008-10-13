<?php
require_once 'headerHtml.inc.php';
echo "</head><body>\n";
require_once 'headerMenu.inc.php';
echo "<div id='main'>\n";
log_array('$_SESSION','$_POST');
include_once 'showMessage.inc.php';
echo checkRegisterGlobals();
?>
