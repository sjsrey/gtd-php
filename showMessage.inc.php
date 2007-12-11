<?php
if (count($_SESSION['message'])) {
    if (is_array($_SESSION['message'])) {
        include_once('gtdfuncs.php');
        echo "<div class='success'>\n";
        foreach ($_SESSION['message'] as $msg)
            echo stripslashes(escapeChars($msg)),"<br />\n";
        echo "</div>";
    }
    $_SESSION['message']=array();
}
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
