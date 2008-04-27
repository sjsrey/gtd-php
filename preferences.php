<?php
//INCLUDES
include_once('header.inc.php');

// query theme directory to build dropdown selector
$themedir = "./themes";
if ($handle = opendir($themedir)) {
	while (false !== ($file = readdir($handle))) {
		if ($file[0] !== "." && is_dir($themedir. "/" . $file)) {
			$themes[] = $file;
		}
	}
	closedir($handle);
}

$html="";

// ran into a strange PHP bug when using "foreach ($themes as $theme)", so just using $t
foreach ($themes as $t) {
	$html.= "<option value='$t' ";
	if($t === $config['theme']) $html.=" selected='selected' ";
	$html.=">$t</option>\n";
}

if ($config['useLiveEnhancements']) {
    $useLiveEnhancements="<div class='formrow'>"
        ."<label for='useLiveEnhancements'>Use Live Enhancements</label>"
        ."<input type='checkbox'name='useLiveEnhancements' id='useLiveEnhancements' "
        .(($_SESSION['useLiveEnhancements'])?" checked='checked' ":'')
        ." />\n"
        ."<input type='hidden' name='checkboxes[]' value='useLiveEnhancements' />\n"
        ."</div>\n";
} else $useLiveEnhancements='';

?>

<h2>Preference</h2>
<form action="processPreferences.php" method="post">
    <div class='formrow'>
        <label for='theme'>Theme:</label>
        <select id='theme' name="theme">
            <?php echo $html; ?>
        </select>
    </div><?php
        echo $useLiveEnhancements;
    ?><div class='formbuttons'>
        <input type="submit" class="button" value="Apply" name="submit" id='submit' />
    </div>
</form>
<?php include_once('footer.inc.php'); ?>
