<?php
gtd_handleEvent(_GTD_ON_FOOTER,$pagename);
include 'showMessage.inc.php';
?>
</div><!-- main -->
</div><!-- container -->
<div id='footer'>
<?php
    global $totalquerytime,$starttime;
    
    if(!empty($totalquerytime))
        echo 'Database: '
            ,(int) ($totalquerytime*1000+0.5)
            ,'ms&nbsp;&nbsp;+&nbsp;';

    if(!empty($starttime)) {
        list($usec, $sec) = explode(" ", microtime());
        $tottime=(int) (((float)$usec + (float)$sec - $starttime)*1000+0.5);
        echo "PHP: {$tottime}ms;&nbsp;&nbsp;&nbsp;&nbsp;";
    }
        
    echo 'gtd-php database:',_GTD_VERSION,', package:',_GTDPHP_VERSION,' rev',_GTD_REVISION;
?>
</div>
</body>
</html>
