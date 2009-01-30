<?php
$events[_GTD_ON_MENU]['*']=array(
    'link'   => "actionBooklet.inc.php",
    'title'  => 'Action Booklet',
    'label'  => 'ActionBooklet',
    'where'  => 'listItems.php?type=m',
    'when'   => 'after',
    'options'=> array('fpdfpath' =>'../fpdf/',// relative path to the FPDF installation
                      'papersize'=>'letter',  // paper size - letter or A4
                      'fontname' =>'Arial',   // font name
                      'fontsize' => 6,        // font size in points
                      'nextonly' => true      // set to true to restrict listing to NEXT actions only
                      )
);