<?php
$title='Portal';
include_once 'header.inc.php';

$portal_links=$addon[_GTD_ON_MENU.'-*']['portal_links'];

foreach ($portal_links as $header => $link) {
	echo '<h2>'.$header.'</h2><ul>';
	foreach ($link as $title => $url) {
		echo '<li><a href="'.$url.'" target="_NEW">'.$title.'</a></li>';
		}
	echo '</ul>';
	}

include_once 'footer.inc.php';
