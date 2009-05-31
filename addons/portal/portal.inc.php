<?php
$title='Portal';
include_once 'header.inc.php';

foreach ($addon['portal_links'] as $header => $link) {
	echo '<h2>'.$header.'</h2><ul>';
	foreach ($link as $title => $url) {
		echo '<li><a href="'.$url.'" target="_NEW">'.$title.'</a></li>';
		}
	echo '</ul>';
	}

include_once 'footer.inc.php';
