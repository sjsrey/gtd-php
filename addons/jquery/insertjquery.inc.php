<?php
global $headertext;
// jquery needs to be near top of header
$jquery="\n<script type='text/javascript' src='{$addon['dir']}jquery.js'></script>\n";
$firstscriptpos=strpos($headertext,'</script>');
if ($firstscriptpos)
    $headertext=substr_replace($headertext,$jquery,$firstscriptpos+9,0);

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
