<?php

//INCLUDES
include_once('header.php');

// stub for querying theme directory to build dropdown selector
$themes[0]='default';
$themes[1]='menu_sidebar';
$html="";
foreach ($themes as $theme) {
   $html.= '<option value="'.$theme;
   $html.='"';
   if($theme == $_SESSION['theme']) $html.=" SELECTED ";
   $html.='>'.$theme;
   $html.="</option>";
   $html.="\n";
}


// Display code
echo "<h2>Theme</h2>\n";
echo '<form action="updatePreferences.php?theme='.$theme.'" method="post">';
echo '<select name="theme">';
echo "\n";
echo $html;
echo "</select>\n"; echo '<input type="submit" class="button" value="Apply" name="submit">'."\n";
echo "</form>\n";








	echo "</div>\n";

	include_once('footer.php');
?>
