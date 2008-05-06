<?php
require_once 'headerHtml.inc.php';
echo "</head><body><div id='container'>\n";
require_once 'headerMenu.inc.php';
echo "<div id='main'>\n";
if ($config['debug'] & _GTD_DEBUG)
    echo '<br /><hr /><pre>Session:',print_r($_SESSION,true)
        ,'<br />Post:',print_r($_POST,true),'</pre><hr />';
include_once 'showMessage.inc.php';
?>
