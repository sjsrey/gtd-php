<?php
//INCLUDES
include_once 'header.inc.php';

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


$optionsarray=array(
    array('title','text','title'),
    array('title_suffix','checkbox','Add filename to page title'),
    array('trimLength','text','Max length of descriptions in lists of items'),
    array('trimLengthInReport','text','Max length of descriptions of children in item reports'),
    array('firstDayOfWeek','select:0,Sunday;...','First day of week'),
    array('ReportMaxCompleteChildren','text','Max number of children of each type to display in item reports'),
    array('useLiveEnhancements','checkbox','Use javascript enhancements'),
    array('radioButtonsForNextPage','checkbox','when editin an item, display choice of next page as a radio group rather than as submit buttons'),
    // cycle through all options in headerMenu, for shortcut keys: but what do we use as option label???
    array('contextsummaryshowsonlynextactions','checkbox','In the space contexts report, show only <b>next</b> actions'),
    array('reviewProjectsWithoutOutcomes','checkbox','In the weekly review, identify projects without outcomes'),
);

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
<?php include_once 'footer.inc.php'; ?>
