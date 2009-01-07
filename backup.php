<?php
header('Content-type: text/plain');
header('Content-Disposition: attachment; filename="gtdphpBackup.sql"');
@ob_start();
require_once 'headerDB.inc.php';
@ob_end_flush();
echo backupData($_GET['prefix']);