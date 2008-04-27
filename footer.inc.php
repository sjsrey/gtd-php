<?php
gtd_handleEvent(_GTD_ON_FOOTER,$pagename);
include('showMessage.inc.php'); ?>
</div><!-- main -->
<?php if(isset($starttime)) {
    list($usec, $sec) = explode(" ", microtime());
    $tottime=(int) (((float)$usec + (float)$sec - $starttime)*1000);
} ?>
<div id='footer'>
    page generated in <?php echo $tottime; ?>ms
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	gtd-php version <?php echo _GTDPHP_VERSION,' rev',_GTD_REVISION; ?>
</div>
</div> <!-- Container-->
</body>
</html>
