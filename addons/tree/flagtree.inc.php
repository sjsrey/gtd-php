<?php
global $titlefull,$values;
$titlefull="$titlefull <a href='"
    .htmlspecialchars("{$addon['urlprefix']}maketree.inc.php&itemId={$values['itemId']}")
    ."'>"
    ."<img src='{$addon['dir']}chart.png' title='Show tree of item and its descendants' alt='flowchart icon' />
    </a>";
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
