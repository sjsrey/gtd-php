<?php
$events[_GTD_ON_MENU]['*']=array(
    'link'   => "achievements.inc.php",
    'title'  => 'Notable Achievements',
    'label'  => 'Achievements',
    'where'  => 'listItems.php?type=*&amp;tickler=true&amp;liveparents=*',
    'when'   => 'after',
    'options'=> array('jpgraphdir'=>'../jpgraph/')
);
