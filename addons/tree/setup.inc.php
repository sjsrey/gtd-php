<?php
$events[_GTD_ON_MENU]['*']=array(
    'link'   => "maketree.inc.php",
    'title'  => 'Show the entire hierarchy tree of all items',
    'label'  => 'Map tree',
    'where'  => 'listItems.php?type=m',
    'when'   => 'after',
    'options'=> array()
);
$events[_GTD_ON_DATA]['itemReport']="flagtree.inc.php";

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
