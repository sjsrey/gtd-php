<?php
define('_GTD_REVISION',461);
define('_GTD_VERSION','0.8z.04'); // DATABASE version
define('_GTDPHP_VERSION','0.9alpha');  // gtd-php version, as per the TRAC system

// binary debug flags, to combine with a logical "or": "|"
define('_GTD_WAIT'    ,1);
define('_GTD_DEBUG'   ,2);
define('_GTD_FREEZEDB',4);
define('_GTD_NOTICE'  ,8);

// events
define('_GTD_ON_HEADER','_GTD_ON_HEADER');
define('_GTD_ON_DATA','_GTD_ON_DATA');
define('_GTD_ON_FOOTER','_GTD_ON_FOOTER');

// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
