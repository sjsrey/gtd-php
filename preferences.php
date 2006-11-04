<?php

//INCLUDES
include_once('header.php');

// query theme directory to build dropdown selector
$themedir = "./themes";
if ($handle = opendir($themedir)) {	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != ".." && is_dir($themedir. "/" . $file)) {
			$themes[] = $file;		}	}	closedir($handle);}

$html="";

// ran into a strange PHP bug when using "foreach ($themes as $theme)", so just using $t
foreach ($themes as $t) {
	$html.= '<option value="'.$t;
	$html.='"';
	if($t == $_SESSION['theme']) $html.=" SELECTED ";
	$html.='>'.$t;
	$html.="</option>";
	$html.="\n";
}


// Display code
echo "<h2>Theme</h2>\n";
echo '<form action="updatePreferences.php" method="post">';
echo '<select name="theme">';
echo "\n";
echo $html;
echo "</select>\n"; echo '<input type="submit" class="button" value="Apply" name="submit">'."\n";
echo "</form>\n";








	echo "</div>\n";

	include_once('footer.php');
?>
