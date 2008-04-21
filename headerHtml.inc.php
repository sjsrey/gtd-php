<?php
    list($usec, $sec) = explode(" ", microtime());
    $starttime=(float)$usec + (float)$sec;
    require_once("headerDB.inc.php");

    if ($_SESSION['version']!==_GTD_VERSION && !isset($areUpdating) ) {
        $testver=query('getgtdphpversion',$config);
        if ($testver && _GTD_VERSION === array_pop(array_pop($testver)) ) {
            $_SESSION['version']=_GTD_VERSION;
        } else {
            $msg= ($testver)
                    ? "<p class='warning'>Your version of the database needs upgrading before we can continue.</p>"
                    : "<p class='warning'>No gtd-php installation found: please check the database prefix in config.php, and then install.</p>";
            $_SESSION['message']=array($msg); // remove warning about version not being found
            nextScreen('install.php');
            die;
        }
    }

    if (!headers_sent()) {
        $header="Content-Type: text/html; charset=".$config['charset'];
        header($header);
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
		  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=<?php echo $config['charset'];?>" />
<?php
$thisurl=parse_url($_SERVER['PHP_SELF']);

$title = '	<title>'.$config['title'];
if ($config['title_suffix']) { $title .= '-'.basename($thisurl['path'],".php");}
$title .= "</title>\n";

echo $title;

if ($config['debug'] || defined('_DEBUG'))
	echo '<style type="text/css">pre,.debug {}</style>';
if (!empty($_SESSION['theme']))
    $config['theme']=$_SESSION['theme'];
if (!isset($_SESSION['useLiveEnhancements']))
    $_SESSION['useLiveEnhancements']=$config['useLiveEnhancements'];
?>

<!-- theme main stylesheet -->
<link rel="stylesheet" href="themes/<?php echo $config['theme']; ?>/style.css" type="text/css"/>

<!-- theme screen stylesheet (should check to see if this actually exists) -->
<link rel="stylesheet" href="themes/<?php echo $config['theme']; ?>/style_screen.css" type="text/css" media="screen" />

<!-- theme script (should check to see if this actually exists) -->
<script type="text/javascript" src="themes/<?php echo $config['theme']; ?>/theme.js"></script>

<!-- printing stylesheet -->
<link rel="stylesheet" href="print.css" type="text/css" media="print" />

<link rel="shortcut icon" href="./favicon.ico" />

<!-- calendar stylesheet -->
<link rel="stylesheet" type="text/css" media="all" href="calendar-win2k-cold-1.css" title="win2k-cold-1" />

<!-- main calendar program -->
<script type="text/javascript" src="calendar.js"></script>

<!-- language for the calendar -->
<script type="text/javascript" src="lang/calendar-en.js"></script>

<!-- the following script defines the Calendar.setup helper function, which makes
	  adding a calendar a matter of 1 or 2 lines of code. -->
<script type="text/javascript" src="calendar-setup.js"></script>

<!-- sort tables, and other utilities -->
<script type="text/javascript" src="gtdfuncs.js"></script>

<?php if ($_SESSION['useLiveEnhancements']) { ?>
<!-- enhancements for live feedback -->
<script type="text/javascript" src="enhancers.js"></script>

<?php
}
if ($config['debug'] || defined('_DEBUG'))
	echo '<script type="text/javascript">aps_debugInit("',$config['debugKey'],'");</script>'; ?>
